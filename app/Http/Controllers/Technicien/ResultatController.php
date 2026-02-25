<?php

namespace App\Http\Controllers\Technicien;

use App\Http\Controllers\Controller;
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
     * Auto-save a single result (called on field change via debounce)
     */
    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer|exists:prescriptions,id',
            'analyse_id' => 'required|integer|exists:analyses,id',
            'valeur' => 'nullable',
            'resultats' => 'nullable',
            'interpretation' => 'nullable|string|in:NORMAL,PATHOLOGIQUE',
        ]);

        try {
            $resultat = Resultat::updateOrCreate(
                [
                    'prescription_id' => $request->prescription_id,
                    'analyse_id' => $request->analyse_id,
                ],
                [
                    'valeur' => $request->valeur,
                    'resultats' => is_array($request->resultats)
                        ? json_encode($request->resultats, JSON_UNESCAPED_UNICODE)
                        : $request->resultats,
                    'interpretation' => $request->interpretation ?: 'NORMAL',
                    'status' => 'EN_COURS',
                ]
            );

            // Update prescription status if needed
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
                'resultat_id' => $resultat->id,
                'saved_at' => now()->format('H:i'),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur auto-save résultat', [
                'prescription_id' => $request->prescription_id,
                'analyse_id' => $request->analyse_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save all results at once
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

        DB::beginTransaction();
        try {
            $prescription = Prescription::findOrFail($request->prescription_id);

            foreach ($request->results as $data) {
                Resultat::updateOrCreate(
                    [
                        'prescription_id' => $prescription->id,
                        'analyse_id' => $data['analyse_id'],
                    ],
                    [
                        'valeur' => $data['valeur'] ?? null,
                        'resultats' => isset($data['resultats']) && is_array($data['resultats'])
                            ? json_encode($data['resultats'], JSON_UNESCAPED_UNICODE)
                            : ($data['resultats'] ?? null),
                        'interpretation' => $data['interpretation'] ?? 'NORMAL',
                        'status' => 'EN_COURS',
                    ]
                );
            }

            if ($prescription->status === 'EN_ATTENTE') {
                $prescription->update([
                    'status' => 'EN_COURS',
                    'technicien_id' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'saved_at' => now()->format('H:i'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur saveAll résultats', [
                'prescription_id' => $request->prescription_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recursively collect all leaf descendant IDs (active, non-LABEL).
     *
     * @return int[]
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
                // This child is a leaf (no active non-LABEL descendants)
                $leafIds[] = $child->id;
            } else {
                $leafIds = array_merge($leafIds, $childLeafs);
            }
        }

        return $leafIds;
    }

    /**
     * Mark a single analyse as completed
     */
    public function completeAnalyse(int $parentId, Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer|exists:prescriptions,id',
        ]);

        DB::beginTransaction();
        try {
            $prescription = Prescription::findOrFail($request->prescription_id);

            // Get ALL leaf descendants recursively (not just direct children)
            $leafIds = $this->collectLeafDescendantIds($parentId);
            $targets = empty($leafIds) ? [$parentId] : $leafIds;

            $updated = $prescription->resultats()
                ->whereIn('analyse_id', $targets)
                ->update(['status' => 'TERMINE']);

            // Update pivot table
            $principalIds = DB::table('prescription_analyse')
                ->where('prescription_id', $prescription->id)
                ->pluck('analyse_id')->toArray();

            if (in_array($parentId, $principalIds)) {
                DB::table('prescription_analyse')
                    ->where('prescription_id', $prescription->id)
                    ->where('analyse_id', $parentId)
                    ->update(['status' => 'TERMINE', 'updated_at' => now()]);
            }

            // Check if all analyses are done → auto-complete prescription
            $allDone = $this->checkAllAnalysesCompleted($prescription);
            if ($allDone) {
                $prescription->update(['status' => 'TERMINE']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'prescription_completed' => $allDone,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur completeAnalyse', [
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize the entire prescription
     */
    public function completePrescription(int $prescriptionId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $prescription = Prescription::findOrFail($prescriptionId);

            // Validation: verify that all expected analyses have filled results
            $missingAnalyses = [];
            $attachedIds = DB::table('prescription_analyse')
                ->where('prescription_id', $prescription->id)
                ->pluck('analyse_id')->toArray();

            foreach ($attachedIds as $analyseId) {
                $analyseRoot = Analyse::find($analyseId);
                if (! $analyseRoot || ! $analyseRoot->status) {
                    continue;
                }

                // Get ALL leaf descendants recursively (not just direct children)
                $leafIds = $this->collectLeafDescendantIds($analyseId);

                if (empty($leafIds)) {
                    // No children → the root itself is the leaf
                    $targets = collect([$analyseRoot]);
                } else {
                    $targets = Analyse::whereIn('id', $leafIds)->with('type:id,name')->get();
                }

                foreach ($targets as $target) {
                    $resultat = $prescription->resultats()->where('analyse_id', $target->id)->first();
                    $isFilled = false;

                    if ($resultat) {
                        $hasValeur = $resultat->valeur !== null && trim((string) $resultat->valeur) !== '';
                        $rawResultats = $resultat->resultats;
                        $hasResultats = is_array($rawResultats) ? ! empty($rawResultats) : ($rawResultats !== null && trim((string) $rawResultats) !== '');
                        $typeName = $target->type->name ?? '';

                        // For Germe/Culture, having a record is sometimes enough if they just selected bacterias
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
                $msg = 'Analyses incomplètes : '.implode(', ', array_slice($missingAnalyses, 0, 3));
                if (count($missingAnalyses) > 3) {
                    $msg .= ' et '.(count($missingAnalyses) - 3).' autre(s)';
                }

                return response()->json([
                    'success' => false,
                    'missing' => $missingAnalyses,
                    'message' => $msg,
                ], 422);
            }

            // Mark all results as completed
            $prescription->resultats()->update(['status' => 'TERMINE']);

            // Update all pivot entries
            DB::table('prescription_analyse')
                ->where('prescription_id', $prescription->id)
                ->update(['status' => 'TERMINE', 'updated_at' => now()]);

            // Mark prescription as completed
            $prescription->update(['status' => 'TERMINE']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Prescription finalisée avec succès.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur completePrescription', [
                'prescription_id' => $prescriptionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Group Conclusions
     */
    public function saveGroupConclusion(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer|exists:prescriptions,id',
            'examen_id' => 'required|integer|exists:examens,id',
            'conclusion' => 'nullable|string',
        ]);

        try {
            \App\Models\PrescriptionExamenConclusion::updateOrCreate(
                [
                    'prescription_id' => $request->prescription_id,
                    'examen_id' => $request->examen_id,
                ],
                [
                    'conclusion' => $request->conclusion ?: null,
                    'created_by' => Auth::id(),
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Partial Notes (AnalyseConclusionNote)
     */
    public function addConclusionNote(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer|exists:prescriptions,id',
            'analyse_id' => 'required|integer|exists:analyses,id',
            'note' => 'required|string',
        ]);

        try {
            $created = \App\Models\AnalyseConclusionNote::create([
                'prescription_id' => $request->prescription_id,
                'analyse_id' => $request->analyse_id,
                'technicien_id' => Auth::id(),
                'note' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'note' => [
                    'id' => $created->id,
                    'note' => $created->note,
                    'created_at' => optional($created->created_at)->format('d/m/Y H:i'),
                    'technicien_name' => Auth::user()->name,
                ],
            ]);
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

    /**
     * Antibiogrammes Sync (Germe / Culture)
     */
    public function syncAntibiogrammes(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer|exists:prescriptions,id',
            'analyse_id' => 'required|integer|exists:analyses,id',
            'bacteries' => 'array',
        ]);

        try {
            $prescriptionId = $request->prescription_id;
            $analyseId = $request->analyse_id;
            $bacteries = $request->bacteries ?? [];

            // Supprimer les antibiogrammes pour les bactéries qui ne sont plus sélectionnées
            \App\Models\Antibiogramme::where('prescription_id', $prescriptionId)
                ->where('analyse_id', $analyseId)
                ->whereNotIn('bacterie_id', $bacteries)
                ->delete(); // La cascade DB devrait supprimer les resultats_antibiotiques associés, ou on peut le laisser si défini

            // Créer les antibiogrammes pour les nouvelles bactéries sélectionnées
            foreach ($bacteries as $bacterieId) {
                \App\Models\Antibiogramme::firstOrCreate([
                    'prescription_id' => $prescriptionId,
                    'analyse_id' => $analyseId,
                    'bacterie_id' => $bacterieId,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getAntibiogrammesData(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer',
            'analyse_id' => 'required|integer',
            'bacterie_id' => 'required|integer',
        ]);

        $antibiogramme = \App\Models\Antibiogramme::where([
            'prescription_id' => $request->prescription_id,
            'analyse_id' => $request->analyse_id,
            'bacterie_id' => $request->bacterie_id,
        ])->first();

        $resultats = [];
        $antibiotiquesUtilises = [];

        if ($antibiogramme) {
            $resultatsRaw = \App\Models\ResultatAntibiotique::where('antibiogramme_id', $antibiogramme->id)
                ->with('antibiotique:id,designation')
                ->orderBy('created_at')
                ->get();

            foreach ($resultatsRaw as $r) {
                $antibiotiquesUtilises[] = $r->antibiotique_id;
                $resultats[] = [
                    'id' => $r->id,
                    'antibiotique_id' => $r->antibiotique_id,
                    'antibiotique_designation' => $r->antibiotique->designation ?? '—',
                    'interpretation' => $r->interpretation,
                    'diametre_mm' => $r->diametre_mm,
                ];
            }
        }

        $antibiotiquesDisponibles = \App\Models\Antibiotique::actives()
            ->whereNotIn('id', $antibiotiquesUtilises)
            ->orderBy('designation')
            ->get(['id', 'designation']);

        return response()->json([
            'success' => true,
            'antibiogramme_id' => $antibiogramme->id ?? null,
            'resultats' => $resultats,
            'antibiotiquesDisponibles' => $antibiotiquesDisponibles,
        ]);
    }

    public function addAntibiotique(Request $request): JsonResponse
    {
        $request->validate([
            'prescription_id' => 'required|integer',
            'analyse_id' => 'required|integer',
            'bacterie_id' => 'required|integer',
            'antibiotique_id' => 'required|integer|exists:antibiotiques,id',
            'interpretation' => 'required|in:S,I,R',
            'diametre_mm' => 'nullable|numeric|min:0|max:50',
        ]);

        try {
            $antibiogramme = \App\Models\Antibiogramme::firstOrCreate([
                'prescription_id' => $request->prescription_id,
                'analyse_id' => $request->analyse_id,
                'bacterie_id' => $request->bacterie_id,
            ]);

            \App\Models\ResultatAntibiotique::updateOrCreate(
                [
                    'antibiogramme_id' => $antibiogramme->id,
                    'antibiotique_id' => $request->antibiotique_id,
                ],
                [
                    'interpretation' => $request->interpretation,
                    'diametre_mm' => $request->diametre_mm ?: null,
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateResultatAntibiotique(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'field' => 'required|in:interpretation,diametre_mm',
            'value' => 'nullable',
        ]);

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

            // Get ALL leaf descendants recursively (not just direct children)
            $leafIds = $this->collectLeafDescendantIds($analyseId);
            $targets = empty($leafIds) ? [$analyseId] : $leafIds;

            foreach ($targets as $targetId) {
                $resultat = $prescription->resultats()
                    ->where('analyse_id', $targetId)
                    ->first();

                if (! $resultat || $resultat->status !== 'TERMINE') {
                    return false;
                }
            }
        }

        return true;
    }
}
