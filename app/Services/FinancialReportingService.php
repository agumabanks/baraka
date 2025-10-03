<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Backend\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportingService
{
    /**
     * Generate comprehensive financial report
     */
    public function generateFinancialReport(Carbon $startDate, Carbon $endDate, array $options = []): array
    {
        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'generated_at' => now()->toISOString(),
            ],
            'executive_summary' => $this->generateExecutiveSummary($startDate, $endDate),
            'income_statement' => $this->generateIncomeStatement($startDate, $endDate),
            'balance_sheet' => $this->generateBalanceSheet($endDate),
            'cash_flow_statement' => $this->generateCashFlowStatement($startDate, $endDate),
            'accounts_receivable' => $this->generateARReport($startDate, $endDate),
            'accounts_payable' => $this->generateAPReport($startDate, $endDate),
            'branch_performance' => $this->generateBranchPerformanceReport($startDate, $endDate),
            'customer_analysis' => $this->generateCustomerAnalysis($startDate, $endDate),
            'variance_analysis' => $this->generateVarianceAnalysis($startDate, $endDate),
            'forecasting' => $this->generateFinancialForecast($startDate, $endDate),
        ];

        if (isset($options['include_charts']) && $options['include_charts']) {
            $report['charts'] = $this->generateChartData($report);
        }

        return $report;
    }

    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary(Carbon $startDate, Carbon $endDate): array
    {
        $analyticsService = app(FinanceAnalyticsService::class);
        $analytics = $analyticsService->getFinancialDashboard($startDate, $endDate);

        return [
            'total_revenue' => $analytics['revenue_analytics']['total_revenue'],
            'total_expenses' => $analytics['profitability_analysis']['cost_breakdown'],
            'net_profit' => $analytics['profitability_analysis']['net_profit'],
            'profit_margin' => $analytics['profitability_analysis']['profit_margin'],
            'collection_rate' => $analytics['revenue_analytics']['collection_rate'],
            'working_capital' => $analytics['cash_flow_metrics']['working_capital'],
            'key_highlights' => $this->identifyKeyHighlights($analytics),
            'risk_indicators' => $this->identifyRiskIndicators($analytics),
            'recommendations' => $this->generateExecutiveRecommendations($analytics),
        ];
    }

    /**
     * Generate income statement
     */
    private function generateIncomeStatement(Carbon $startDate, Carbon $endDate): array
    {
        // Revenue section
        $revenue = [
            'shipping_revenue' => $this->calculateRevenueByType($startDate, $endDate, 'shipping'),
            'cod_revenue' => $this->calculateRevenueByType($startDate, $endDate, 'cod'),
            'fuel_surcharge_revenue' => $this->calculateRevenueByType($startDate, $endDate, 'fuel_surcharge'),
            'special_handling_revenue' => $this->calculateRevenueByType($startDate, $endDate, 'special_handling'),
        ];

        $totalRevenue = array_sum($revenue);

        // Cost of goods sold
        $cogs = [
            'transportation_costs' => $this->calculateCOGS($startDate, $endDate, 'transportation'),
            'handling_costs' => $this->calculateCOGS($startDate, $endDate, 'handling'),
            'sorting_costs' => $this->calculateCOGS($startDate, $endDate, 'sorting'),
        ];

        $totalCOGS = array_sum($cogs);
        $grossProfit = $totalRevenue - $totalCOGS;

        // Operating expenses
        $operatingExpenses = [
            'salaries_and_wages' => $this->calculateOperatingExpenses($startDate, $endDate, 'salaries'),
            'rent_and_utilities' => $this->calculateOperatingExpenses($startDate, $endDate, 'rent'),
            'insurance' => $this->calculateOperatingExpenses($startDate, $endDate, 'insurance'),
            'marketing' => $this->calculateOperatingExpenses($startDate, $endDate, 'marketing'),
            'depreciation' => $this->calculateOperatingExpenses($startDate, $endDate, 'depreciation'),
            'other_operating' => $this->calculateOperatingExpenses($startDate, $endDate, 'other'),
        ];

        $totalOperatingExpenses = array_sum($operatingExpenses);
        $operatingIncome = $grossProfit - $totalOperatingExpenses;

        // Non-operating items
        $nonOperatingItems = [
            'interest_income' => $this->calculateNonOperating($startDate, $endDate, 'interest_income'),
            'interest_expense' => $this->calculateNonOperating($startDate, $endDate, 'interest_expense'),
            'other_income' => $this->calculateNonOperating($startDate, $endDate, 'other_income'),
        ];

        $totalNonOperating = array_sum($nonOperatingItems);
        $netIncomeBeforeTax = $operatingIncome + $totalNonOperating;

        // Taxes
        $taxes = $this->calculateTaxes($netIncomeBeforeTax);
        $netIncome = $netIncomeBeforeTax - $taxes;

        return [
            'revenue' => $revenue,
            'total_revenue' => $totalRevenue,
            'cost_of_goods_sold' => $cogs,
            'total_cogs' => $totalCOGS,
            'gross_profit' => $grossProfit,
            'gross_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
            'operating_expenses' => $operatingExpenses,
            'total_operating_expenses' => $totalOperatingExpenses,
            'operating_income' => $operatingIncome,
            'non_operating_items' => $nonOperatingItems,
            'total_non_operating' => $totalNonOperating,
            'net_income_before_tax' => $netIncomeBeforeTax,
            'taxes' => $taxes,
            'net_income' => $netIncome,
            'net_margin' => $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0,
        ];
    }

    /**
     * Generate balance sheet
     */
    private function generateBalanceSheet(Carbon $endDate): array
    {
        // Assets
        $currentAssets = [
            'cash_and_equivalents' => $this->getBalanceSheetItem('cash', $endDate),
            'accounts_receivable' => $this->getBalanceSheetItem('accounts_receivable', $endDate),
            'inventory' => $this->getBalanceSheetItem('inventory', $endDate),
            'prepaid_expenses' => $this->getBalanceSheetItem('prepaid_expenses', $endDate),
        ];

        $totalCurrentAssets = array_sum($currentAssets);

        $fixedAssets = [
            'property_and_equipment' => $this->getBalanceSheetItem('property_equipment', $endDate),
            'accumulated_depreciation' => $this->getBalanceSheetItem('accumulated_depreciation', $endDate),
        ];

        $netFixedAssets = $fixedAssets['property_and_equipment'] - $fixedAssets['accumulated_depreciation'];
        $totalAssets = $totalCurrentAssets + $netFixedAssets;

        // Liabilities
        $currentLiabilities = [
            'accounts_payable' => $this->getBalanceSheetItem('accounts_payable', $endDate),
            'accrued_expenses' => $this->getBalanceSheetItem('accrued_expenses', $endDate),
            'short_term_debt' => $this->getBalanceSheetItem('short_term_debt', $endDate),
            'current_portion_long_term_debt' => $this->getBalanceSheetItem('current_long_term_debt', $endDate),
        ];

        $totalCurrentLiabilities = array_sum($currentLiabilities);

        $longTermLiabilities = [
            'long_term_debt' => $this->getBalanceSheetItem('long_term_debt', $endDate),
            'deferred_tax_liability' => $this->getBalanceSheetItem('deferred_tax', $endDate),
        ];

        $totalLongTermLiabilities = array_sum($longTermLiabilities);
        $totalLiabilities = $totalCurrentLiabilities + $totalLongTermLiabilities;

        // Equity
        $equity = [
            'common_stock' => $this->getBalanceSheetItem('common_stock', $endDate),
            'retained_earnings' => $this->getBalanceSheetItem('retained_earnings', $endDate),
            'accumulated_other_comprehensive_income' => $this->getBalanceSheetItem('aoci', $endDate),
        ];

        $totalEquity = array_sum($equity);
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
            'assets' => [
                'current_assets' => $currentAssets,
                'total_current_assets' => $totalCurrentAssets,
                'fixed_assets' => $fixedAssets,
                'net_fixed_assets' => $netFixedAssets,
                'total_assets' => $totalAssets,
            ],
            'liabilities' => [
                'current_liabilities' => $currentLiabilities,
                'total_current_liabilities' => $totalCurrentLiabilities,
                'long_term_liabilities' => $longTermLiabilities,
                'total_long_term_liabilities' => $totalLongTermLiabilities,
                'total_liabilities' => $totalLiabilities,
            ],
            'equity' => [
                'equity_items' => $equity,
                'total_equity' => $totalEquity,
            ],
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'balances' => $totalAssets == $totalLiabilitiesAndEquity,
            'working_capital' => $totalCurrentAssets - $totalCurrentLiabilities,
            'current_ratio' => $totalCurrentLiabilities > 0 ? $totalCurrentAssets / $totalCurrentLiabilities : 0,
            'debt_to_equity_ratio' => $totalEquity > 0 ? $totalLiabilities / $totalEquity : 0,
        ];
    }

    /**
     * Generate cash flow statement
     */
    private function generateCashFlowStatement(Carbon $startDate, Carbon $endDate): array
    {
        // Operating activities
        $operatingActivities = [
            'net_income' => $this->getCashFlowItem('net_income', $startDate, $endDate),
            'depreciation' => $this->getCashFlowItem('depreciation', $startDate, $endDate),
            'changes_in_working_capital' => $this->calculateWorkingCapitalChanges($startDate, $endDate),
        ];

        $netCashFromOperating = array_sum($operatingActivities);

        // Investing activities
        $investingActivities = [
            'capital_expenditures' => $this->getCashFlowItem('capex', $startDate, $endDate),
            'equipment_sales' => $this->getCashFlowItem('equipment_sales', $startDate, $endDate),
        ];

        $netCashFromInvesting = array_sum($investingActivities);

        // Financing activities
        $financingActivities = [
            'debt_proceeds' => $this->getCashFlowItem('debt_proceeds', $startDate, $endDate),
            'debt_repayments' => $this->getCashFlowItem('debt_repayments', $startDate, $endDate),
            'equity_issuance' => $this->getCashFlowItem('equity_issuance', $startDate, $endDate),
            'dividends_paid' => $this->getCashFlowItem('dividends', $startDate, $endDate),
        ];

        $netCashFromFinancing = array_sum($financingActivities);

        $netChangeInCash = $netCashFromOperating + $netCashFromInvesting + $netCashFromFinancing;
        $beginningCashBalance = $this->getBeginningCashBalance($startDate);
        $endingCashBalance = $beginningCashBalance + $netChangeInCash;

        return [
            'operating_activities' => $operatingActivities,
            'net_cash_from_operating' => $netCashFromOperating,
            'investing_activities' => $investingActivities,
            'net_cash_from_investing' => $netCashFromInvesting,
            'financing_activities' => $financingActivities,
            'net_cash_from_financing' => $netCashFromFinancing,
            'net_change_in_cash' => $netChangeInCash,
            'beginning_cash_balance' => $beginningCashBalance,
            'ending_cash_balance' => $endingCashBalance,
            'cash_flow_ratio' => $this->calculateCashFlowRatios($netCashFromOperating, $startDate, $endDate),
        ];
    }

    /**
     * Generate accounts receivable report
     */
    private function generateARReport(Carbon $startDate, Carbon $endDate): array
    {
        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->with('customer')
            ->get();

        $agingBuckets = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
        ];

        $totalReceivable = 0;
        $totalOverdue = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->status === 'paid') continue;

            $paidAmount = $invoice->payments()->sum('amount');
            $outstanding = $invoice->total_amount - $paidAmount;
            $totalReceivable += $outstanding;

            if ($outstanding <= 0) continue;

            $daysOverdue = now()->diffInDays($invoice->due_date);

            if ($daysOverdue <= 0) {
                $agingBuckets['current'] += $outstanding;
            } elseif ($daysOverdue <= 30) {
                $agingBuckets['1_30_days'] += $outstanding;
                $totalOverdue += $outstanding;
            } elseif ($daysOverdue <= 60) {
                $agingBuckets['31_60_days'] += $outstanding;
                $totalOverdue += $outstanding;
            } elseif ($daysOverdue <= 90) {
                $agingBuckets['61_90_days'] += $outstanding;
                $totalOverdue += $outstanding;
            } else {
                $agingBuckets['over_90_days'] += $outstanding;
                $totalOverdue += $outstanding;
            }
        }

        return [
            'total_accounts_receivable' => $totalReceivable,
            'total_overdue' => $totalOverdue,
            'aging_breakdown' => $agingBuckets,
            'collection_rate' => $this->calculateCollectionRate($invoices),
            'average_collection_period' => $this->calculateAverageCollectionPeriod($invoices),
            'bad_debt_allowance' => $totalOverdue * 0.05, // 5% allowance
            'top_overdue_customers' => $this->getTopOverdueCustomers($invoices),
        ];
    }

    /**
     * Generate accounts payable report
     */
    private function generateAPReport(Carbon $startDate, Carbon $endDate): array
    {
        // Simplified AP report - in a real implementation, this would pull from vendor bills
        $settlementService = app(SettlementEngineService::class);
        $settlements = $settlementService->generateSettlementReport($startDate, $endDate);

        $totalPayable = collect($settlements['branch_settlements'])
            ->where('settlement.settlement_type', 'receivable_from_branch')
            ->sum('settlement.settlement_amount');

        return [
            'total_accounts_payable' => $totalPayable,
            'aging_breakdown' => [
                'current' => $totalPayable * 0.7,
                '1_30_days' => $totalPayable * 0.2,
                '31_60_days' => $totalPayable * 0.08,
                'over_60_days' => $totalPayable * 0.02,
            ],
            'payment_terms' => 'Net 30 days',
            'average_payment_period' => 28,
        ];
    }

    /**
     * Generate branch performance report
     */
    private function generateBranchPerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $branches = Branch::active()->get();
        $settlementService = app(SettlementEngineService::class);

        $branchPerformance = [];

        foreach ($branches as $branch) {
            $settlement = $settlementService->calculateBranchSettlement($branch, $startDate, $endDate);

            $branchPerformance[] = [
                'branch_name' => $branch->name,
                'branch_type' => $branch->type,
                'revenue' => $settlement['revenue']['total_revenue'],
                'costs' => $settlement['costs']['total_costs'],
                'profit' => $settlement['settlement']['net_amount'],
                'profit_margin' => $settlement['revenue']['total_revenue'] > 0
                    ? ($settlement['settlement']['net_amount'] / $settlement['revenue']['total_revenue']) * 100
                    : 0,
                'shipment_volume' => $settlement['breakdown']['total_shipments'],
                'cost_per_shipment' => $settlement['breakdown']['total_shipments'] > 0
                    ? $settlement['costs']['total_costs'] / $settlement['breakdown']['total_shipments']
                    : 0,
                'performance_rating' => $this->calculateBranchPerformanceRating($settlement),
            ];
        }

        return [
            'branch_performance' => collect($branchPerformance)->sortByDesc('profit')->values(),
            'top_performing_branches' => collect($branchPerformance)->sortByDesc('profit_margin')->take(3)->values(),
            'underperforming_branches' => collect($branchPerformance)->filter(function ($branch) {
                return $branch['profit_margin'] < 5;
            })->values(),
            'network_summary' => [
                'total_branches' => count($branchPerformance),
                'total_revenue' => collect($branchPerformance)->sum('revenue'),
                'total_profit' => collect($branchPerformance)->sum('profit'),
                'average_profit_margin' => collect($branchPerformance)->avg('profit_margin'),
            ],
        ];
    }

    /**
     * Generate customer analysis
     */
    private function generateCustomerAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $analyticsService = app(FinanceAnalyticsService::class);
        $customerInsights = $analyticsService->getCustomerFinancialInsights($startDate, $endDate);

        return [
            'customer_segments' => $customerInsights['segment_analysis'],
            'top_customers_by_revenue' => collect($customerInsights['top_customers'])->sortByDesc('total_billed')->take(10)->values(),
            'payment_behavior' => [
                'average_payment_time' => $this->calculateAveragePaymentTime($startDate, $endDate),
                'payment_reliability_score' => collect($customerInsights['top_customers'])->avg('payment_reliability_score'),
            ],
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue($customerInsights['top_customers']),
        ];
    }

    /**
     * Generate variance analysis
     */
    private function generateVarianceAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $currentPeriod = $this->generateIncomeStatement($startDate, $endDate);

        // Compare with previous period
        $previousStart = $startDate->copy()->subMonths($startDate->diffInMonths($endDate) + 1);
        $previousEnd = $endDate->copy()->subMonths($startDate->diffInMonths($endDate) + 1);
        $previousPeriod = $this->generateIncomeStatement($previousStart, $previousEnd);

        $variances = [];

        foreach ($currentPeriod as $key => $currentValue) {
            if (is_array($currentValue)) {
                $variances[$key] = [];
                foreach ($currentValue as $subKey => $subValue) {
                    $previousValue = $previousPeriod[$key][$subKey] ?? 0;
                    $variance = $subValue - $previousValue;
                    $variancePercent = $previousValue > 0 ? ($variance / $previousValue) * 100 : 0;

                    $variances[$key][$subKey] = [
                        'current' => $subValue,
                        'previous' => $previousValue,
                        'variance' => $variance,
                        'variance_percent' => $variancePercent,
                        'favorable' => $this->isFavorableVariance($key, $subKey, $variance),
                    ];
                }
            } else {
                $previousValue = $previousPeriod[$key] ?? 0;
                $variance = $currentValue - $previousValue;
                $variancePercent = $previousValue > 0 ? ($variance / $previousValue) * 100 : 0;

                $variances[$key] = [
                    'current' => $currentValue,
                    'previous' => $previousValue,
                    'variance' => $variance,
                    'variance_percent' => $variancePercent,
                    'favorable' => $this->isFavorableVariance($key, null, $variance),
                ];
            }
        }

        return [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'variances' => $variances,
            'significant_variances' => $this->identifySignificantVariances($variances),
        ];
    }

    /**
     * Generate financial forecast
     */
    private function generateFinancialForecast(Carbon $startDate, Carbon $endDate): array
    {
        $analyticsService = app(FinanceAnalyticsService::class);
        $forecast = $analyticsService->getFinancialForecasting($startDate, $endDate, 6);

        return [
            'forecast_period_months' => 6,
            'revenue_forecast' => collect($forecast['forecast_data'])->pluck('revenue')->values(),
            'cost_forecast' => collect($forecast['forecast_data'])->pluck('costs')->values(),
            'profit_forecast' => collect($forecast['forecast_data'])->pluck('profit')->values(),
            'assumptions' => $forecast['assumptions'],
            'confidence_intervals' => $this->calculateConfidenceIntervals($forecast['forecast_data']),
        ];
    }

    /**
     * Generate PDF report
     */
    public function generatePDFReport(array $reportData, string $reportType = 'comprehensive'): string
    {
        $data = [
            'report' => $reportData,
            'report_type' => $reportType,
            'company' => [
                'name' => 'Baraka Courier Management System',
                'period' => $reportData['period'],
            ],
        ];

        $pdf = Pdf::loadView("reports.financial.{$reportType}", $data);

        return $pdf->output();
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(array $reportData, string $format = 'xlsx'): string
    {
        // In a real implementation, this would use Laravel Excel or similar
        // For now, return JSON representation
        return json_encode($reportData, JSON_PRETTY_PRINT);
    }

    // Helper methods

    private function calculateRevenueByType(Carbon $startDate, Carbon $endDate, string $type): float
    {
        // Simplified calculation - in real implementation would query actual data
        return match($type) {
            'shipping' => 50000,
            'cod' => 15000,
            'fuel_surcharge' => 3000,
            'special_handling' => 2000,
            default => 0,
        };
    }

    private function calculateCOGS(Carbon $startDate, Carbon $endDate, string $type): float
    {
        return match($type) {
            'transportation' => 20000,
            'handling' => 8000,
            'sorting' => 5000,
            default => 0,
        };
    }

    private function calculateOperatingExpenses(Carbon $startDate, Carbon $endDate, string $type): float
    {
        return match($type) {
            'salaries' => 25000,
            'rent' => 5000,
            'insurance' => 2000,
            'marketing' => 3000,
            'depreciation' => 1500,
            'other' => 1000,
            default => 0,
        };
    }

    private function calculateNonOperating(Carbon $startDate, Carbon $endDate, string $type): float
    {
        return match($type) {
            'interest_income' => 500,
            'interest_expense' => -800,
            'other_income' => 200,
            default => 0,
        };
    }

    private function calculateTaxes(float $netIncome): float
    {
        $taxRate = 0.25; // 25% corporate tax rate
        return max(0, $netIncome * $taxRate);
    }

    private function getBalanceSheetItem(string $type, Carbon $date): float
    {
        // Simplified balance sheet data
        return match($type) {
            'cash' => 50000,
            'accounts_receivable' => 25000,
            'inventory' => 5000,
            'prepaid_expenses' => 2000,
            'property_equipment' => 100000,
            'accumulated_depreciation' => -20000,
            'accounts_payable' => 15000,
            'accrued_expenses' => 5000,
            'short_term_debt' => 10000,
            'current_long_term_debt' => 5000,
            'long_term_debt' => 30000,
            'deferred_tax' => 2000,
            'common_stock' => 50000,
            'retained_earnings' => 25000,
            'aoci' => 1000,
            default => 0,
        };
    }

    private function getCashFlowItem(string $type, Carbon $startDate, Carbon $endDate): float
    {
        return match($type) {
            'net_income' => 15000,
            'depreciation' => 2000,
            'capex' => -10000,
            'equipment_sales' => 1000,
            'debt_proceeds' => 5000,
            'debt_repayments' => -3000,
            'equity_issuance' => 0,
            'dividends' => -2000,
            default => 0,
        };
    }

    private function calculateWorkingCapitalChanges(Carbon $startDate, Carbon $endDate): float
    {
        return -5000; // Simplified
    }

    private function getBeginningCashBalance(Carbon $startDate): float
    {
        return 45000;
    }

    private function calculateCashFlowRatios(float $operatingCashFlow, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'operating_cash_flow_ratio' => 1.2,
            'cash_flow_margin' => 15.5,
        ];
    }

    private function calculateCollectionRate(Collection $invoices): float
    {
        $totalInvoiced = $invoices->sum('total_amount');
        $totalCollected = $invoices->where('status', 'paid')->sum('total_amount');

        return $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
    }

    private function calculateAverageCollectionPeriod(Collection $invoices): float
    {
        $paidInvoices = $invoices->where('status', 'paid')->whereNotNull('paid_at');

        if ($paidInvoices->isEmpty()) return 0;

        $totalDays = $paidInvoices->sum(function ($invoice) {
            return $invoice->invoice_date->diffInDays($invoice->paid_at);
        });

        return $totalDays / $paidInvoices->count();
    }

    private function getTopOverdueCustomers(Collection $invoices): array
    {
        return $invoices->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->groupBy('customer_id')
            ->map(function ($customerInvoices) {
                $customer = $customerInvoices->first()->customer;
                return [
                    'customer_name' => $customer->name,
                    'total_overdue' => $customerInvoices->sum(function ($invoice) {
                        return $invoice->total_amount - $invoice->payments()->sum('amount');
                    }),
                    'invoice_count' => $customerInvoices->count(),
                ];
            })
            ->sortByDesc('total_overdue')
            ->take(10)
            ->values()
            ->toArray();
    }

    private function calculateBranchPerformanceRating(array $settlement): string
    {
        $profitMargin = $settlement['revenue']['total_revenue'] > 0
            ? ($settlement['settlement']['net_amount'] / $settlement['revenue']['total_revenue']) * 100
            : 0;

        if ($profitMargin >= 20) return 'excellent';
        if ($profitMargin >= 10) return 'good';
        if ($profitMargin >= 0) return 'fair';
        return 'poor';
    }

    private function calculateAveragePaymentTime(Carbon $startDate, Carbon $endDate): float
    {
        return 24.5; // days
    }

    private function calculateCustomerLifetimeValue(array $customers): float
    {
        return collect($customers)->avg(function ($customer) {
            return $customer['total_billed'] * 1.5; // Simplified CLV calculation
        });
    }

    private function isFavorableVariance(string $category, ?string $item, float $variance): bool
    {
        // Revenue increases are favorable
        if (str_contains($category, 'revenue') || $category === 'total_revenue') {
            return $variance > 0;
        }

        // Expense decreases are favorable
        if (str_contains($category, 'expense') || str_contains($category, 'cost')) {
            return $variance < 0;
        }

        // Profit increases are favorable
        if (str_contains($category, 'profit') || str_contains($category, 'income')) {
            return $variance > 0;
        }

        return $variance > 0; // Default assumption
    }

    private function identifySignificantVariances(array $variances): array
    {
        $significant = [];

        foreach ($variances as $category => $data) {
            if (is_array($data)) {
                foreach ($data as $item => $values) {
                    if (abs($values['variance_percent']) >= 10) { // 10% threshold
                        $significant[] = [
                            'category' => $category,
                            'item' => $item,
                            'variance_percent' => $values['variance_percent'],
                            'impact' => $this->assessVarianceImpact($values['variance_percent']),
                        ];
                    }
                }
            } else {
                if (abs($data['variance_percent']) >= 10) {
                    $significant[] = [
                        'category' => $category,
                        'variance_percent' => $data['variance_percent'],
                        'impact' => $this->assessVarianceImpact($data['variance_percent']),
                    ];
                }
            }
        }

        return $significant;
    }

    private function assessVarianceImpact(float $variancePercent): string
    {
        $absVariance = abs($variancePercent);

        if ($absVariance >= 25) return 'critical';
        if ($absVariance >= 15) return 'high';
        if ($absVariance >= 10) return 'medium';
        return 'low';
    }

    private function calculateConfidenceIntervals(array $forecastData): array
    {
        // Simplified confidence interval calculation
        return collect($forecastData)->map(function ($data) {
            $baseValue = $data['revenue'];
            return [
                'lower_bound' => $baseValue * 0.9,
                'expected' => $baseValue,
                'upper_bound' => $baseValue * 1.1,
                'confidence_level' => 85,
            ];
        })->toArray();
    }

    private function identifyKeyHighlights(array $analytics): array
    {
        $highlights = [];

        if ($analytics['profitability_analysis']['profit_margin'] > 20) {
            $highlights[] = 'Strong profit margin of ' . number_format($analytics['profitability_analysis']['profit_margin'], 1) . '%';
        }

        if ($analytics['revenue_analytics']['collection_rate'] > 95) {
            $highlights[] = 'Excellent collection rate of ' . number_format($analytics['revenue_analytics']['collection_rate'], 1) . '%';
        }

        return $highlights;
    }

    private function identifyRiskIndicators(array $analytics): array
    {
        $risks = [];

        if ($analytics['cash_flow_metrics']['net_cash_flow'] < 0) {
            $risks[] = 'Negative cash flow of $' . number_format(abs($analytics['cash_flow_metrics']['net_cash_flow']), 0);
        }

        if ($analytics['profitability_analysis']['profit_margin'] < 5) {
            $risks[] = 'Low profit margin of ' . number_format($analytics['profitability_analysis']['profit_margin'], 1) . '%';
        }

        return $risks;
    }

    private function generateExecutiveRecommendations(array $analytics): array
    {
        $recommendations = [];

        if ($analytics['revenue_analytics']['collection_rate'] < 90) {
            $recommendations[] = 'Implement stricter credit control and collection procedures';
        }

        if ($analytics['profitability_analysis']['profit_margin'] < 15) {
            $recommendations[] = 'Review pricing strategy and cost optimization opportunities';
        }

        return $recommendations;
    }

    private function generateChartData(array $report): array
    {
        return [
            'revenue_trend' => [
                'labels' => $report['income_statement']['revenue'], // Simplified
                'data' => array_values($report['income_statement']['revenue']),
            ],
            'profit_trend' => [
                'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                'data' => [15000, 18000, 22000, 25000],
            ],
            'expense_breakdown' => [
                'labels' => array_keys($report['income_statement']['operating_expenses']),
                'data' => array_values($report['income_statement']['operating_expenses']),
            ],
        ];
    }
}