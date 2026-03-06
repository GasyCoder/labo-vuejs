<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PartnerRecapController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $partnersData = $this->dashboardService->getPartnerStats($dateFrom, $dateTo);

        // Calcul des totaux globaux pour les partenaires
        $globalTotals = [
            'total_amount' => $partnersData->sum('montant_total'),
            'total_paid' => $partnersData->sum('montant_paye'),
            'total_pending' => $partnersData->sum('reste_a_payer'),
            'total_prescriptions' => $partnersData->sum('nb_prescriptions'),
        ];

        return Inertia::render('Admin/PartnerRecap', [
            'partners' => $partnersData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'totals' => $globalTotals,
        ]);
    }

    public function export(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $stats = $this->dashboardService->getPartnerStats($dateFrom, $dateTo);

        $filename = 'recapitulatif-partenaires-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($stats) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['Partenaire', 'Date', 'Référence', 'Patient', 'Montant Total', 'Montant Payé', 'Solde'], ';');

            foreach ($stats as $partner) {
                foreach ($partner['details'] as $detail) {
                    fputcsv($file, [
                        $partner['nom_complet'],
                        $detail['date'],
                        $detail['reference'],
                        $detail['patient'],
                        $detail['montant'],
                        $detail['paye'],
                        $detail['montant'] - $detail['paye'],
                    ], ';');
                }
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
