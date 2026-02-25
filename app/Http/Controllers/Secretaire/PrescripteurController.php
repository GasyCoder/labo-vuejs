<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Prescripteur;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PrescripteurController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $statutFilter = $request->input('statutFilter', '');
        $sortField = $request->input('sortField', 'nom');
        $sortDirection = $request->input('sortDirection', 'asc');
        $perPage = $request->input('perPage', 10);

        $prescripteurs = Prescripteur::query()
            ->withCount([
                'prescriptions as total_prescriptions',
                'prescriptions as prescriptions_commissionnables' => function ($q) {
                    $q->whereHas('paiements');
                },
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', '%'.$search.'%')
                        ->orWhere('prenom', 'like', '%'.$search.'%')
                        ->orWhere('grade', 'like', '%'.$search.'%')
                        ->orWhere('telephone', 'like', '%'.$search.'%');
                });
            })
            ->when($statutFilter, function ($query) use ($statutFilter) {
                if ($statutFilter === 'actif') {
                    $query->where('is_active', true);
                } elseif ($statutFilter === 'inactif') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        $statistiques = $this->calculateGlobalStatistics();

        // Inject computed data directly without modifying the model files permanently if needed
        // Or we can just use mapping
        $prescripteurs->getCollection()->transform(function ($prescripteur) {
            return [
                'id' => $prescripteur->id,
                'nom' => $prescripteur->nom,
                'prenom' => $prescripteur->prenom,
                'nom_complet' => $prescripteur->nom_complet,
                'grade' => $prescripteur->grade,
                'status' => $prescripteur->status,
                'telephone' => $prescripteur->telephone,
                'notes' => $prescripteur->notes,
                'is_active' => $prescripteur->is_active,
                'commission_quota' => $prescripteur->commission_quota,
                'commission_pourcentage' => $prescripteur->commission_pourcentage,
                'total_prescriptions' => $prescripteur->total_prescriptions,
                'prescriptions_commissionnables' => $prescripteur->prescriptions_commissionnables,
                'brute_mensuel' => $prescripteur->getBruteAnalysesMensuel(),
            ];
        });

        $grades = Prescripteur::getGradesDisponibles();
        $statusOptions = Prescripteur::getStatusDisponibles();

        return Inertia::render('Secretaire/Prescripteurs', array_merge([
            'prescripteurs' => $prescripteurs,
            'grades' => $grades,
            'statusOptions' => $statusOptions,
            'filters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'sortField' => $sortField,
                'sortDirection' => $sortDirection,
                'perPage' => $perPage,
            ],
            'defaultCommissionQuota' => Setting::getCommissionQuota(),
            'defaultCommissionPourcentage' => Setting::getCommissionPourcentage(),
        ], $statistiques));
    }

    private function calculateGlobalStatistics()
    {
        try {
            $totalPrescripteurs = Prescripteur::count();
            $prescripteursActifs = Prescripteur::where('is_active', true)->count();

            $result = DB::table('paiements')
                ->join('prescriptions', 'paiements.prescription_id', '=', 'prescriptions.id')
                ->join('prescripteurs', 'prescriptions.prescripteur_id', '=', 'prescripteurs.id')
                ->whereNull('paiements.deleted_at')
                ->selectRaw('
                    COUNT(*) as total_paiements,
                    SUM(paiements.commission_prescripteur) as total_commissions
                ')
                ->first();

            $totalCommissions = $result->total_commissions ?? 0;
            $totalPaiements = $result->total_paiements ?? 0;

            return [
                'totalPrescripteurs' => $totalPrescripteurs,
                'prescripteursActifs' => $prescripteursActifs,
                'totalCommissions' => $totalCommissions,
                'totalPrescriptionsCommissionnables' => $totalPaiements,
            ];
        } catch (\Exception $e) {
            return [
                'totalPrescripteurs' => Prescripteur::count(),
                'prescripteursActifs' => Prescripteur::where('is_active', true)->count(),
                'totalCommissions' => 0,
                'totalPrescriptionsCommissionnables' => 0,
            ];
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|min:2|max:100',
            'prenom' => 'nullable|max:100',
            'grade' => 'nullable|max:20',
            'status' => 'required|in:Medecin,Professeur',
            'telephone' => 'nullable|max:20',
            'notes' => 'nullable|max:1000',
            'is_active' => 'boolean',
            'commission_quota' => 'required|numeric|min:0',
            'commission_pourcentage' => 'required|numeric|min:0|max:100',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.min' => 'Le nom doit contenir au moins 2 caractères.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
        ]);

        Prescripteur::create($validated);

        return redirect()->back()->with('success', 'Prescripteur créé avec succès.');
    }

    public function update(Request $request, Prescripteur $prescripteur)
    {
        $validated = $request->validate([
            'nom' => 'required|min:2|max:100',
            'prenom' => 'nullable|max:100',
            'grade' => 'nullable|max:20',
            'status' => 'required|in:Medecin,Professeur',
            'telephone' => 'nullable|max:20',
            'notes' => 'nullable|max:1000',
            'is_active' => 'boolean',
            'commission_quota' => 'required|numeric|min:0',
            'commission_pourcentage' => 'required|numeric|min:0|max:100',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.min' => 'Le nom doit contenir au moins 2 caractères.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
        ]);

        $prescripteur->update($validated);

        return redirect()->back()->with('success', 'Prescripteur modifié avec succès.');
    }

    public function destroy(Prescripteur $prescripteur)
    {
        try {
            $prescripteurNom = $prescripteur->nom_complet;
            $prescriptionsCount = $prescripteur->prescriptions()->count();

            $prescripteur->delete(); // Soft delete

            $message = $prescriptionsCount > 0
                ? "Prescripteur « {$prescripteurNom} » déplacé vers la corbeille ({$prescriptionsCount} prescription(s) associée(s))."
                : "Prescripteur « {$prescripteurNom} » déplacé vers la corbeille.";

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du prescripteur', [
                'prescripteur_id' => $prescripteur->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Impossible de supprimer ce prescripteur : '.$e->getMessage());
        }
    }

    public function toggleStatus(Prescripteur $prescripteur)
    {
        $prescripteur->update(['is_active' => ! $prescripteur->is_active]);

        $statusMessage = $prescripteur->is_active ? 'activé' : 'désactivé';

        return redirect()->back()->with('success', "Prescripteur {$statusMessage} avec succès.");
    }

    public function export(Request $request)
    {
        $search = $request->input('search', '');
        $statutFilter = $request->input('statutFilter', '');
        $sortField = $request->input('sortField', 'nom');
        $sortDirection = $request->input('sortDirection', 'asc');

        $prescripteurs = Prescripteur::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', '%'.$search.'%')
                        ->orWhere('prenom', 'like', '%'.$search.'%')
                        ->orWhere('grade', 'like', '%'.$search.'%')
                        ->orWhere('telephone', 'like', '%'.$search.'%');
                });
            })
            ->when($statutFilter, function ($query) use ($statutFilter) {
                if ($statutFilter === 'actif') {
                    $query->where('is_active', true);
                } elseif ($statutFilter === 'inactif') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy($sortField, $sortDirection)
            ->get();

        $filename = 'prescripteurs-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($prescripteurs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['ID', 'Nom', 'Prénom', 'Grade', 'Statut', 'Téléphone', 'Actif', 'Quota', 'Pourcentage %', 'Notes'], ';');

            foreach ($prescripteurs as $p) {
                fputcsv($file, [
                    $p->id,
                    $p->nom,
                    $p->prenom,
                    $p->grade,
                    $p->status,
                    $p->telephone,
                    $p->is_active ? 'Oui' : 'Non',
                    $p->commission_quota,
                    $p->commission_pourcentage,
                    $p->notes,
                ], ';');
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function getCommissions(Request $request, Prescripteur $prescripteur)
    {
        $dateDebut = $request->input('dateDebut', now()->startOfYear()->format('Y-m-d'));
        $dateFin = $request->input('dateFin', now()->format('Y-m-d'));

        try {
            $statistiques = $prescripteur->getStatistiquesCommissions($dateDebut, $dateFin);
            $commissions = $prescripteur->getCommissionsParMois(null, $dateDebut, $dateFin);

            return response()->json([
                'data' => $commissions,
                'total_commission' => $statistiques['total_commission'] ?? 0,
                'total_prescriptions' => $statistiques['total_prescriptions'] ?? 0,
                'montant_total_analyses' => $statistiques['montant_total_analyses'] ?? 0,
                'montant_total_paye' => $statistiques['montant_total_paye'] ?? 0,
                'commission_moyenne' => $statistiques['commission_moyenne'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du chargement des commissions'], 500);
        }
    }

    public function generateCommissionPDF(Request $request, Prescripteur $prescripteur)
    {
        try {
            $dateDebut = $request->input('dateDebut');
            $dateFin = $request->input('dateFin');

            $template = 'pdf.autre.commission-facture';

            if (! view()->exists($template)) {
                return redirect()->back()->with('error', "Le template PDF '$template' n'existe pas.");
            }

            $statistiques = $prescripteur->getStatistiquesCommissions($dateDebut, $dateFin);

            if (($statistiques['total_commission'] ?? 0) == 0) {
                return redirect()->back()->with('error', 'Aucune commission à facturer pour cette période.');
            }

            $commissions = $prescripteur->getCommissionsParMois(null, $dateDebut, $dateFin);

            $commissionDetails = [
                'data' => $commissions,
                ...$statistiques,
            ];

            $data = [
                'prescripteur' => $prescripteur,
                'commissionDetails' => $commissionDetails,
                'commissionPourcentage' => $prescripteur->commission_pourcentage,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'dateEmission' => now()->format('d/m/Y'),
            ];

            $pdf = Pdf::loadView($template, $data);
            $nomFichier = 'facture_commissions_'.\Illuminate\Support\Str::slug($prescripteur->nom_complet).'_'.now()->format('Ymd').'.pdf';

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $nomFichier,
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF: '.$e->getMessage());

            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF : '.$e->getMessage());
        }
    }
}
