<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Build the KPIs for the top section (Admin/Superadmin only)
     */
    public function getKpis()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Prescriptions count
        $prescriptionsToday = Prescription::whereDate('created_at', $today)->count();

        // Revenue calculations
        $revenueToday = Paiement::whereDate('created_at', $today)->sum('montant');
        $revenueThisMonth = Paiement::whereDate('created_at', '>=', $startOfMonth)->sum('montant');

        // We fetch prescriptions over the current month and calculate exactly to avoid SQL inconsistencies
        // and handle specific package pricing / promotions gracefully
        $monthlyPrescriptions = Prescription::with(['analyses.parent', 'tubes'])
            ->where('created_at', '>=', $startOfMonth)
            ->get();

        $totalExpectedMonth = $monthlyPrescriptions->sum(function ($p) {
            return $p->montant_total;
        });

        $totalPaidMonth = Paiement::where('created_at', '>=', $startOfMonth)->sum('montant') ?? 0;
        $unpaidAmount = max(0, $totalExpectedMonth - $totalPaidMonth);

        // Calculate a rough payment rate for the month
        $paymentRate = $totalExpectedMonth > 0 ? min(100, round(($totalPaidMonth / $totalExpectedMonth) * 100, 2)) : 0;

        // Pending analyses count
        $pendingAnalyses = DB::table('prescription_analyse as pa')
            ->join('prescriptions as p', 'pa.prescription_id', '=', 'p.id')
            ->whereIn('p.status', ['EN_ATTENTE', 'EN_COURS', 'A_REFAIRE'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('resultats as r')
                      ->whereColumn('r.prescription_id', 'pa.prescription_id')
                      ->whereColumn('r.analyse_id', 'pa.analyse_id')
                      ->whereNotNull('r.valeur');
            })
            ->count();

        return [
            'prescriptionsToday' => $prescriptionsToday,
            'revenueToday' => $revenueToday,
            'revenueThisMonth' => $revenueThisMonth,
            'unpaidAmount' => $unpaidAmount,
            'pendingAnalyses' => $pendingAnalyses,
            'paymentRate' => $paymentRate,
        ];
    }

    /**
     * Line chart: Revenue last 30 days
     */
    public function getRevenueLast30Days()
    {
        $last30Days = Carbon::now()->subDays(29)->startOfDay();

        $revenues = Paiement::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(montant) as total')
        )
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $data = [];

        // Fill empty days for continuous graph
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(29 - $i)->format('Y-m-d');
            $row = $revenues->firstWhere('date', $date);

            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $row ? (float) $row->total : 0;
        }

        return [
            'labels' => $labels,
            'series' => $data,
        ];
    }

    /**
     * Bar chart: Prescriptions per day (last 30 days)
     */
    public function getPrescriptionsLast30Days()
    {
        $last30Days = Carbon::now()->subDays(29)->startOfDay();

        $prescriptions = Prescription::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(id) as total')
        )
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $data = [];

        // Fill empty days
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(29 - $i)->format('Y-m-d');
            $row = $prescriptions->firstWhere('date', $date);

            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $row ? (int) $row->total : 0;
        }

        return [
            'labels' => $labels,
            'series' => $data,
        ];
    }

    /**
     * Pie chart: Top 5 analyses performed overall or this month
     */
    public function getTopAnalyses()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $top = DB::table('prescription_analyse as pa')
            ->join('analyses as a', 'pa.analyse_id', '=', 'a.id')
            ->join('prescriptions as p', 'pa.prescription_id', '=', 'p.id')
            ->where('p.created_at', '>=', $startOfMonth)
            ->select('a.designation', DB::raw('COUNT(pa.analyse_id) as count'))
            ->groupBy('a.designation')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'labels' => $top->pluck('designation'),
            'series' => $top->pluck('count'),
        ];
    }

    /**
     * Doughnut chart: Paid vs Unpaid ratio current month
     */
    public function getPaymentRatio()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $monthlyPrescriptions = Prescription::with(['analyses.parent', 'tubes'])
            ->where('created_at', '>=', $startOfMonth)
            ->get();
            
        $expectedQueryMonth = $monthlyPrescriptions->sum(function ($p) {
            return $p->montant_total;
        });

        $totalPaidMonth = Paiement::where('created_at', '>=', $startOfMonth)->sum('montant') ?? 0;
        $unpaidMonth = max(0, $expectedQueryMonth - $totalPaidMonth);

        return [
            'labels' => ['Payé', 'Reste à payer'],
            'series' => [(float)$totalPaidMonth, (float)$unpaidMonth],
        ];
    }

    /**
     * Compare this month vs previous month for growth
     */
    public function getMonthlyComparison()
    {
        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $revenueThisMonth = Paiement::where('created_at', '>=', $thisMonthStart)->sum('montant');
        $revenueLastMonth = Paiement::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('montant');

        $growth = 0;
        if ($revenueLastMonth > 0) {
            $growth = (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
        } elseif ($revenueThisMonth > 0) {
            $growth = 100; // From 0 to something
        }

        return [
            'revenueThisMonth' => $revenueThisMonth,
            'revenueLastMonth' => $revenueLastMonth,
            'growthPercentage' => round($growth, 2),
            'isPositive' => $growth >= 0,
        ];
    }
}
