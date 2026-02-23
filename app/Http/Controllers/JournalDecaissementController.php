<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalDecaissementController extends Controller
{
    public function index(Request $request)
    {
        $dateDebut = $request->input('dateDebut', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('dateFin', Carbon::today()->format('Y-m-d'));

        $totalCommissions = $this->calculateTotalCommissions($dateDebut, $dateFin);
        $decaissements = $this->getDecaissements($dateDebut, $dateFin);

        $decaissementsData = $decaissements->map(function ($paiement) {
            return [
                'id' => $paiement->id,
                'date_paiement' => $paiement->date_paiement,
                'date_paiement_format' => $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y H:i') : 'N/A',
                'commission_prescripteur' => $paiement->commission_prescripteur,
                'prescription' => $paiement->prescription ? [
                    'id' => $paiement->prescription->id,
                    'prescripteur' => $paiement->prescription->prescripteur ? [
                        'nom_complet' => $paiement->prescription->prescripteur->nom_complet,
                        'nom' => $paiement->prescription->prescripteur->nom,
                    ] : null,
                    'patient' => $paiement->prescription->patient ? [
                        'numero_dossier' => $paiement->prescription->patient->numero_dossier,
                        'nom_complet' => $paiement->prescription->patient->nom_complet,
                    ] : null,
                ] : null,
            ];
        });

        return Inertia::render('JournalDecaissement', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'totalCommissions' => $totalCommissions,
            'decaissements' => $decaissementsData,
        ]);
    }

    private function calculateTotalCommissions($dateDebut, $dateFin)
    {
        return Paiement::payés()
            ->whereNotNull('date_paiement')
            ->whereBetween('date_paiement', [
                Carbon::parse($dateDebut)->startOfDay(),
                Carbon::parse($dateFin)->endOfDay(),
            ])
            ->sum('commission_prescripteur');
    }

    private function getDecaissements($dateDebut, $dateFin)
    {
        return Paiement::with(['prescription.prescripteur', 'prescription.patient', 'paymentMethod'])
            ->payés()
            ->whereNotNull('date_paiement')
            ->whereBetween('date_paiement', [
                Carbon::parse($dateDebut)->startOfDay(),
                Carbon::parse($dateFin)->endOfDay(),
            ])
            ->where('commission_prescripteur', '>', 0)
            ->orderBy('date_paiement', 'desc')
            ->get();
    }

    public function exportPdf(Request $request)
    {
        $dateDebut = $request->input('dateDebut', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('dateFin', Carbon::today()->format('Y-m-d'));

        $decaissements = $this->getDecaissements($dateDebut, $dateFin);
        $totalCommissions = $this->calculateTotalCommissions($dateDebut, $dateFin);

        $pdf = Pdf::loadView('factures.journal-decaissement', [
            'decaissements' => $decaissements,
            'totalCommissions' => $totalCommissions,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);

        $filename = 'journal-decaissements-'.$dateDebut.'-'.$dateFin.'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
