<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Resultat;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Utilisateur non connecté');
        }

        if (! $user->hasPermission('dashboard.voir')) {
            // Si pas de permission dashboard, on redirige vers l'espace de travail
            if ($user->hasRole('biologiste')) {
                return redirect()->route('biologiste.analyse.index');
            }
            if ($user->hasRole('technicien')) {
                return redirect()->route('technicien.index');
            }
            if ($user->hasRole('secretaire')) {
                return redirect()->route('secretaire.prescription.index');
            }

            return redirect()->route('login');
        }

        // 1. Données de base communes
        $stats = [
            'patients' => $this->getPatientStats($user),
            'analyses' => $this->getAnalyseStats($user),
            'finances' => $this->getFinanceStats($user),
            'activites' => $this->getRecentActivities($user, $request),
        ];

        // 2. Données métier spécifiques
        $roleData = [];
        if ($user->hasRole('secretaire')) {
            $roleData = [
                'prescriptions_a_encaisser' => Prescription::whereDoesntHave('paiements')->where('status', '!=', 'ARCHIVE')->count(),
                'patients_jour' => Patient::whereDate('created_at', Carbon::today())->count(),
                'dernieres_prescriptions' => Prescription::with(['patient', 'paiements'])->latest()->limit(5)->get()->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'reference' => $p->reference,
                        'patient' => $p->patient,
                        'is_paid' => $p->est_payee,
                        'montant_total' => $p->montant_total,
                    ];
                }),
                'payment_methods_chart' => $this->dashboardService->getPaymentMethodsStats(),
                'revenue_trend' => $this->dashboardService->getRevenueLast30Days(), // Re-use existing service method
            ];
        } elseif ($user->hasRole('technicien')) {
            $roleData = [
                'analyses_urgentes' => Prescription::whereIn('patient_type', ['URGENCE-NUIT', 'URGENCE-JOUR'])->whereIn('status', ['EN_ATTENTE', 'EN_COURS'])->count(),
                'analyses_a_faire' => Prescription::where('status', 'EN_ATTENTE')->count(),
                'examens_par_type' => DB::table('prescription_analyse')
                    ->join('analyses', 'prescription_analyse.analyse_id', '=', 'analyses.id')
                    ->join('types', 'analyses.type_id', '=', 'types.id')
                    ->select('types.libelle as nom', DB::raw('count(*) as total'))
                    ->groupBy('types.libelle')
                    ->get(),
                'completion_trend' => $this->dashboardService->getAnalysisCompletionTrend(),
            ];
        } elseif ($user->hasRole('biologiste')) {
            $roleData = [
                'a_valider' => Prescription::where('status', 'TERMINE')->count(),
                'pathologiques_recente' => Resultat::where('interpretation', 'PATHOLOGIQUE')
                    ->where('status', '!=', 'VALIDE')
                    ->with(['analyse', 'prescription.patient'])
                    ->latest()
                    ->limit(5)
                    ->get(),
                'pathology_ratio' => $this->dashboardService->getPathologyRatio(),
            ];
        }

        // 3. Données stratégiques (Admin ou permission spéciale)
        $strategicData = [];
        if ($user->type === 'superadmin' || $user->type === 'admin') {
            $strategicData = [
                'kpis' => $this->dashboardService->getKpis(),
                'revenueLast30Days' => $this->dashboardService->getRevenueLast30Days(),
                'prescriptionsLast30Days' => $this->dashboardService->getPrescriptionsLast30Days(),
                'topAnalyses' => $this->dashboardService->getTopAnalyses(),
                'paymentRatio' => $this->dashboardService->getPaymentRatio(),
                'monthlyComparison' => $this->dashboardService->getMonthlyComparison(),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'roleData' => $roleData,
            'strategicData' => $strategicData,
            'filters' => $request->only(['search', 'date_from', 'date_to']),
        ]);
    }

    private function getPatientStats($user)
    {
        if (! $user->hasPermission('dashboard.voir') && ! in_array($user->type, ['superadmin', 'admin', 'secretaire'])) {
            return [];
        }

        return [
            'total' => Patient::count(),
            'nouveaux' => Patient::where('statut', 'NOUVEAU')->count(),
            'fideles' => Patient::where('statut', 'FIDELE')->count(),
            'vip' => Patient::where('statut', 'VIP')->count(),
            'actifs_30j' => Patient::whereHas('prescriptions', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            })->count(),
        ];
    }

    private function getAnalyseStats($user)
    {
        if (! $user->hasPermission('dashboard.voir') && ! in_array($user->type, ['superadmin', 'admin', 'biologiste', 'technicien'])) {
            return [];
        }

        $prescriptionStats = [
            'en_attente' => Prescription::where('status', 'EN_ATTENTE')->count(),
            'en_cours' => Prescription::where('status', 'EN_COURS')->count(),
            'terminees' => Prescription::where('status', 'TERMINE')->count(),
            'valides' => Prescription::where('status', 'VALIDE')->count(),
            'a_refaire' => Prescription::where('status', 'A_REFAIRE')->count(),
        ];

        try {
            $totalAnalyses = DB::table('prescription_analyse')->count();
        } catch (\Exception $e) {
            $totalAnalyses = 0;
        }

        $pathologiques = 0;
        $totalResultats = 0;

        try {
            if (class_exists('App\Models\Resultat')) {
                $pathologiques = \App\Models\Resultat::where('interpretation', 'PATHOLOGIQUE')->count();
                $totalResultats = \App\Models\Resultat::count();
            }
        } catch (\Exception $e) {
        }

        return array_merge($prescriptionStats, [
            'pathologiques' => $pathologiques,
            'total_resultats' => $totalResultats,
            'total_analyses' => $totalAnalyses,
        ]);
    }

    private function getFinanceStats($user)
    {
        if (! $user->hasPermission('dashboard.voir') && ! in_array($user->type, ['superadmin', 'admin', 'secretaire'])) {
            return [];
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        try {
            return [
                'recettes_jour' => Paiement::whereDate('created_at', $today)->sum('montant') ?? 0,
                'recettes_mois' => Paiement::whereDate('created_at', '>=', $startOfMonth)->sum('montant') ?? 0,
                'nb_paiements' => Paiement::whereDate('created_at', $today)->count() ?? 0,
                'moyenne_paiement' => Paiement::whereDate('created_at', $today)->avg('montant') ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'recettes_jour' => 0,
                'recettes_mois' => 0,
                'nb_paiements' => 0,
                'moyenne_paiement' => 0,
            ];
        }
    }

    private function getRecentActivities($user, Request $request)
    {
        $activities = [];
        $isAdmin = in_array($user->type, ['superadmin', 'admin']);
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $limit = ($search || $dateFrom || $dateTo) ? 50 : 10;

        try {
            // --- 1. PATIENTS ---
            if ($isAdmin || $user->type === 'secretaire') {
                $recentPatients = Patient::latest()
                    ->when(! $isAdmin, function ($query) use ($user) {
                        return $query->whereHas('prescriptions', function ($q) use ($user) {
                            $q->where('secretaire_id', $user->id);
                        });
                    })
                    ->when($search, function ($q) use ($search) {
                        $q->where(function ($sq) use ($search) {
                            $sq->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenom', 'like', "%{$search}%");
                        });
                    })
                    ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                    ->limit($limit)
                    ->get();

                foreach ($recentPatients as $patient) {
                    $activities[] = [
                        'message' => 'Nouveau patient : '.$patient->prenom.' '.$patient->nom,
                        'author' => $isAdmin ? ($patient->prescriptions->first()?->secretaire?->name ?? 'Système') : null,
                        'time' => $patient->created_at->diffForHumans(),
                        'color' => 'green',
                        'type' => 'patient',
                        'timestamp' => $patient->created_at->timestamp,
                    ];
                }
            }

            // --- 2. VALIDATIONS (BIOLOGISTE) ---
            if ($isAdmin || $user->type === 'biologiste') {
                try {
                    $recentValidations = \App\Models\Resultat::with(['analyse', 'prescription.patient', 'validatedBy'])
                        ->where('status', 'VALIDE')
                        ->whereNotNull('validated_at')
                        ->when(! $isAdmin, function ($query) use ($user) {
                            return $query->where('validated_by', $user->id);
                        })
                        ->when($search, function ($q) use ($search) {
                            $q->whereHas('prescription.patient', function ($sq) use ($search) {
                                $sq->where('nom', 'like', "%{$search}%")
                                    ->orWhere('prenom', 'like', "%{$search}%");
                            })->orWhereHas('analyse', fn ($sq) => $sq->where('designation', 'like', "%{$search}%"));
                        })
                        ->when($dateFrom, fn ($q) => $q->whereDate('validated_at', '>=', $dateFrom))
                        ->when($dateTo, fn ($q) => $q->whereDate('validated_at', '<=', $dateTo))
                        ->latest('validated_at')
                        ->limit($limit)
                        ->get();

                    foreach ($recentValidations as $resultat) {
                        $patientNom = $resultat->prescription->patient->nom ?? 'Patient inconnu';
                        $analyseNom = $resultat->analyse->designation ?? 'Analyse inconnue';

                        $activities[] = [
                            'message' => "Résultat validé : {$analyseNom} pour {$patientNom}",
                            'author' => $isAdmin ? ($resultat->validatedBy?->name ?? 'Inconnu') : null,
                            'time' => $resultat->validated_at->diffForHumans(),
                            'color' => 'blue',
                            'type' => 'validation',
                            'timestamp' => $resultat->validated_at->timestamp,
                        ];
                    }
                } catch (\Exception $e) {
                }
            }

            // --- 3. PAIEMENTS (SECRETAIRE) ---
            if ($isAdmin || $user->type === 'secretaire') {
                try {
                    $recentPayments = Paiement::with(['prescription.patient', 'utilisateur'])
                        ->when(! $isAdmin, function ($query) use ($user) {
                            return $query->where('recu_par', $user->id);
                        })
                        ->when($search, function ($q) use ($search) {
                            $q->whereHas('prescription.patient', function ($sq) use ($search) {
                                $sq->where('nom', 'like', "%{$search}%")
                                    ->orWhere('prenom', 'like', "%{$search}%");
                            });
                        })
                        ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                        ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                        ->latest()
                        ->limit($limit)
                        ->get();

                    foreach ($recentPayments as $paiement) {
                        $patientNom = $paiement->prescription->patient->nom ?? 'Patient inconnu';
                        $montant = number_format($paiement->montant, 0, ',', ' ');

                        $activities[] = [
                            'message' => "Paiement reçu : {$montant} Ar de {$patientNom}",
                            'author' => $isAdmin ? ($paiement->utilisateur?->name ?? 'Inconnu') : null,
                            'time' => $paiement->created_at->diffForHumans(),
                            'color' => 'green',
                            'type' => 'paiement',
                            'timestamp' => $paiement->created_at->timestamp,
                        ];
                    }
                } catch (\Exception $e) {
                }
            }

            // --- 4. TRAVAIL TECHNIQUE (TECHNICIEN) ---
            if ($isAdmin || $user->type === 'technicien') {
                $recentTasks = Prescription::with(['patient', 'technicien'])
                    ->when(! $isAdmin, function ($query) use ($user) {
                        return $query->where('technicien_id', $user->id);
                    })
                    ->whereIn('status', ['EN_COURS', 'TERMINE', 'VALIDE'])
                    ->when($search, function ($q) use ($search) {
                        $q->whereHas('patient', function ($sq) use ($search) {
                            $sq->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenom', 'like', "%{$search}%");
                        })->orWhere('reference', 'like', "%{$search}%");
                    })
                    ->when($dateFrom, fn ($q) => $q->whereDate('updated_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('updated_at', '<=', $dateTo))
                    ->latest('updated_at')
                    ->limit($limit)
                    ->get();

                foreach ($recentTasks as $prescription) {
                    $patientNom = $prescription->patient->nom ?? 'Patient inconnu';
                    $statusAction = $prescription->status === 'EN_COURS' ? 'Prise en charge' : 'Analyse terminée';

                    $activities[] = [
                        'message' => "{$statusAction} : {$patientNom}",
                        'author' => $isAdmin ? ($prescription->technicien?->name ?? 'Inconnu') : null,
                        'time' => $prescription->updated_at->diffForHumans(),
                        'color' => $prescription->status === 'EN_COURS' ? 'yellow' : 'indigo',
                        'type' => 'attente',
                        'timestamp' => $prescription->updated_at->timestamp,
                    ];
                }
            }

            // Tri final par timestamp décroissant
            usort($activities, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            return array_slice($activities, 0, $limit);

        } catch (\Exception $e) {
            return [];
        }
    }
}
