<?php

namespace App\Http\Controllers;

use App\Models\Analyse;
use App\Models\AnalyseConclusionNote;
use App\Models\BacterieFamille;
use App\Models\Prescription;
use App\Models\PrescriptionExamenConclusion;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionWorkspaceController extends Controller
{
    public function showTechnicien(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'technicien', 'Espace Technicien');
    }

    public function showBiologiste(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'biologiste', 'Espace Biologiste');
    }

    public function showBiologisteValidation(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'biologiste-validation', 'Validation Biologiste');
    }

    public function showAdmin(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'admin', 'Consultation Admin');
    }

    private function renderWorkspace(Prescription $prescription, string $context, string $title): Response
    {
        $prescription->load([
            'patient',
            'prescripteur:id,nom,prenom',
            'analyses' => fn ($q) => $q->with(['type:id,name', 'examen:id,name', 'enfantsRecursive.type:id,name'])
                ->orderBy('ordre')->orderBy('id'),
            'resultats:id,prescription_id,analyse_id,valeur,resultats,interpretation,status',
        ]);

        $patient = $prescription->patient;
        $attachedIds = $prescription->analyses->pluck('id')->all();
        $resultatsMap = $prescription->resultats->keyBy('analyse_id');

        // Build parent analyses list (same logic as legacy AnalysesSidebar)
        $parents = $prescription->analyses->filter(function ($analyse) use ($attachedIds) {
            return $analyse->level === 'PARENT'
                || is_null($analyse->parent_id)
                || ! in_array($analyse->parent_id, $attachedIds);
        });

        $analysesTree = [];
        foreach ($parents as $parent) {
            $analysesTree[] = $this->buildAnalyseNode($parent, $prescription, $resultatsMap, $patient);
        }

        return Inertia::render('Shared/PrescriptionWorkspace', [
            'context' => $context,
            'title' => $title,
            'prescription' => [
                'id' => $prescription->id,
                'reference' => $prescription->reference,
                'status' => $prescription->status,
                'created_at' => $prescription->created_at?->format('d/m/Y H:i'),
                'patient' => $patient ? [
                    'id' => $patient->id,
                    'nom' => $patient->nom,
                    'prenom' => $patient->prenom,
                    'nom_complet' => trim(sprintf('%s %s', $patient->nom, $patient->prenom ?? '')),
                    'telephone' => $patient->telephone,
                    'civilite' => $patient->civilite ?? null,
                    'numero_dossier' => $patient->numero_dossier ?? null,
                    'age' => $prescription->age ?? null,
                    'unite_age' => $prescription->unite_age ?? null,
                ] : null,
                'prescripteur' => $prescription->prescripteur ? [
                    'nom' => $prescription->prescripteur->nom,
                    'prenom' => $prescription->prescripteur->prenom ?? '',
                ] : null,
                'analyses_count' => $prescription->analyses->count(),
            ],
            'analysesTree' => $analysesTree,
            'familles' => BacterieFamille::actives()->with(['bacteries' => function ($query) {
                $query->where('status', true);
            }])->get(),
            'notes' => AnalyseConclusionNote::with('technicien:id,name')
                ->where('prescription_id', $prescription->id)
                ->get()
                ->groupBy('analyse_id')
                ->map(function ($items) {
                    return $items->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'note' => $note->note,
                            'created_at' => $note->created_at?->format('d/m/Y H:i'),
                            'technicien_name' => $note->technicien?->name,
                        ];
                    });
                }),
            'conclusionsGroupes' => PrescriptionExamenConclusion::where('prescription_id', $prescription->id)
                ->get()
                ->keyBy('examen_id')
                ->map(fn ($c) => $c->conclusion),
            'canEditConclusions' => auth()->user()?->can('analyses.conclusion') ?? true, // Fallback to true if no permission set for testing
            'resultats' => $resultatsMap->map(fn ($r) => [
                'id' => $r->id,
                'analyse_id' => $r->analyse_id,
                'valeur' => $r->valeur,
                'resultats' => $r->resultats,
                'interpretation' => $r->interpretation,
                'status' => $r->status,
            ])->values(),
        ]);
    }

    private function isAnalyseFilled(Analyse $analyse, $resultat, int $prescriptionId): bool
    {
        if (in_array($analyse->type?->name, ['GERME', 'CULTURE'])) {
            $hasNonSterile = \Illuminate\Support\Facades\DB::table('analyse_standards')
                ->where('prescription_id', $prescriptionId)
                ->where('analyse_id', $analyse->id)
                ->where('is_checked', true)
                ->exists();

            $hasBacteria = \Illuminate\Support\Facades\DB::table('analyse_bacteria')
                ->where('prescription_id', $prescriptionId)
                ->where('analyse_id', $analyse->id)
                ->exists();

            return $hasNonSterile || $hasBacteria;
        }

        if (! $resultat) {
            return false;
        }

        if ($resultat->valeur !== null && $resultat->valeur !== '') {
            return true;
        }

        if ($resultat->resultats !== null && $resultat->resultats !== 'null' && $resultat->resultats !== '[]' && $resultat->resultats !== '') {
            return true;
        }

        if ($resultat->status === 'TERMINE') {
            return true;
        }

        return false;
    }

    /**
     * Recursively collect all leaf descendant IDs (active, non-LABEL) from the eager-loaded tree.
     *
     * @return int[]
     */
    private function collectLeafIds($analyse): array
    {
        $leafIds = [];

        // If no children loaded, this node has no descendants to walk
        if (! $analyse->relationLoaded('enfantsRecursive') || $analyse->enfantsRecursive->isEmpty()) {
            return $leafIds;
        }

        foreach ($analyse->enfantsRecursive as $child) {
            // Skip inactive or LABEL type analyses
            if (! $child->status || $child->type?->name === 'LABEL') {
                continue;
            }

            $childLeafs = $this->collectLeafIds($child);
            if (empty($childLeafs)) {
                // This child is a leaf (no active non-LABEL descendants)
                $leafIds[] = $child->id;
            } else {
                $leafIds = array_merge($leafIds, $childLeafs);
            }
        }

        return $leafIds;
    }

    private function buildAnalyseNode(Analyse $analyse, Prescription $prescription, $resultatsMap, $patient): array
    {
        // Collect ALL leaf descendant IDs recursively (matching legacy AnalysesSidebar)
        // Uses eager-loaded enfantsRecursive to avoid N+1 queries
        $enfants = $this->collectLeafIds($analyse);

        // Count leaves with existing results and leaves marked TERMINE
        $enfantsCompleted = 0;
        $enfantsTermine = 0;
        foreach ($enfants as $leafId) {
            if ($resultatsMap->has($leafId)) {
                $enfantsCompleted++;
                if ($resultatsMap->get($leafId)->status === 'TERMINE') {
                    $enfantsTermine++;
                }
            }
        }

        // Determine status (replicating legacy getAnalysisStatus logic)
        $hasResult = $resultatsMap->has($analyse->id);
        $resultStatus = $resultatsMap->get($analyse->id)?->status;

        if (empty($enfants)) {
            // Analyse without children → check direct result
            $isFilled = $this->isAnalyseFilled($analyse, $resultatsMap->get($analyse->id), $prescription->id);
            $status = ($resultStatus === 'TERMINE' || $isFilled) ? 'TERMINE' : ($hasResult ? 'EN_COURS' : 'VIDE');
            $canComplete = $isFilled && $resultStatus !== 'TERMINE';
        } else {
            $allHaveResults = $enfantsCompleted > 0 && $enfantsCompleted === count($enfants);
            $allTermine = $enfantsTermine > 0 && $enfantsTermine === count($enfants);
            $someHaveResults = $enfantsCompleted > 0;

            if ($allHaveResults && $allTermine) {
                $status = 'TERMINE';
                $canComplete = false;
            } elseif ($allHaveResults) {
                $status = 'EN_COURS';
                $canComplete = true;
            } elseif ($someHaveResults) {
                $status = 'EN_COURS';
                $canComplete = false;
            } else {
                $status = 'VIDE';
                $canComplete = false;
            }
        }

        // Build children tree for the form
        $childrenTree = [];
        if ($analyse->enfantsRecursive && $analyse->enfantsRecursive->count() > 0) {
            foreach ($analyse->enfantsRecursive as $child) {
                $childrenTree[] = $this->buildChildNode($child, $resultatsMap, $patient);
            }
        }

        // If no children, the parent itself is the input
        if (empty($childrenTree)) {
            $childrenTree[] = $this->buildChildNode($analyse, $resultatsMap, $patient);
        }

        return [
            'id' => $analyse->id,
            'examen_id' => $analyse->examen_id,
            'code' => $analyse->code,
            'designation' => $analyse->designation,
            'enfants_count' => count($enfants),
            'enfants_completed' => $enfantsCompleted,
            'status' => $status,
            'can_complete' => $canComplete,
            'children' => $childrenTree,
        ];
    }

    private function buildChildNode(Analyse $analyse, $resultatsMap, $patient): array
    {
        $typeName = strtoupper($analyse->type?->name ?? 'INPUT');
        $resultat = $resultatsMap->get($analyse->id);

        $node = [
            'id' => $analyse->id,
            'examen_id' => $analyse->examen_id,
            'code' => $analyse->code,
            'designation' => $analyse->designation,
            'type' => $typeName,
            'unite' => $analyse->unite,
            'suffixe' => $analyse->suffixe,
            'is_bold' => $analyse->is_bold,
            'valeur_ref' => $analyse->getValeurReferenceByPatient($patient),
            'valeur_ref_label' => $analyse->getLabelValeurReferenceByPatient($patient),
            'valeurs_predefinies' => $analyse->valeurs_predefinies,
            'resultat' => $resultat ? [
                'id' => $resultat->id,
                'valeur' => $resultat->valeur,
                'resultats' => $resultat->resultats,
                'interpretation' => $resultat->interpretation,
                'status' => $resultat->status,
            ] : null,
            'children' => [],
        ];

        // Recursive children
        if ($analyse->enfantsRecursive && $analyse->enfantsRecursive->count() > 0) {
            foreach ($analyse->enfantsRecursive as $child) {
                $node['children'][] = $this->buildChildNode($child, $resultatsMap, $patient);
            }
        }

        return $node;
    }

    public function getProgression(Prescription $prescription): \Illuminate\Http\JsonResponse
    {
        $prescription->load([
            'patient',
            'analyses' => fn ($q) => $q->with(['type:id,name', 'examen:id,name', 'enfantsRecursive.type:id,name'])
                ->orderBy('ordre')->orderBy('id'),
            'resultats:id,prescription_id,analyse_id,valeur,resultats,interpretation,status',
        ]);

        $patient = $prescription->patient;
        $attachedIds = $prescription->analyses->pluck('id')->all();
        $resultatsMap = $prescription->resultats->keyBy('analyse_id');

        $parents = $prescription->analyses->filter(function ($analyse) use ($attachedIds) {
            return $analyse->level === 'PARENT'
                || is_null($analyse->parent_id)
                || ! in_array($analyse->parent_id, $attachedIds);
        });

        $analysesTree = [];
        foreach ($parents as $parent) {
            $node = $this->buildAnalyseNode($parent, $prescription, $resultatsMap, $patient);
            // We only need the parent progression info, not the children bodies for the sidebar
            unset($node['children']);
            $analysesTree[] = $node;
        }

        $canFinalize = collect($analysesTree)->every(fn ($a) => $a['status'] === 'TERMINE');
        $isReadyToFinalize = collect($analysesTree)->every(fn ($a) => $a['status'] === 'TERMINE' || $a['can_complete']);

        return response()->json([
            'success' => true,
            'analysesParents' => $analysesTree,
            'canFinalize' => $canFinalize,
            'isReadyToFinalize' => $isReadyToFinalize,
            'prescriptionStatus' => $prescription->status,
        ]);
    }
}
