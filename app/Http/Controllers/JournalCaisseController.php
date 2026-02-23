<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalCaisseController extends Controller
{
    public function index(Request $request)
    {
        $dateDebut = $request->input('dateDebut', Carbon::today()->subDays(7)->format('Y-m-d'));
        $dateFin = $request->input('dateFin', Carbon::today()->format('Y-m-d'));

        $totalGeneral = Paiement::payés()
            ->whereNotNull('date_paiement')
            ->sum('montant');

        $paiements = $this->getPaiements($dateDebut, $dateFin);
        $totauxParMethode = $this->getTotauxParMethode($paiements);
        $totalSemaineStats = $this->getTotalSemaine($dateDebut, $dateFin);

        $paiementsData = $paiements->map(function ($paiement) {
            return [
                'id' => $paiement->id,
                'montant' => $paiement->montant,
                'date_paiement' => $paiement->date_paiement,
                'date_paiement_format' => $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A',
                'heure_paiement_format' => $paiement->date_paiement ? $paiement->date_paiement->format('H:i') : null,
                'payment_method_label' => $paiement->paymentMethod ? $paiement->paymentMethod->label : 'NON DÉFINI',
                'prescription' => $paiement->prescription ? [
                    'id' => $paiement->prescription->id,
                    'is_modified' => $this->isPrescriptionModified($paiement->prescription),
                    'updated_at_format' => $paiement->prescription->updated_at->format('d/m/Y H:i:s'),
                    'patient' => $paiement->prescription->patient ? [
                        'numero_dossier' => $paiement->prescription->patient->numero_dossier,
                        'nom' => $paiement->prescription->patient->nom,
                        'prenom' => $paiement->prescription->patient->prenom,
                    ] : null,
                ] : null,
            ];
        });

        // Group by payment method label
        $paiementsGroupes = $paiementsData->groupBy('payment_method_label');

        return Inertia::render('JournalCaisse', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'totalGeneral' => $totalGeneral,
            'paiementsGroupes' => $paiementsGroupes,
            'totauxParMethode' => $totauxParMethode,
            'totalSemaine' => $totalSemaineStats['total'],
            'evolutionSemaine' => $totalSemaineStats['evolution'],
            'totalCount' => $paiements->count(),
            'totalMontant' => $paiements->sum('montant'),
        ]);
    }

    private function getPaiements($dateDebut, $dateFin)
    {
        return Paiement::with([
            'prescription.patient',
            'prescription',
            'paymentMethod',
            'utilisateur',
        ])
            ->whereBetween('date_paiement', [
                Carbon::parse($dateDebut)->startOfDay(),
                Carbon::parse($dateFin)->endOfDay(),
            ])
            ->payés()
            ->whereNotNull('date_paiement')
            ->orderBy('date_paiement')
            ->orderBy('payment_method_id')
            ->get();
    }

    private function getTotalSemaine($dateDebut, $dateFin)
    {
        $debutPeriode = Carbon::parse($dateDebut)->startOfDay();
        $finPeriode = Carbon::parse($dateFin)->endOfDay();

        $debutSemainePrecedente = $debutPeriode->copy()->subWeek()->startOfDay();
        $finSemainePrecedente = $finPeriode->copy()->subWeek()->endOfDay();

        $totalSemaine = Paiement::payés()
            ->whereNotNull('date_paiement')
            ->whereBetween('date_paiement', [$debutPeriode, $finPeriode])
            ->sum('montant');

        $totalSemainePrecedente = Paiement::payés()
            ->whereNotNull('date_paiement')
            ->whereBetween('date_paiement', [$debutSemainePrecedente, $finSemainePrecedente])
            ->sum('montant');

        $evolution = $totalSemainePrecedente > 0
            ? (($totalSemaine - $totalSemainePrecedente) / $totalSemainePrecedente) * 100
            : 0;

        return [
            'total' => $totalSemaine,
            'evolution' => $evolution,
        ];
    }

    private function getTotauxParMethode($paiements)
    {
        return $paiements->groupBy('paymentMethod.label')->map(function ($group) {
            return [
                'total' => $group->sum('montant'),
                'count' => $group->count(),
                'label' => $group->first()->paymentMethod ? $group->first()->paymentMethod->label : 'NON DÉFINI',
            ];
        })->values();
    }

    private function isPrescriptionModified($prescription)
    {
        if (! $prescription) {
            return false;
        }

        return $prescription->created_at->ne($prescription->updated_at);
    }

    public function exportPdf(Request $request)
    {
        $dateDebut = $request->input('dateDebut', Carbon::today()->subDays(7)->format('Y-m-d'));
        $dateFin = $request->input('dateFin', Carbon::today()->format('Y-m-d'));

        $totalGeneral = Paiement::payés()
            ->whereNotNull('date_paiement')
            ->sum('montant');

        $paiements = $this->getPaiements($dateDebut, $dateFin);
        $totauxParMethode = $this->getTotauxParMethode($paiements)->keyBy('label');
        $totalSemaine = $this->getTotalSemaine($dateDebut, $dateFin);

        $pdf = Pdf::loadView('factures.journal-caisse', [
            'paiements' => $paiements,
            'totauxParMethode' => $totauxParMethode,
            'totalGeneral' => $totalGeneral,
            'totalSemaine' => $totalSemaine['total'],
            'evolutionSemaine' => $totalSemaine['evolution'],
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);

        $filename = 'journal-caisse-'.$dateDebut.'-'.$dateFin.'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
