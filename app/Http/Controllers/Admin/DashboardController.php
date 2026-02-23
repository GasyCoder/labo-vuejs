<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Resultat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Utilisateur non connecté');
        }

        $stats = [
            'patients' => $this->getPatientStats($user),
            'analyses' => $this->getAnalyseStats($user),
            'finances' => $this->getFinanceStats($user),
            'activites' => $this->getRecentActivities($user),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }

    private function getPatientStats($user)
    {
        if (! in_array($user->type, ['superadmin', 'secretaire'])) {
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
        if (! in_array($user->type, ['superadmin', 'biologiste', 'technicien'])) {
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
                $pathologiques = Resultat::where('interpretation', 'PATHOLOGIQUE')->count();
                $totalResultats = Resultat::count();
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
        if (! in_array($user->type, ['superadmin', 'secretaire'])) {
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

    private function getRecentActivities($user)
    {
        $activities = [];

        try {
            if (in_array($user->type, ['superadmin', 'secretaire'])) {
                $recentPatients = Patient::latest()
                    ->limit(3)
                    ->get();

                foreach ($recentPatients as $patient) {
                    $activities[] = [
                        'message' => "Nouveau patient : {$patient->prenom} {$patient->nom}",
                        'time' => $patient->created_at->diffForHumans(),
                        'color' => 'green',
                        'type' => 'patient',
                    ];
                }
            }

            if (in_array($user->type, ['superadmin', 'biologiste'])) {
                try {
                    $recentValidations = Resultat::with(['analyse', 'prescription.patient'])
                        ->where('status', 'VALIDE')
                        ->whereNotNull('validated_at')
                        ->latest('validated_at')
                        ->limit(2)
                        ->get();

                    foreach ($recentValidations as $resultat) {
                        $patientNom = $resultat->prescription->patient->nom ?? 'Patient inconnu';
                        $analyseNom = $resultat->analyse->designation ?? 'Analyse inconnue';

                        $activities[] = [
                            'message' => "Résultat validé : {$analyseNom} pour {$patientNom}",
                            'time' => $resultat->validated_at->diffForHumans(),
                            'color' => 'blue',
                            'type' => 'validation',
                        ];
                    }
                } catch (\Exception $e) {
                }
            }

            if (in_array($user->type, ['superadmin', 'secretaire'])) {
                try {
                    $recentPayments = Paiement::with('prescription.patient')
                        ->latest()
                        ->limit(2)
                        ->get();

                    foreach ($recentPayments as $paiement) {
                        $patientNom = $paiement->prescription->patient->nom ?? 'Patient inconnu';
                        $montant = number_format($paiement->montant, 0, ',', ' ');

                        $activities[] = [
                            'message' => "Paiement reçu : {$montant} Ar de {$patientNom}",
                            'time' => $paiement->created_at->diffForHumans(),
                            'color' => 'green',
                            'type' => 'paiement',
                        ];
                    }
                } catch (\Exception $e) {
                }
            }

            if (in_array($user->type, ['superadmin', 'technicien'])) {
                $prescriptionsEnAttente = Prescription::with('patient')
                    ->where('status', 'EN_ATTENTE')
                    ->latest()
                    ->limit(2)
                    ->get();

                foreach ($prescriptionsEnAttente as $prescription) {
                    $patientNom = $prescription->patient->nom ?? 'Patient inconnu';

                    $nbAnalyses = DB::table('prescription_analyse')
                        ->where('prescription_id', $prescription->id)
                        ->count();

                    $activities[] = [
                        'message' => "Prescription en attente : {$nbAnalyses} analyse(s) pour {$patientNom}",
                        'time' => $prescription->created_at->diffForHumans(),
                        'color' => 'yellow',
                        'type' => 'attente',
                    ];
                }
            }

            usort($activities, function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            });

            return array_slice($activities, 0, 8);

        } catch (\Exception $e) {
            return [];
        }
    }
}
