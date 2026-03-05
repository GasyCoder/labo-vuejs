<?php

namespace App\Http\Controllers\Technicien;

use App\Http\Controllers\Controller;
use App\Http\Requests\Technicien\SaveResultatRequest;
use App\Models\Analyse;
use App\Models\Prescription;
use App\Models\Resultat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultatController extends Controller
{
    /**
     * Auto-save a single result using UPSERT for atomicity.
     * Prevents 1062 Duplicate entry errors.
     */
    public function save(SaveResultatRequest $request): JsonResponse
    {
        try {
            // UPSERT : Solution atomique MySQL (INSERT ... ON DUPLICATE KEY UPDATE)
            // Élimine totalement les erreurs de concurrence 1062.
            Resultat::upsert([
                [
                    'prescription_id' => $request->prescription_id,
                    'analyse_id' => $request->analyse_id,
                    'valeur' => $request->valeur,
                    'resultats' => is_array($request->resultats) ? json_encode($request->resultats, JSON_UNESCAPED_UNICODE) : $request->resultats,
                    'interpretation' => $request->interpretation ?: 'NORMAL',
                    'status' => 'EN_COURS',
                    'updated_at' => now(),
                    // Note: created_at sera ignoré lors d'un UPDATE si on ne le met pas dans le 3ème argument
                    'created_at' => now(),
                ],
            ],
                ['prescription_id', 'analyse_id'], // Colonnes de la contrainte UNIQUE
                ['valeur', 'resultats', 'interpretation', 'status', 'updated_at'] // Colonnes à mettre à jour
            );

            // Récupérer l'ID pour la réponse (inclure withTrashed au cas où un ancien résultat existe en corbeille)
            $resultat = Resultat::withTrashed()
                ->where('prescription_id', $request->prescription_id)
                ->where('analyse_id', $request->analyse_id)
                ->first();

            if ($resultat && $resultat->trashed()) {
                $resultat->restore();
            }

            // Mettre à jour le statut de la prescription
            $prescription = Prescription::find($request->prescription_id);
            if ($prescription && $prescription->status === 'EN_ATTENTE') {
                $prescription->update([
                    'status' => 'EN_COURS',
                    'technicien_id' => Auth::id(),
                ]);
                DB::table('prescription_analyse')
                    ->where('prescription_id', $prescription->id)
                    ->update(['status' => 'EN_COURS', 'updated_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'resultat_id' => $resultat?->id,
                'saved_at' => now()->format('H:i'),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur critique auto-save UPSERT', [
                'prescription_id' => $request->prescription_id,
                'analyse_id' => $request->analyse_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save all results at once using batch upsert
     */
    public function saveAll(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer|exists:prescriptions,id',
            'results' => 'required|array',
            'results.*.analyse_id' => 'required|integer',
            'results.*.valeur' => 'nullable',
            'results.*.resultats' => 'nullable',
            'results.*.interpretation' => 'nullable|string',
        ]);

        try {
            $prescription = Prescription::findOrFail($request->prescription_id);
            $upsertData = [];

            foreach ($request->results as $data) {
                $upsertData[] = [
                    'prescription_id' => $prescription->id,
                    'analyse_id' => $data['analyse_id'],
                    'valeur' => $data['valeur'] ?? null,
                    'resultats' => isset($data['resultats']) && is_array($data['resultats'])
                        ? json_encode($data['resultats'], JSON_UNESCAPED_UNICODE)
                        : ($data['resultats'] ?? null),
                    'interpretation' => $data['interpretation'] ?? 'NORMAL',
                    'status' => 'EN_COURS',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($upsertData)) {
                Resultat::upsert($upsertData, ['prescription_id', 'analyse_id'], ['valeur', 'resultats', 'interpretation', 'status', 'updated_at']);
            }

            if ($prescription->status === 'EN_ATTENTE') {
                $prescription->update([
                    'status' => 'EN_COURS',
                    'technicien_id' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'saved_at' => now()->format('H:i'),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur saveAll résultats UPSERT', [
                'prescription_id' => $request->prescription_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur de groupe : '.$e->getMessage(),
            ], 500);
        }
    }

    // ... (le reste des méthodes reste identique)

    /**
     * Recursively collect all leaf descendant IDs (active, non-LABEL).
     */
    private function collectLeafDescendantIds(int $parentId): array
    {
        $children = Analyse::where('parent_id', $parentId)
            ->where('status', true)
            ->whereHas('type', fn ($q) => $q->where('name', '!=', 'LABEL'))
            ->get(['id']);

        if ($children->isEmpty()) {
            return [];
        }

        $leafIds = [];
        foreach ($children as $child) {
            $childLeafs = $this->collectLeafDescendantIds($child->id);
            if (empty($childLeafs)) {
                $leafIds[] = $child->id;
            } else {
                $leafIds = array_merge($leafIds, $childLeafs);
            }
        }

        return $leafIds;
    }

    public function completeAnalyse(int $parentId, Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer|exists:prescriptions,id']);
        DB::beginTransaction();
        try {
            $prescription = Prescription::findOrFail($request->prescription_id);
            $leafIds = $this->collectLeafDescendantIds($parentId);
            $targets = empty($leafIds) ? [$parentId] : $leafIds;
            $updated = $prescription->resultats()->whereIn('analyse_id', $targets)->update(['status' => 'TERMINE']);
            $principalIds = DB::table('prescription_analyse')->where('prescription_id', $prescription->id)->pluck('analyse_id')->toArray();
            if (in_array($parentId, $principalIds)) {
                DB::table('prescription_analyse')->where('prescription_id', $prescription->id)->where('analyse_id', $parentId)->update(['status' => 'TERMINE', 'updated_at' => now()]);
            }
            $allDone = $this->checkAllAnalysesCompleted($prescription);
            if ($allDone) {
                $prescription->update(['status' => 'TERMINE']);
            }
            DB::commit();

            return response()->json(['success' => true, 'updated' => $updated, 'prescription_completed' => $allDone]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function completePrescription(int $prescriptionId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $prescription = Prescription::findOrFail($prescriptionId);
            $missingAnalyses = [];
            $attachedIds = DB::table('prescription_analyse')->where('prescription_id', $prescription->id)->pluck('analyse_id')->toArray();
            foreach ($attachedIds as $analyseId) {
                $analyseRoot = Analyse::find($analyseId);
                if (! $analyseRoot || ! $analyseRoot->status) {
                    continue;
                }
                $leafIds = $this->collectLeafDescendantIds($analyseId);
                $targets = empty($leafIds) ? [$analyseRoot] : Analyse::whereIn('id', $leafIds)->with('type:id,name')->get();
                foreach ($targets as $target) {
                    $resultat = $prescription->resultats()->where('analyse_id', $target->id)->first();
                    $isFilled = false;
                    if ($resultat) {
                        $hasValeur = $resultat->valeur !== null && trim((string) $resultat->valeur) !== '';
                        $rawResultats = $resultat->resultats;
                        $hasResultats = is_array($rawResultats) ? ! empty($rawResultats) : ($rawResultats !== null && trim((string) $rawResultats) !== '');
                        $typeName = $target->type->name ?? '';
                        if ($hasValeur || $hasResultats || in_array($typeName, ['GERME', 'CULTURE'])) {
                            $isFilled = true;
                        }
                    }
                    if (! $isFilled) {
                        $missingAnalyses[] = $target->designation;
                    }
                }
            }
            if (! empty($missingAnalyses)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Analyses incomplètes.'], 422);
            }
            $prescription->resultats()->update(['status' => 'TERMINE']);
            DB::table('prescription_analyse')->where('prescription_id', $prescription->id)->update(['status' => 'TERMINE', 'updated_at' => now()]);
            $prescription->update(['status' => 'TERMINE']);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Prescription finalisée.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function saveGroupConclusion(Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer|exists:prescriptions,id', 'examen_id' => 'required|integer|exists:examens,id', 'conclusion' => 'nullable|string']);
        try {
            \App\Models\PrescriptionExamenConclusion::updateOrCreate(['prescription_id' => $request->prescription_id, 'examen_id' => $request->examen_id], ['conclusion' => $request->conclusion ?: null, 'created_by' => Auth::id()]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function addConclusionNote(Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer|exists:prescriptions,id', 'analyse_id' => 'required|integer|exists:analyses,id', 'note' => 'required|string']);
        try {
            $created = \App\Models\AnalyseConclusionNote::create(['prescription_id' => $request->prescription_id, 'analyse_id' => $request->analyse_id, 'technicien_id' => Auth::id(), 'note' => $request->note]);

            return response()->json(['success' => true, 'note' => ['id' => $created->id, 'note' => $created->note, 'created_at' => optional($created->created_at)->format('d/m/Y H:i'), 'technicien_name' => Auth::user()->name]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateConclusionNote(int $id, Request $request): JsonResponse
    {
        $request->validate(['note' => 'required|string']);
        try {
            $note = \App\Models\AnalyseConclusionNote::findOrFail($id);
            $note->update(['note' => $request->note]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteConclusionNote(int $id): JsonResponse
    {
        try {
            \App\Models\AnalyseConclusionNote::findOrFail($id)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getNotes(Analyse $analyse, Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer|exists:prescriptions,id']);
        
        $notes = \App\Models\AnalyseConclusionNote::where('prescription_id', $request->prescription_id)
            ->where('analyse_id', $analyse->id)
            ->with('technicien:id,name')
            ->latest()
            ->get();

        return response()->json($notes);
    }

    public function syncAntibiogrammes(Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer|exists:prescriptions,id', 'analyse_id' => 'required|integer|exists:analyses,id', 'bacteries' => 'array']);
        try {
            $prescriptionId = $request->prescription_id;
            $analyseId = $request->analyse_id;
            $bacteries = $request->bacteries ?? [];
            \App\Models\Antibiogramme::where('prescription_id', $prescriptionId)->where('analyse_id', $analyseId)->whereNotIn('bacterie_id', $bacteries)->delete();
            foreach ($bacteries as $bacterieId) {
                \App\Models\Antibiogramme::firstOrCreate(['prescription_id' => $prescriptionId, 'analyse_id' => $analyseId, 'bacterie_id' => $bacterieId]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getAntibiogrammesData(Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer', 'analyse_id' => 'required|integer', 'bacterie_id' => 'required|integer']);
        $antibiogramme = \App\Models\Antibiogramme::where(['prescription_id' => $request->prescription_id, 'analyse_id' => $request->analyse_id, 'bacterie_id' => $request->bacterie_id])->first();
        $resultats = [];
        $antibiotiquesUtilises = [];
        if ($antibiogramme) {
            $resultatsRaw = \App\Models\ResultatAntibiotique::where('antibiogramme_id', $antibiogramme->id)->with('antibiotique:id,designation')->orderBy('created_at')->get();
            foreach ($resultatsRaw as $r) {
                $antibiotiquesUtilises[] = $r->antibiotique_id;
                $resultats[] = ['id' => $r->id, 'antibiotique_id' => $r->antibiotique_id, 'antibiotique_designation' => $r->antibiotique->designation ?? '—', 'interpretation' => $r->interpretation, 'diametre_mm' => $r->diametre_mm];
            }
        }
        $antibiotiquesDisponibles = \App\Models\Antibiotique::actives()->whereNotIn('id', $antibiotiquesUtilises)->orderBy('designation')->get(['id', 'designation']);

        return response()->json(['success' => true, 'antibiogramme_id' => $antibiogramme->id ?? null, 'resultats' => $resultats, 'antibiotiquesDisponibles' => $antibiotiquesDisponibles]);
    }

    public function addAntibiotique(Request $request): JsonResponse
    {
        $request->validate(['prescription_id' => 'required|integer', 'analyse_id' => 'required|integer', 'bacterie_id' => 'required|integer', 'antibiotique_id' => 'required|integer|exists:antibiotiques,id', 'interpretation' => 'required|in:S,I,R', 'diametre_mm' => 'nullable|numeric|min:0|max:50']);
        try {
            $antibiogramme = \App\Models\Antibiogramme::firstOrCreate(['prescription_id' => $request->prescription_id, 'analyse_id' => $request->analyse_id, 'bacterie_id' => $request->bacterie_id]);
            \App\Models\ResultatAntibiotique::updateOrCreate(['antibiogramme_id' => $antibiogramme->id, 'antibiotique_id' => $request->antibiotique_id], ['interpretation' => $request->interpretation, 'diametre_mm' => $request->diametre_mm ?: null]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateResultatAntibiotique(int $id, Request $request): JsonResponse
    {
        $request->validate(['field' => 'required|in:interpretation,diametre_mm', 'value' => 'nullable']);
        try {
            $resultat = \App\Models\ResultatAntibiotique::findOrFail($id);
            $val = $request->value;
            if ($request->field === 'interpretation' && ! in_array($val, ['S', 'I', 'R'])) {
                throw new \Exception('Interprétation invalide');
            }
            if ($request->field === 'diametre_mm') {
                $val = trim($val) === '' ? null : (float) $val;
            }
            $resultat->update([$request->field => $val]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteResultatAntibiotique(int $id): JsonResponse
    {
        try {
            \App\Models\ResultatAntibiotique::findOrFail($id)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function checkAllAnalysesCompleted(Prescription $prescription): bool
    {
        $attachedIds = $prescription->analyses()->pluck('analyses.id')->all();
        foreach ($attachedIds as $analyseId) {
            $analyse = Analyse::find($analyseId);
            if (! $analyse) {
                continue;
            }
            $leafIds = $this->collectLeafDescendantIds($analyseId);
            $targets = empty($leafIds) ? [$analyseId] : $leafIds;
            foreach ($targets as $targetId) {
                $resultat = $prescription->resultats()->where('analyse_id', $targetId)->first();
                if (! $resultat || $resultat->status !== 'TERMINE') {
                    return false;
                }
            }
        }

        return true;
    }
}
