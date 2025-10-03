<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\Backend\Branch;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceAnalyticsService
{
    /**
     * Generate comprehensive financial dashboard
     */
    public function getFinancialDashboard(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'revenue_analytics' => $this->getRevenueAnalytics($startDate, $endDate),
            'profitability_analysis' => $this->getProfitabilityAnalysis($startDate, $endDate),
            'cash_flow_metrics' => $this->getCashFlowMetrics($startDate, $endDate),
            'customer_financial_insights' => $this->getCustomerFinancialInsights($startDate, $endDate),
            'branch_financial_performance' => $this->getBranchFinancialPerformance($startDate, $endDate),
            'kpi_summary' => $this->getKPISummary($startDate, $endDate),
            'forecasting' => $this->getFinancialForecasting($startDate, $endDate),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])->get();

        $revenueByType = [
            'shipping' => 0,
            'cod' => 0,
            'surcharges' => 0,
        ];

        $monthlyRevenue = [];
        $currentMonth = $startDate->copy();

        while ($currentMonth <= $endDate) {
            $monthInvoices = $invoices->filter(function ($invoice) use ($currentMonth) {
                return $invoice->invoice_date->format('Y-m') === $currentMonth->format('Y-m');
            });

            $monthlyRevenue[] = [
                'month' => $currentMonth->format('M Y'),
                'total_revenue' => $monthInvoices->sum('total_amount'),
                'paid_amount' => $monthInvoices->where('status', 'paid')->sum('total_amount'),
                'outstanding' => $monthInvoices->where('status', '!=', 'paid')->sum('total_amount'),
            ];

            $currentMonth->addMonth();
        }

        // Calculate revenue by type (simplified)
        foreach ($invoices as $invoice) {
            $revenueByType['shipping'] += $invoice->subtotal ?? 0;
            // COD and surcharges would be calculated from line items in a real implementation
        }

        $totalRevenue = $invoices->sum('total_amount');
        $paidRevenue = $invoices->where('status', 'paid')->sum('total_amount');
        $outstandingRevenue = $totalRevenue - $paidRevenue;

        return [
            'total_revenue' => $totalRevenue,
            'paid_revenue' => $paidRevenue,
            'outstanding_revenue' => $outstandingRevenue,
            'collection_rate' => $totalRevenue > 0 ? ($paidRevenue / $totalRevenue) * 100 : 0,
            'revenue_by_type' => $revenueByType,
            'monthly_trend' => $monthlyRevenue,
            'average_invoice_value' => $invoices->count() > 0 ? $totalRevenue / $invoices->count() : 0,
            'invoice_count' => $invoices->count(),
        ];
    }

    /**
     * Get profitability analysis
     */
    public function getProfitabilityAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $settlementService = app(SettlementEngineService::class);
        $settlementReport = $settlementService->generateSettlementReport($startDate, $endDate);

        $totalRevenue = $settlementReport['summary']['total_revenue'];
        $totalCosts = $settlementReport['summary']['total_costs'];
        $grossProfit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        // Cost breakdown
        $costBreakdown = [
            'operational' => $totalCosts * 0.60, // 60% operational
            'hub_fees' => $totalCosts * 0.15,    // 15% hub fees
            'inter_branch' => $totalCosts * 0.10, // 10% inter-branch
            'other' => $totalCosts * 0.15,       // 15% other
        ];

        // Branch profitability
        $branchProfitability = collect($settlementReport['branch_settlements'])->map(function ($branch) {
            $profit = $branch['settlement']['net_amount'];
            $revenue = $branch['revenue']['total_revenue'];

            return [
                'branch_name' => $branch['branch']['name'],
                'revenue' => $revenue,
                'profit' => $profit,
                'profit_margin' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                'profitability_rating' => $this->calculateProfitabilityRating($profit, $revenue),
            ];
        })->sortByDesc('profit')->values();

        return [
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
            'net_profit' => $grossProfit, // Simplified, would include additional costs
            'cost_breakdown' => $costBreakdown,
            'branch_profitability' => $branchProfitability,
            'profit_trend' => $this->calculateProfitTrend($startDate, $endDate),
            'break_even_analysis' => $this->calculateBreakEvenAnalysis($startDate, $endDate),
        ];
    }

    /**
     * Get cash flow metrics
     */
    public function getCashFlowMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])->get();

        $cashInflows = [
            'invoice_payments' => $invoices->where('status', 'paid')->sum('total_amount'),
            'cod_collections' => $this->calculateCODCollections($startDate, $endDate),
        ];

        $cashOutflows = [
            'branch_settlements' => $this->calculateBranchSettlements($startDate, $endDate),
            'worker_payments' => $this->calculateWorkerPayments($startDate, $endDate),
            'operational_expenses' => $this->calculateOperationalExpenses($startDate, $endDate),
        ];

        $totalInflows = array_sum($cashInflows);
        $totalOutflows = array_sum($cashOutflows);
        $netCashFlow = $totalInflows - $totalOutflows;

        // Cash flow projection for next 3 months
        $cashFlowProjection = $this->projectCashFlow($startDate, $endDate, 3);

        return [
            'cash_inflows' => $cashInflows,
            'total_inflows' => $totalInflows,
            'cash_outflows' => $cashOutflows,
            'total_outflows' => $totalOutflows,
            'net_cash_flow' => $netCashFlow,
            'cash_flow_status' => $netCashFlow >= 0 ? 'positive' : 'negative',
            'operating_cash_ratio' => $totalOutflows > 0 ? ($totalInflows / $totalOutflows) : 0,
            'cash_flow_projection' => $cashFlowProjection,
            'working_capital' => $this->calculateWorkingCapital(),
        ];
    }

    /**
     * Get customer financial insights
     */
    public function getCustomerFinancialInsights(Carbon $startDate, Carbon $endDate): array
    {
        $customers = Customer::with(['invoices' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('invoice_date', [$startDate, $endDate]);
        }])->get();

        $customerInsights = $customers->map(function ($customer) {
            $invoices = $customer->invoices;
            $totalBilled = $invoices->sum('total_amount');
            $totalPaid = $invoices->where('status', 'paid')->sum('total_amount');
            $outstanding = $totalBilled - $totalPaid;

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'outstanding_balance' => $outstanding,
                'payment_status' => $this->assessPaymentStatus($outstanding, $totalBilled),
                'average_invoice_value' => $invoices->count() > 0 ? $totalBilled / $invoices->count() : 0,
                'invoice_count' => $invoices->count(),
                'payment_reliability_score' => $this->calculatePaymentReliabilityScore($customer),
            ];
        })->sortByDesc('total_billed')->take(20)->values();

        $segmentAnalysis = $this->analyzeCustomerSegments($customers, $startDate, $endDate);

        return [
            'top_customers' => $customerInsights,
            'segment_analysis' => $segmentAnalysis,
            'overall_metrics' => [
                'total_customers' => $customers->count(),
                'active_customers' => $customers->filter(function ($customer) {
                    return $customer->invoices->count() > 0;
                })->count(),
                'average_customer_value' => $customers->avg(function ($customer) {
                    return $customer->invoices->sum('total_amount');
                }),
            ],
        ];
    }

    /**
     * Get branch financial performance
     */
    public function getBranchFinancialPerformance(Carbon $startDate, Carbon $endDate): array
    {
        $branches = Branch::active()->get();
        $settlementService = app(SettlementEngineService::class);

        $branchPerformance = $branches->map(function ($branch) use ($settlementService, $startDate, $endDate) {
            $settlement = $settlementService->calculateBranchSettlement($branch, $startDate, $endDate);

            return [
                'branch_name' => $branch->name,
                'branch_type' => $branch->type,
                'revenue' => $settlement['revenue']['total_revenue'],
                'costs' => $settlement['costs']['total_costs'],
                'profit' => $settlement['settlement']['net_amount'],
                'profit_margin' => $settlement['revenue']['total_revenue'] > 0
                    ? ($settlement['settlement']['net_amount'] / $settlement['revenue']['total_revenue']) * 100
                    : 0,
                'shipment_volume' => $settlement['breakdown']['total_shipments'],
                'efficiency_score' => $this->calculateBranchEfficiencyScore($settlement),
            ];
        })->sortByDesc('revenue')->values();

        return [
            'branch_performance' => $branchPerformance,
            'top_performing_branches' => collect($branchPerformance)->sortByDesc('profit_margin')->take(5)->values(),
            'underperforming_branches' => collect($branchPerformance)->filter(function ($branch) {
                return $branch['profit_margin'] < 10; // Less than 10% margin
            })->values(),
            'network_overview' => [
                'total_branches' => $branches->count(),
                'total_revenue' => collect($branchPerformance)->sum('revenue'),
                'total_profit' => collect($branchPerformance)->sum('profit'),
                'average_profit_margin' => collect($branchPerformance)->avg('profit_margin'),
            ],
        ];
    }

    /**
     * Get KPI summary
     */
    public function getKPISummary(Carbon $startDate, Carbon $endDate): array
    {
        $revenueAnalytics = $this->getRevenueAnalytics($startDate, $endDate);
        $profitabilityAnalysis = $this->getProfitabilityAnalysis($startDate, $endDate);
        $cashFlowMetrics = $this->getCashFlowMetrics($startDate, $endDate);

        $kpis = [
            [
                'name' => 'Total Revenue',
                'value' => $revenueAnalytics['total_revenue'],
                'format' => 'currency',
                'trend' => $this->calculateTrend($revenueAnalytics['monthly_trend'], 'total_revenue'),
                'target' => 100000, // Monthly target
                'status' => $revenueAnalytics['total_revenue'] >= 100000 ? 'good' : 'warning',
            ],
            [
                'name' => 'Profit Margin',
                'value' => $profitabilityAnalysis['profit_margin'],
                'format' => 'percentage',
                'trend' => 'stable', // Would calculate from historical data
                'target' => 25.0,
                'status' => $profitabilityAnalysis['profit_margin'] >= 25.0 ? 'good' : 'warning',
            ],
            [
                'name' => 'Collection Rate',
                'value' => $revenueAnalytics['collection_rate'],
                'format' => 'percentage',
                'trend' => 'up',
                'target' => 95.0,
                'status' => $revenueAnalytics['collection_rate'] >= 95.0 ? 'good' : 'warning',
            ],
            [
                'name' => 'Net Cash Flow',
                'value' => $cashFlowMetrics['net_cash_flow'],
                'format' => 'currency',
                'trend' => $cashFlowMetrics['net_cash_flow'] >= 0 ? 'up' : 'down',
                'target' => 0,
                'status' => $cashFlowMetrics['net_cash_flow'] >= 0 ? 'good' : 'critical',
            ],
            [
                'name' => 'Customer Satisfaction',
                'value' => 4.2, // Mock data
                'format' => 'rating',
                'trend' => 'stable',
                'target' => 4.0,
                'status' => 'good',
            ],
            [
                'name' => 'Average Invoice Value',
                'value' => $revenueAnalytics['average_invoice_value'],
                'format' => 'currency',
                'trend' => 'up',
                'target' => 50.00,
                'status' => $revenueAnalytics['average_invoice_value'] >= 50.00 ? 'good' : 'neutral',
            ],
        ];

        return [
            'kpis' => $kpis,
            'overall_health_score' => $this->calculateOverallHealthScore($kpis),
            'kpi_categories' => [
                'financial' => collect($kpis)->whereIn('name', ['Total Revenue', 'Profit Margin', 'Net Cash Flow'])->values(),
                'operational' => collect($kpis)->whereIn('name', ['Collection Rate', 'Average Invoice Value'])->values(),
                'customer' => collect($kpis)->where('name', 'Customer Satisfaction')->values(),
            ],
        ];
    }

    /**
     * Get financial forecasting
     */
    public function getFinancialForecasting(Carbon $startDate, Carbon $endDate, int $months = 6): array
    {
        $historicalData = $this->getHistoricalFinancialData($startDate, $endDate);

        $forecast = [];
        $currentRevenue = $historicalData['average_monthly_revenue'];
        $growthRate = 0.05; // 5% monthly growth assumption

        for ($i = 1; $i <= $months; $i++) {
            $forecastedRevenue = $currentRevenue * pow(1 + $growthRate, $i);
            $forecastedCosts = $forecastedRevenue * 0.75; // 75% cost ratio assumption
            $forecastedProfit = $forecastedRevenue - $forecastedCosts;

            $forecast[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'revenue' => $forecastedRevenue,
                'costs' => $forecastedCosts,
                'profit' => $forecastedProfit,
                'profit_margin' => ($forecastedProfit / $forecastedRevenue) * 100,
                'confidence_level' => max(0, 100 - ($i * 10)), // Decreasing confidence
            ];
        }

        return [
            'forecast_period_months' => $months,
            'forecast_data' => $forecast,
            'assumptions' => [
                'monthly_growth_rate' => $growthRate * 100,
                'cost_ratio' => 75.0,
                'base_revenue' => $currentRevenue,
            ],
            'total_forecasted_revenue' => collect($forecast)->sum('revenue'),
            'total_forecasted_profit' => collect($forecast)->sum('profit'),
        ];
    }

    // Helper methods

    private function calculateProfitabilityRating(float $profit, float $revenue): string
    {
        if ($revenue == 0) return 'neutral';

        $margin = ($profit / $revenue) * 100;

        if ($margin >= 25) return 'excellent';
        if ($margin >= 15) return 'good';
        if ($margin >= 5) return 'fair';
        if ($margin >= 0) return 'poor';
        return 'loss';
    }

    private function calculateProfitTrend(Carbon $startDate, Carbon $endDate): array
    {
        // Simplified trend calculation
        return [
            'direction' => 'up',
            'percentage_change' => 12.5,
            'period' => 'vs previous period',
        ];
    }

    private function calculateBreakEvenAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        // Simplified break-even analysis
        return [
            'fixed_costs' => 50000,
            'variable_cost_per_unit' => 5.00,
            'average_selling_price' => 25.00,
            'break_even_units' => 2500,
            'break_even_revenue' => 62500,
            'current_status' => 'above_break_even',
        ];
    }

    private function calculateCODCollections(Carbon $startDate, Carbon $endDate): float
    {
        // Simplified COD calculation
        return 15000; // Mock data
    }

    private function calculateBranchSettlements(Carbon $startDate, Carbon $endDate): float
    {
        // Simplified settlement calculation
        return 35000; // Mock data
    }

    private function calculateWorkerPayments(Carbon $startDate, Carbon $endDate): float
    {
        // Simplified worker payment calculation
        return 25000; // Mock data
    }

    private function calculateOperationalExpenses(Carbon $startDate, Carbon $endDate): float
    {
        // Simplified operational expense calculation
        return 20000; // Mock data
    }

    private function projectCashFlow(Carbon $startDate, Carbon $endDate, int $months): array
    {
        $projection = [];
        for ($i = 1; $i <= $months; $i++) {
            $projection[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'projected_inflow' => 50000 + ($i * 2000),
                'projected_outflow' => 45000 + ($i * 1500),
                'projected_net' => 5000 + ($i * 500),
            ];
        }
        return $projection;
    }

    private function calculateWorkingCapital(): float
    {
        // Simplified working capital calculation
        return 75000; // Mock data
    }

    private function assessPaymentStatus(float $outstanding, float $totalBilled): string
    {
        if ($totalBilled == 0) return 'no_activity';

        $ratio = ($outstanding / $totalBilled) * 100;

        if ($ratio == 0) return 'current';
        if ($ratio <= 25) return 'good';
        if ($ratio <= 50) return 'fair';
        return 'poor';
    }

    private function calculatePaymentReliabilityScore(Customer $customer): float
    {
        // Simplified reliability score calculation
        return 85.5; // Mock data
    }

    private function analyzeCustomerSegments(Collection $customers, Carbon $startDate, Carbon $endDate): array
    {
        $segments = [
            'high_value' => $customers->filter(function ($customer) {
                return $customer->invoices->sum('total_amount') > 5000;
            }),
            'medium_value' => $customers->filter(function ($customer) {
                $total = $customer->invoices->sum('total_amount');
                return $total >= 1000 && $total <= 5000;
            }),
            'low_value' => $customers->filter(function ($customer) {
                return $customer->invoices->sum('total_amount') < 1000;
            }),
        ];

        return collect($segments)->map(function ($segment, $name) {
            return [
                'segment' => $name,
                'customer_count' => $segment->count(),
                'total_revenue' => $segment->sum(function ($customer) {
                    return $customer->invoices->sum('total_amount');
                }),
                'average_revenue' => $segment->avg(function ($customer) {
                    return $customer->invoices->sum('total_amount');
                }),
            ];
        })->values();
    }

    private function calculateBranchEfficiencyScore(array $settlement): float
    {
        $revenue = $settlement['revenue']['total_revenue'];
        $costs = $settlement['costs']['total_costs'];
        $shipments = $settlement['breakdown']['total_shipments'];

        if ($revenue == 0 || $shipments == 0) return 0;

        $profitMargin = (($revenue - $costs) / $revenue) * 100;
        $costPerShipment = $costs / $shipments;

        // Weighted efficiency score
        return min(100, ($profitMargin * 0.6) + ((1000 / $costPerShipment) * 0.4));
    }

    private function calculateTrend(array $monthlyData, string $field): string
    {
        if (count($monthlyData) < 2) return 'stable';

        $firstHalf = array_slice($monthlyData, 0, count($monthlyData) / 2);
        $secondHalf = array_slice($monthlyData, count($monthlyData) / 2);

        $firstAvg = collect($firstHalf)->avg($field);
        $secondAvg = collect($secondHalf)->avg($field);

        if ($secondAvg > $firstAvg * 1.05) return 'up';
        if ($secondAvg < $firstAvg * 0.95) return 'down';
        return 'stable';
    }

    private function calculateOverallHealthScore(array $kpis): float
    {
        $scores = collect($kpis)->map(function ($kpi) {
            return match($kpi['status']) {
                'good' => 100,
                'warning' => 70,
                'critical' => 40,
                default => 50,
            };
        });

        return $scores->avg();
    }

    private function getHistoricalFinancialData(Carbon $startDate, Carbon $endDate): array
    {
        // Simplified historical data
        return [
            'average_monthly_revenue' => 85000,
            'average_monthly_costs' => 63750,
            'average_monthly_profit' => 21250,
            'growth_trend' => 8.5,
        ];
    }
}