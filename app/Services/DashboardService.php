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
    public function getKpis($startDate = null, $endDate = null)
    {
        $today = Carbon::today();
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        // Prescriptions count (for selected period)
        $prescriptionsCount = Prescription::whereBetween('created_at', [$startDate, $endDate])->count();
        $prescriptionsToday = Prescription::whereDate('created_at', $today)->count();

        // Revenue calculations
        $revenueToday = Paiement::where('status', true)->whereDate('date_paiement', $today)->sum('montant') ?? 0;
        $revenuePeriod = Paiement::where('status', true)->whereBetween('date_paiement', [$startDate, $endDate])->sum('montant') ?? 0;

        // We fetch prescriptions over the period and calculate exactly to avoid SQL inconsistencies
        // and handle specific package pricing / promotions gracefully
        $periodPrescriptions = Prescription::with(['analyses.parent', 'tubes'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalExpectedPeriod = $periodPrescriptions->sum(function ($p) {
            return $p->montant_total;
        });

        $totalPaidPeriod = Paiement::where('status', true)->whereBetween('date_paiement', [$startDate, $endDate])->sum('montant') ?? 0;
        $unpaidAmount = max(0, $totalExpectedPeriod - $totalPaidPeriod);

        // Calculate a rough payment rate for the period
        $paymentRate = $totalExpectedPeriod > 0 ? min(100, round(($totalPaidPeriod / $totalExpectedPeriod) * 100, 2)) : 0;

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
            'prescriptionsPeriod' => $prescriptionsCount,
            'revenueToday' => $revenueToday,
            'revenueThisMonth' => $revenuePeriod, // Kept key name for frontend compatibility
            'unpaidAmount' => $unpaidAmount,
            'pendingAnalyses' => $pendingAnalyses,
            'paymentRate' => $paymentRate,
        ];
    }

    /**
     * Line chart: Revenue trend for period
     */
    public function getRevenueTrend($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $revenues = Paiement::select(
            DB::raw('DATE(date_paiement) as date'),
            DB::raw('SUM(montant) as total')
        )
            ->where('status', true)
            ->whereBetween('date_paiement', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $data = [];

        $diffInDays = $startDate->diffInDays($endDate);

        for ($i = 0; $i <= $diffInDays; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
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
     * Bar chart: Prescriptions per day for period
     */
    public function getPrescriptionsTrend($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $prescriptions = Prescription::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(id) as total')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $data = [];

        $diffInDays = $startDate->diffInDays($endDate);

        for ($i = 0; $i <= $diffInDays; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
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
     * Get daily breakdown of revenue
     */
    public function getDailyRevenueBreakdown($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        return Paiement::select(
            DB::raw('DATE(date_paiement) as date'),
            DB::raw('SUM(montant) as total'),
            DB::raw('COUNT(id) as count')
        )
            ->where('status', true)
            ->whereBetween('date_paiement', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Doughnut chart: Paid vs Unpaid ratio for period
     */
    public function getPaymentRatio($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $periodPrescriptions = Prescription::with(['analyses.parent', 'tubes'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $expectedQueryPeriod = $periodPrescriptions->sum(function ($p) {
            return $p->montant_total;
        });

        $totalPaidPeriod = Paiement::where('status', true)->whereBetween('date_paiement', [$startDate, $endDate])->sum('montant') ?? 0;
        $unpaidPeriod = max(0, $expectedQueryPeriod - $totalPaidPeriod);

        return [
            'labels' => ['Payé', 'Reste à payer'],
            'series' => [(float) $totalPaidPeriod, (float) $unpaidPeriod],
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

        $revenueThisMonth = Paiement::where('status', true)->where('date_paiement', '>=', $thisMonthStart)->sum('montant');
        $revenueLastMonth = Paiement::where('status', true)->whereBetween('date_paiement', [$lastMonthStart, $lastMonthEnd])->sum('montant');

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

    /**
     * Pie chart: Top 5 analyses performed this month or period
     */
    public function getTopAnalyses($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $top = DB::table('prescription_analyse as pa')
            ->join('analyses as a', 'pa.analyse_id', '=', 'a.id')
            ->join('prescriptions as p', 'pa.prescription_id', '=', 'p.id')
            ->whereBetween('p.created_at', [$startDate, $endDate])
            ->select('a.designation', DB::raw('COUNT(pa.analyse_id) as count'))
            ->groupBy('a.designation', 'a.id') // Added a.id for strict SQL
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'labels' => $top->pluck('designation'),
            'series' => $top->pluck('count'),
        ];
    }

    /**
     * Secretary: Payment methods distribution (Pie chart)
     */
    public function getPaymentMethodsStats()
    {
        $data = DB::table('paiements')
            ->join('payment_methods', 'paiements.payment_method_id', '=', 'payment_methods.id')
            ->select('payment_methods.label', DB::raw('COUNT(*) as count'))
            ->whereNull('paiements.deleted_at')
            ->groupBy('payment_methods.label')
            ->get();

        return [
            'labels' => $data->pluck('label'),
            'series' => $data->pluck('count'),
        ];
    }

    /**
     * Technician: Analysis completion trend (last 7 days)
     */
    public function getAnalysisCompletionTrend()
    {
        $days = [];
        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('d/m');
            $series[] = Prescription::whereDate('updated_at', $date)
                ->whereIn('status', ['TERMINE', 'VALIDE'])
                ->count();
        }

        return [
            'labels' => $days,
            'series' => $series,
        ];
    }

    /**
     * Biologist: Pathology Ratio
     */
    public function getPathologyRatio()
    {
        $total = \App\Models\Resultat::count();
        $patho = \App\Models\Resultat::where('interpretation', 'PATHOLOGIQUE')->count();
        $normal = max(0, $total - $patho);

        return [
            'labels' => ['Normal', 'Pathologique'],
            'series' => [$normal, $patho],
        ];
    }
}
