<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Display predictive analytics dashboard
     */
    public function index(): View
    {
        // Calculate key metrics
        $metrics = $this->calculateMetrics();
        
        // Get revenue trends (last 12 months)
        $revenueTrends = $this->getRevenueTrends();
        
        // Get client growth trends (last 12 months)
        $clientTrends = $this->getClientTrends();
        
        // Get forecasts for next 6 months
        $forecasts = $this->generateForecasts($revenueTrends);
        
        // Get top insights
        $insights = $this->generateInsights($metrics, $revenueTrends);

        return view('admin.analytics.index', compact(
            'metrics',
            'revenueTrends',
            'clientTrends',
            'forecasts',
            'insights'
        ));
    }

    /**
     * Calculate current metrics
     *
     * @return array<string, mixed>
     */
    private function calculateMetrics(): array
    {
        // Monthly Recurring Revenue (last 30 days)
        $mrr = DB::table('pib_invoices')
            ->where('invoice_date', '>=', now()->subDays(30))
            ->where('status', 'paid')
            ->sum('total_amount');

        // MRR 30 days ago for growth calculation
        $mrrPrevious = DB::table('pib_invoices')
            ->where('invoice_date', '>=', now()->subDays(60))
            ->where('invoice_date', '<', now()->subDays(30))
            ->where('status', 'paid')
            ->sum('total_amount');

        $mrrGrowth = $mrrPrevious > 0 ? (($mrr - $mrrPrevious) / $mrrPrevious) * 100 : 0;

        // Active clients
        $activeClients = DB::table('customers')
            ->whereNull('deleted_at')
            ->count();

        // New clients this month
        $newClients = DB::table('customers')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Total revenue (all time)
        $totalRevenue = DB::table('pib_invoices')
            ->where('status', 'paid')
            ->sum('total_amount');

        // Average revenue per client
        $arpc = $activeClients > 0 ? $totalRevenue / $activeClients : 0;

        // Unbilled service usage value
        $unbilledValue = DB::table('pib_service_usage')
            ->where('status', 'approved')
            ->whereNull('invoice_id')
            ->selectRaw('SUM(hours * COALESCE(hourly_rate, 150)) as total')
            ->value('total') ?? 0;

        return [
            'mrr' => round((float) $mrr, 2),
            'mrr_growth' => round((float) $mrrGrowth, 2),
            'active_clients' => $activeClients,
            'new_clients_this_month' => $newClients,
            'total_revenue' => round((float) $totalRevenue, 2),
            'arpc' => round((float) $arpc, 2),
            'unbilled_value' => round(is_numeric($unbilledValue) ? (float) $unbilledValue : 0.0, 2),
        ];
    }

    /**
     * Get revenue trends for last 12 months
     *
     * @return array<string, mixed>
     */
    private function getRevenueTrends(): array
    {
        $trends = DB::table('pib_invoices')
            ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month, SUM(total_amount) as revenue, COUNT(*) as invoice_count')
            ->where('invoice_date', '>=', now()->subMonths(12))
            ->where('status', 'paid')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return $trends->map(function ($item) {
            return [
                'month' => Carbon::parse($item->month . '-01')->format('M Y'),
                'revenue' => (float) $item->revenue,
                'invoice_count' => $item->invoice_count,
            ];
        })->toArray();
    }

    /**
     * Get client growth trends for last 12 months
     *
     * @return list<array<string, mixed>>
     */
    private function getClientTrends(): array
    {
        $trends = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = DB::table('customers')
                ->where('created_at', '<=', $month->endOfMonth())
                ->whereNull('deleted_at')
                ->count();
            
            $trends[] = [
                'month' => $month->format('M Y'),
                'clients' => $count,
            ];
        }

        return $trends;
    }

    /**
     * Generate forecasts for next 6 months using linear regression
     *
     * @param array<string, mixed> $revenueTrends
     * @return list<array<string, mixed>>
     */
    private function generateForecasts(array $revenueTrends): array
    {
        if (count($revenueTrends) < 3) {
            return [];
        }

        // Calculate simple moving average and growth rate
        $recentRevenues = array_slice(array_column($revenueTrends, 'revenue'), -3);
        $avgRevenue = array_sum($recentRevenues) / count($recentRevenues);
        
        // Calculate growth rate from last 3 months
        $growthRate = 0;
        if (count($recentRevenues) >= 2) {
            $growthRate = (($recentRevenues[count($recentRevenues) - 1] - $recentRevenues[0]) / $recentRevenues[0]) / (count($recentRevenues) - 1);
        }

        // Generate forecasts
        $forecasts = [];
        $lastRevenue = end($recentRevenues);
        
        for ($i = 1; $i <= 6; $i++) {
            $forecastDate = now()->addMonths($i);
            $forecastRevenue = $lastRevenue * (1 + ($growthRate * $i));
            
            $forecasts[] = [
                'month' => $forecastDate->format('M Y'),
                'forecast' => round($forecastRevenue, 2),
                'confidence' => $i <= 3 ? 'high' : 'medium', // Confidence decreases with time
            ];
        }

        return $forecasts;
    }

    /**
     * Generate actionable insights
     *
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $revenueTrends
     * @return list<array<string, mixed>>
     */
    private function generateInsights(array $metrics, array $revenueTrends): array
    {
        $insights = [];

        // MRR Growth Insight
        if ($metrics['mrr_growth'] > 10) {
            $val = $metrics['mrr_growth'];
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong Revenue Growth',
                'message' => sprintf('MRR grew by %.1f%% this month. Maintain current growth strategies.', is_numeric($val) ? (float) $val : 0.0),
            ];
        } elseif ($metrics['mrr_growth'] < -5) {
            $val = $metrics['mrr_growth'];
            $insights[] = [
                'type' => 'warning',
                'title' => 'Revenue Decline Alert',
                'message' => sprintf('MRR decreased by %.1f%%. Review client retention and pricing.', abs(is_numeric($val) ? (float) $val : 0.0)),
            ];
        }

        // New Clients Insight
        if ($metrics['new_clients_this_month'] > 5) {
            $val = $metrics['new_clients_this_month'];
            $insights[] = [
                'type' => 'success',
                'title' => 'Healthy Client Acquisition',
                'message' => sprintf('%d new clients added this month. Sales pipeline is strong.', is_numeric($val) ? (int) $val : 0),
            ];
        } elseif ($metrics['new_clients_this_month'] === 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'No New Clients',
                'message' => 'Zero client acquisitions this month. Review marketing and sales efforts.',
            ];
        }

        // Unbilled Services Insight
        if ($metrics['unbilled_value'] > 5000) {
            $val = $metrics['unbilled_value'];
            $insights[] = [
                'type' => 'info',
                'title' => 'Unbilled Services Pending',
                'message' => sprintf('$%s in approved services awaiting invoicing. Review service usage queue.', number_format(is_numeric($val) ? (float) $val : 0.0, 2)),
            ];
        }

        // Revenue Trend Insight
        if (count($revenueTrends) >= 3) {
            $recentRevenues = array_slice(array_column($revenueTrends, 'revenue'), -3);
            $isIncreasing = $recentRevenues[2] > $recentRevenues[1] && $recentRevenues[1] > $recentRevenues[0];
            
            if ($isIncreasing) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Positive Revenue Trend',
                    'message' => 'Revenue has been consistently increasing over the last 3 months.',
                ];
            }
        }

        // If no insights, add a neutral one
        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Metrics Stable',
                'message' => 'All key metrics are within expected ranges. Continue monitoring.',
            ];
        }

        return $insights;
    }
}
