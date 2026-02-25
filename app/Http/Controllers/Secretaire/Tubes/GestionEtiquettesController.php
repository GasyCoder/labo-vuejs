<?php

namespace App\Http\Controllers\Secretaire\Tubes;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Tube;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GestionEtiquettesController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->input('recherche', '');
        $filtreStatut = $request->input('filtreStatut', 'tous');
        $filtreDate = $request->input('filtreDate', 'aujourd_hui');
        $typeAffichage = $request->input('typeAffichage', 'tous');

        $dates = $this->getDatesFromFilter($filtreDate, $request->input('dateDebut'), $request->input('dateFin'));
        $dateDebut = $dates['debut'];
        $dateFin = $dates['fin'];

        $baseQuery = $this->getBaseQuery($recherche, $dateDebut, $dateFin, $typeAffichage, $filtreStatut);

        $prescriptions = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->paginate(100)
            ->withQueryString();

        // Check if tubes/analyses are present for UI logic (translated from Blade)
        $prescriptions->getCollection()->transform(function ($prescription) {
            $hasAnalyses = \DB::table('prescription_analyse')->where('prescription_id', $prescription->id)->exists();
            $analysesCount = \DB::table('prescription_analyse')->where('prescription_id', $prescription->id)->count();

            $tubesReceptionnes = $prescription->tubes->filter(fn ($tube) => $tube->estReceptionne())->count();
            $totalTubes = $prescription->tubes->count();

            $prescription->has_analyses = $hasAnalyses;
            $prescription->analyses_count = $analysesCount;
            $prescription->tubes_receptionnes_count = $tubesReceptionnes;
            $prescription->total_tubes_count = $totalTubes;

            return $prescription;
        });

        $statistiques = $this->calculerStatistiques($recherche, $dateDebut, $dateFin, $typeAffichage, $filtreStatut);

        return Inertia::render('Secretaire/Tubes/GestionEtiquettes', [
            'prescriptions' => $prescriptions,
            'statistiques' => $statistiques,
            'filters' => [
                'recherche' => $recherche,
                'filtreStatut' => $filtreStatut,
                'filtreDate' => $filtreDate,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'typeAffichage' => $typeAffichage,
            ],
        ]);
    }

    private function getDatesFromFilter($filtreDate, $customDebut = null, $customFin = null)
    {
        $dateDebut = today()->format('Y-m-d');
        $dateFin = today()->format('Y-m-d');

        switch ($filtreDate) {
            case 'hier':
                $dateDebut = Carbon::yesterday()->format('Y-m-d');
                $dateFin = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'cette_semaine':
                $dateDebut = now()->startOfWeek()->format('Y-m-d');
                $dateFin = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'ce_mois':
                $dateDebut = now()->startOfMonth()->format('Y-m-d');
                $dateFin = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'custom':
                $dateDebut = $customDebut ?: today()->format('Y-m-d');
                $dateFin = $customFin ?: today()->format('Y-m-d');
                break;
        }

        return ['debut' => $dateDebut, 'fin' => $dateFin];
    }

    private function getBaseQuery($recherche, $dateDebut, $dateFin, $typeAffichage, $filtreStatut)
    {
        $query = Prescription::with([
            'patient',
            'prescripteur',
            'tubes.prelevement',
            'tubes.prelevement.typeTubeRecommande',
        ]);

        // Appliquer filtres de base
        $query->when($recherche, function ($q) use ($recherche) {
            $recherche = trim($recherche);
            $q->where(function ($query) use ($recherche) {
                $query->where('prescriptions.reference', 'like', "%{$recherche}%")
                    ->orWhereHas('patient', function ($subQ) use ($recherche) {
                        $subQ->where('nom', 'like', "%{$recherche}%")
                            ->orWhere('prenom', 'like', "%{$recherche}%");
                    })
                    ->orWhereHas('prescripteur', function ($subQ) use ($recherche) {
                        $subQ->where('nom', 'like', "%{$recherche}%");
                    });
            });
        })
            ->when($dateDebut && $dateFin, function ($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('prescriptions.created_at', [
                    $dateDebut.' 00:00:00',
                    $dateFin.' 23:59:59',
                ]);
            });

        // Filtres spécifiques au type d'affichage
        if ($typeAffichage === 'avec_tubes') {
            $query->has('tubes');
        } elseif ($typeAffichage === 'sans_tubes') {
            $query->doesntHave('tubes');
        } elseif ($typeAffichage === 'avec_analyses') {
            $query->whereExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('prescription_analyse')
                    ->whereColumn('prescription_analyse.prescription_id', 'prescriptions.id');
            });
        } elseif ($typeAffichage === 'sans_analyses') {
            $query->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('prescription_analyse')
                    ->whereColumn('prescription_analyse.prescription_id', 'prescriptions.id');
            });
        }

        // Filtre statut
        if ($filtreStatut !== 'tous') {
            if ($filtreStatut === 'receptionnes') {
                $query->whereHas('tubes', function ($q) {
                    $q->whereNotNull('receptionne_par');
                });
            } elseif ($filtreStatut === 'non_receptionnes') {
                $query->whereHas('tubes', function ($q) {
                    $q->whereNull('receptionne_par');
                });
            }
        }

        return $query;
    }

    private function calculerStatistiques($recherche, $dateDebut, $dateFin, $typeAffichage, $filtreStatut)
    {
        try {
            $baseQuery = $this->getBaseQuery($recherche, $dateDebut, $dateFin, $typeAffichage, $filtreStatut);

            // Re-apply prescription filters specifically for tubes counts
            $applyFilters = function ($q) use ($recherche, $dateDebut, $dateFin) {
                $q->when($recherche, function ($query) use ($recherche) {
                    $recherche = trim($recherche);
                    $query->where(function ($q) use ($recherche) {
                        $q->where('prescriptions.reference', 'like', "%{$recherche}%")
                            ->orWhereHas('patient', function ($subQ) use ($recherche) {
                                $subQ->where('nom', 'like', "%{$recherche}%")
                                    ->orWhere('prenom', 'like', "%{$recherche}%");
                            })
                            ->orWhereHas('prescripteur', function ($subQ) use ($recherche) {
                                $subQ->where('nom', 'like', "%{$recherche}%");
                            });
                    });
                })->when($dateDebut && $dateFin, function ($query) use ($dateDebut, $dateFin) {
                    $query->whereBetween('prescriptions.created_at', [
                        $dateDebut.' 00:00:00',
                        $dateFin.' 23:59:59',
                    ]);
                });
            };

            return [
                'total_prescriptions' => (clone $baseQuery)->count(),
                'avec_tubes' => (clone $baseQuery)->has('tubes')->count(),
                'sans_tubes' => (clone $baseQuery)->doesntHave('tubes')->count(),
                'avec_analyses' => \DB::table('prescriptions')
                    ->join('prescription_analyse', 'prescriptions.id', '=', 'prescription_analyse.prescription_id')
                    ->whereBetween('prescriptions.created_at', [
                        $dateDebut.' 00:00:00',
                        $dateFin.' 23:59:59',
                    ])
                    ->distinct('prescriptions.id')
                    ->count('prescriptions.id'),
                'tubes_receptionnes' => Tube::whereHas('prescription', $applyFilters)->whereNotNull('receptionne_par')->count(),
                'tubes_non_receptionnes' => Tube::whereHas('prescription', $applyFilters)->whereNull('receptionne_par')->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur calcul statistiques étiquettes', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return [
                'total_prescriptions' => 0,
                'avec_tubes' => 0,
                'sans_tubes' => 0,
                'avec_analyses' => 0,
                'tubes_receptionnes' => 0,
                'tubes_non_receptionnes' => 0,
            ];
        }
    }

    public function marquerReceptionne(Request $request, $id)
    {
        try {
            $prescription = Prescription::with('tubes')->findOrFail($id);

            if ($prescription->tubes->isEmpty()) {
                return redirect()->back()->with('success', 'Cette prescription n\'a pas de tubes');
            }

            $tubesMarques = 0;
            foreach ($prescription->tubes as $tube) {
                if (! $tube->estReceptionne()) {
                    $tube->marquerReceptionne(Auth::id());
                    $tubesMarques++;
                }
            }

            if ($tubesMarques > 0) {
                return redirect()->back()->with('success', "{$tubesMarques} tube(s) marqué(s) comme réceptionné(s)");
            } else {
                return redirect()->back()->with('success', 'Tous les tubes sont déjà réceptionnés');
            }
        } catch (\Exception $e) {
            Log::error('Erreur marquage réception tubes prescription', [
                'prescription_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Erreur lors du marquage: '.$e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        $prescriptionsIds = $request->input('prescriptions', []);
        $inclurePatient = $request->boolean('inclurePatient', true);

        if (empty($prescriptionsIds)) {
            return redirect()->back()->with('error', 'Veuillez sélectionner au moins une prescription');
        }

        try {
            $prescriptions = Prescription::with([
                'patient',
                'prescripteur',
                'tubes.prelevement',
                'tubes.prelevement.typeTubeRecommande',
            ])
                ->whereIn('id', $prescriptionsIds)
                ->orderBy('created_at')
                ->get();

            // Charger les analyses séparément
            foreach ($prescriptions as $prescription) {
                $prescription->analyses_data = \DB::table('prescription_analyse')
                    ->join('analyses', 'prescription_analyse.analyse_id', '=', 'analyses.id')
                    ->where('prescription_analyse.prescription_id', $prescription->id)
                    ->select('analyses.designation', 'analyses.code')
                    ->get();
            }

            if ($prescriptions->isEmpty()) {
                return redirect()->back()->with('error', 'Aucune prescription trouvée pour l\'impression');
            }

            // Calcul des statistiques pour génération
            $nombrePrescriptions = $prescriptions->count();

            $nombreTubes = $prescriptions->sum(function ($prescription) {
                return $prescription->tubes->count();
            });

            $prescriptionsSanstubes = $prescriptions->filter(function ($prescription) {
                return $prescription->tubes->isEmpty();
            })->count();

            $prescriptionsAvecAnalyses = $prescriptions->filter(function ($prescription) {
                $hasAnalyses = \DB::table('prescription_analyse')
                    ->where('prescription_id', $prescription->id)
                    ->exists();

                return $prescription->tubes->isEmpty() && $hasAnalyses;
            })->count();

            $prescriptionsVides = $prescriptions->filter(function ($prescription) {
                $hasAnalyses = \DB::table('prescription_analyse')
                    ->where('prescription_id', $prescription->id)
                    ->exists();

                return $prescription->tubes->isEmpty() && ! $hasAnalyses;
            })->count();

            $nombreEtiquettesTotales = ($nombreTubes * 5) + ($prescriptionsSanstubes * 5);

            $pdf = Pdf::loadView('factures.etiquettes-prescriptions', [
                'prescriptions' => $prescriptions,
                'inclurePatient' => $inclurePatient,
                'titre' => 'Étiquettes Prescriptions - '.now()->format('d/m/Y H:i'),
                'laboratoire' => config('app.name', 'Laboratoire CTB'),
                'statistiques' => [
                    'nombre_prescriptions' => $nombrePrescriptions,
                    'nombre_tubes' => $nombreTubes,
                    'prescriptions_sans_tubes' => $prescriptionsSanstubes,
                    'prescriptions_avec_analyses' => $prescriptionsAvecAnalyses,
                    'prescriptions_vides' => $prescriptionsVides,
                    'nombre_etiquettes_totales' => $nombreEtiquettesTotales,
                ],
            ])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi' => 300,
                    'defaultFont' => 'Arial',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'chroot' => public_path(),
                    'debugKeepTemp' => false,
                ]);

            Log::info('Génération étiquettes prescriptions PDF format horizontal (Inertia)', [
                'user_id' => Auth::id(),
                'prescriptions_count' => $nombrePrescriptions,
                'tubes_count' => $nombreTubes,
                'sans_tubes_count' => $prescriptionsSanstubes,
                'avec_analyses_count' => $prescriptionsAvecAnalyses,
                'vides_count' => $prescriptionsVides,
                'etiquettes_totales' => $nombreEtiquettesTotales,
            ]);

            $filename = 'etiquettes-prescriptions-'.now()->format('Y-m-d-H-i').'.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, ['Content-Type' => 'application/pdf']);

        } catch (\Exception $e) {
            Log::error('Erreur génération PDF étiquettes prescriptions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'prescriptions' => $prescriptionsIds,
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la génération: '.$e->getMessage());
        }
    }
}
