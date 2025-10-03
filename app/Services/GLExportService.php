<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\Backend\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GLExportService
{
    /**
     * Chart of accounts mapping
     */
    private array $chartOfAccounts = [
        'revenue' => [
            'shipping_revenue' => '4000-001',
            'cod_revenue' => '4000-002',
            'fuel_surcharge_revenue' => '4000-003',
            'special_handling_revenue' => '4000-004',
        ],
        'expenses' => [
            'worker_salaries' => '5000-001',
            'vehicle_maintenance' => '5000-002',
            'fuel_expenses' => '5000-003',
            'facility_rent' => '5000-004',
            'insurance' => '5000-005',
            'depreciation' => '5000-006',
        ],
        'assets' => [
            'accounts_receivable' => '1000-001',
            'cash' => '1000-002',
            'inventory' => '1000-003',
            'fixed_assets' => '1000-004',
        ],
        'liabilities' => [
            'accounts_payable' => '2000-001',
            'accrued_expenses' => '2000-002',
            'loans_payable' => '2000-003',
        ],
        'equity' => [
            'retained_earnings' => '3000-001',
            'owner_equity' => '3000-002',
        ],
    ];

    /**
     * Generate GL entries for shipments
     */
    public function generateShipmentGLEntries(Collection $shipments): array
    {
        $glEntries = [];

        foreach ($shipments as $shipment) {
            $entries = $this->generateSingleShipmentGLEntries($shipment);
            $glEntries = array_merge($glEntries, $entries);
        }

        return [
            'entries' => $glEntries,
            'summary' => $this->summarizeGLEntries($glEntries),
            'period' => now()->format('Y-m'),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate GL entries for a single shipment
     */
    private function generateSingleShipmentGLEntries(Shipment $shipment): array
    {
        $entries = [];
        $rateService = app(RateCardManagementService::class);
        $rateCalculation = $rateService->calculateShippingRate($shipment);

        $transactionDate = $shipment->delivered_at ?? $shipment->created_at;
        $reference = "SHIP-{$shipment->id}";

        // Revenue entries
        if ($rateCalculation['base_rate'] > 0) {
            $entries[] = [
                'date' => $transactionDate->toDateString(),
                'reference' => $reference,
                'description' => "Shipping revenue - {$shipment->tracking_number}",
                'account_code' => $this->chartOfAccounts['revenue']['shipping_revenue'],
                'account_name' => 'Shipping Revenue',
                'debit' => 0,
                'credit' => $rateCalculation['base_rate'],
                'branch_id' => $shipment->origin_branch_id,
                'shipment_id' => $shipment->id,
                'entry_type' => 'revenue',
            ];
        }

        // Fuel surcharge revenue
        if ($rateCalculation['fuel_surcharge'] > 0) {
            $entries[] = [
                'date' => $transactionDate->toDateString(),
                'reference' => $reference,
                'description' => "Fuel surcharge - {$shipment->tracking_number}",
                'account_code' => $this->chartOfAccounts['revenue']['fuel_surcharge_revenue'],
                'account_name' => 'Fuel Surcharge Revenue',
                'debit' => 0,
                'credit' => $rateCalculation['fuel_surcharge'],
                'branch_id' => $shipment->origin_branch_id,
                'shipment_id' => $shipment->id,
                'entry_type' => 'revenue',
            ];
        }

        // Special handling revenue
        if ($rateCalculation['surcharges']['special_handling'] > 0) {
            $entries[] = [
                'date' => $transactionDate->toDateString(),
                'reference' => $reference,
                'description' => "Special handling - {$shipment->tracking_number}",
                'account_code' => $this->chartOfAccounts['revenue']['special_handling_revenue'],
                'account_name' => 'Special Handling Revenue',
                'debit' => 0,
                'credit' => $rateCalculation['surcharges']['special_handling'],
                'branch_id' => $shipment->origin_branch_id,
                'shipment_id' => $shipment->id,
                'entry_type' => 'revenue',
            ];
        }

        // COD revenue
        $codCollected = $shipment->codCollections()->sum('collected_amount');
        if ($codCollected > 0) {
            $entries[] = [
                'date' => $transactionDate->toDateString(),
                'reference' => $reference,
                'description' => "COD collection - {$shipment->tracking_number}",
                'account_code' => $this->chartOfAccounts['revenue']['cod_revenue'],
                'account_name' => 'COD Revenue',
                'debit' => 0,
                'credit' => $codCollected,
                'branch_id' => $shipment->dest_branch_id,
                'shipment_id' => $shipment->id,
                'entry_type' => 'revenue',
            ];

            // Corresponding cash entry
            $entries[] = [
                'date' => $transactionDate->toDateString(),
                'reference' => $reference,
                'description' => "COD cash received - {$shipment->tracking_number}",
                'account_code' => $this->chartOfAccounts['assets']['cash'],
                'account_name' => 'Cash',
                'debit' => $codCollected,
                'credit' => 0,
                'branch_id' => $shipment->dest_branch_id,
                'shipment_id' => $shipment->id,
                'entry_type' => 'asset',
            ];
        }

        // Accounts receivable (if not paid)
        $totalRevenue = $rateCalculation['grand_total'];
        if (!$shipment->invoice || $shipment->invoice->status !== 'paid') {
            $entries[] = [
                'date' => $transactionDate->toDateString(),
                'reference' => $reference,
                'description' => "Accounts receivable - {$shipment->tracking_number}",
                'account_code' => $this->chartOfAccounts['assets']['accounts_receivable'],
                'account_name' => 'Accounts Receivable',
                'debit' => $totalRevenue,
                'credit' => 0,
                'branch_id' => $shipment->origin_branch_id,
                'shipment_id' => $shipment->id,
                'entry_type' => 'asset',
            ];
        }

        return $entries;
    }

    /**
     * Generate GL entries for operational expenses
     */
    public function generateOperationalExpenseEntries(Branch $branch, Carbon $startDate, Carbon $endDate): array
    {
        $entries = [];
        $reference = "OPS-{$branch->id}-" . $startDate->format('Ym');

        // Worker salaries
        $workerCosts = $this->calculateWorkerCosts($branch, $startDate, $endDate);
        if ($workerCosts > 0) {
            $entries[] = [
                'date' => $endDate->toDateString(),
                'reference' => $reference,
                'description' => "Worker salaries - {$branch->name}",
                'account_code' => $this->chartOfAccounts['expenses']['worker_salaries'],
                'account_name' => 'Worker Salaries',
                'debit' => $workerCosts,
                'credit' => 0,
                'branch_id' => $branch->id,
                'entry_type' => 'expense',
            ];
        }

        // Vehicle maintenance and fuel
        $vehicleCosts = $this->calculateVehicleCosts($branch, $startDate, $endDate);
        if ($vehicleCosts > 0) {
            $entries[] = [
                'date' => $endDate->toDateString(),
                'reference' => $reference,
                'description' => "Vehicle maintenance & fuel - {$branch->name}",
                'account_code' => $this->chartOfAccounts['expenses']['vehicle_maintenance'],
                'account_name' => 'Vehicle Maintenance',
                'debit' => $vehicleCosts,
                'account_name' => 'Vehicle Maintenance & Fuel',
                'debit' => $vehicleCosts,
                'credit' => 0,
                'branch_id' => $branch->id,
                'entry_type' => 'expense',
            ];
        }

        // Facility rent
        $facilityCosts = $this->calculateFacilityCosts($branch, $startDate, $endDate);
        if ($facilityCosts > 0) {
            $entries[] = [
                'date' => $endDate->toDateString(),
                'reference' => $reference,
                'description' => "Facility rent - {$branch->name}",
                'account_code' => $this->chartOfAccounts['expenses']['facility_rent'],
                'account_name' => 'Facility Rent',
                'debit' => $facilityCosts,
                'credit' => 0,
                'branch_id' => $branch->id,
                'entry_type' => 'expense',
            ];
        }

        return $entries;
    }

    /**
     * Generate GL entries for settlements
     */
    public function generateSettlementGLEntries(Collection $settlements, string $settlementType): array
    {
        $entries = [];

        foreach ($settlements as $settlement) {
            if ($settlementType === 'branch') {
                $entries = array_merge($entries, $this->generateBranchSettlementGLEntries($settlement));
            } elseif ($settlementType === 'worker') {
                $entries = array_merge($entries, $this->generateWorkerSettlementGLEntries($settlement));
            }
        }

        return $entries;
    }

    /**
     * Generate GL entries for branch settlement
     */
    private function generateBranchSettlementGLEntries($settlement): array
    {
        $entries = [];
        $reference = "BR-SETT-{$settlement->id}";
        $paymentDate = $settlement->payment_date ?? now();

        if ($settlement->net_amount > 0) {
            // Debit cash/accounts payable
            $entries[] = [
                'date' => $paymentDate->toDateString(),
                'reference' => $reference,
                'description' => "Branch settlement payment - {$settlement->branch->name}",
                'account_code' => $this->chartOfAccounts['assets']['cash'],
                'account_name' => 'Cash',
                'debit' => $settlement->net_amount,
                'credit' => 0,
                'branch_id' => $settlement->branch_id,
                'entry_type' => 'asset',
            ];

            // Credit revenue or debit expense
            $accountType = $settlement->net_amount > 0 ? 'revenue' : 'expense';
            $entries[] = [
                'date' => $paymentDate->toDateString(),
                'reference' => $reference,
                'description' => "Branch settlement - {$settlement->branch->name}",
                'account_code' => $this->chartOfAccounts[$accountType]['shipping_revenue'],
                'account_name' => 'Branch Settlements',
                'debit' => 0,
                'credit' => $settlement->net_amount,
                'branch_id' => $settlement->branch_id,
                'entry_type' => $accountType,
            ];
        }

        return $entries;
    }

    /**
     * Generate GL entries for worker settlement
     */
    private function generateWorkerSettlementGLEntries($settlement): array
    {
        $entries = [];
        $reference = "WR-SETT-{$settlement->id}";
        $paymentDate = $settlement->payment_date ?? now();

        // Debit salary expense
        $entries[] = [
            'date' => $paymentDate->toDateString(),
            'reference' => $reference,
            'description' => "Worker salary payment - {$settlement->worker->full_name}",
            'account_code' => $this->chartOfAccounts['expenses']['worker_salaries'],
            'account_name' => 'Worker Salaries',
            'debit' => $settlement->net_amount,
            'credit' => 0,
            'branch_id' => $settlement->worker->branch_id,
            'entry_type' => 'expense',
        ];

        // Credit cash
        $entries[] = [
            'date' => $paymentDate->toDateString(),
            'reference' => $reference,
            'description' => "Worker salary payment - {$settlement->worker->full_name}",
            'account_code' => $this->chartOfAccounts['assets']['cash'],
            'account_name' => 'Cash',
            'debit' => 0,
            'credit' => $settlement->net_amount,
            'branch_id' => $settlement->worker->branch_id,
            'entry_type' => 'asset',
        ];

        return $entries;
    }

    /**
     * Calculate worker costs for GL
     */
    private function calculateWorkerCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        $activeWorkers = $branch->activeWorkers()->get();
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;

        return $activeWorkers->sum(function ($worker) use ($daysInPeriod) {
            $dailyRate = ($worker->hourly_rate ?? 15) * 8;
            return $dailyRate * $daysInPeriod;
        });
    }

    /**
     * Calculate vehicle costs for GL
     */
    private function calculateVehicleCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        $vehicleCount = 5; // Assume 5 vehicles per branch
        $dailyVehicleCost = 75; // $75 per vehicle per day (maintenance + fuel)
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;

        return $vehicleCount * $dailyVehicleCost * $daysInPeriod;
    }

    /**
     * Calculate facility costs for GL
     */
    private function calculateFacilityCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        $dailyFacilityCost = 300; // $300 per day for facility operations
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;

        return $dailyFacilityCost * $daysInPeriod;
    }

    /**
     * Summarize GL entries
     */
    private function summarizeGLEntries(array $entries): array
    {
        $summary = [
            'total_debits' => 0,
            'total_credits' => 0,
            'entry_count' => count($entries),
            'accounts_affected' => [],
        ];

        foreach ($entries as $entry) {
            $summary['total_debits'] += $entry['debit'];
            $summary['total_credits'] += $entry['credit'];

            $accountCode = $entry['account_code'];
            if (!isset($summary['accounts_affected'][$accountCode])) {
                $summary['accounts_affected'][$accountCode] = [
                    'account_name' => $entry['account_name'],
                    'debit_total' => 0,
                    'credit_total' => 0,
                    'entry_count' => 0,
                ];
            }

            $summary['accounts_affected'][$accountCode]['debit_total'] += $entry['debit'];
            $summary['accounts_affected'][$accountCode]['credit_total'] += $entry['credit'];
            $summary['accounts_affected'][$accountCode]['entry_count']++;
        }

        $summary['is_balanced'] = abs($summary['total_debits'] - $summary['total_credits']) < 0.01;

        return $summary;
    }

    /**
     * Export GL entries to CSV format
     */
    public function exportToCSV(array $glData): string
    {
        $csvContent = "Date,Reference,Description,Account Code,Account Name,Debit,Credit,Branch ID,Entry Type\n";

        foreach ($glData['entries'] as $entry) {
            $csvContent .= sprintf(
                "%s,%s,%s,%s,%s,%.2f,%.2f,%s,%s\n",
                $entry['date'],
                $entry['reference'],
                $entry['description'],
                $entry['account_code'],
                $entry['account_name'],
                $entry['debit'],
                $entry['credit'],
                $entry['branch_id'] ?? '',
                $entry['entry_type']
            );
        }

        return $csvContent;
    }

    /**
     * Export GL entries to QuickBooks format
     */
    public function exportToQuickBooks(array $glData): array
    {
        $qbEntries = [];

        foreach ($glData['entries'] as $entry) {
            $qbEntries[] = [
                'Date' => $entry['date'],
                'RefNumber' => $entry['reference'],
                'Memo' => $entry['description'],
                'Account' => $entry['account_name'],
                'Debit' => $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '',
                'Credit' => $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '',
            ];
        }

        return $qbEntries;
    }

    /**
     * Generate trial balance
     */
    public function generateTrialBalance(Carbon $startDate, Carbon $endDate): array
    {
        // Get all GL entries for the period
        $entries = $this->getGLEntriesForPeriod($startDate, $endDate);

        $trialBalance = [];

        foreach ($entries as $entry) {
            $accountCode = $entry['account_code'];

            if (!isset($trialBalance[$accountCode])) {
                $trialBalance[$accountCode] = [
                    'account_code' => $accountCode,
                    'account_name' => $entry['account_name'],
                    'debit_balance' => 0,
                    'credit_balance' => 0,
                ];
            }

            $trialBalance[$accountCode]['debit_balance'] += $entry['debit'];
            $trialBalance[$accountCode]['credit_balance'] += $entry['credit'];
        }

        // Calculate net balances
        foreach ($trialBalance as &$account) {
            $account['net_balance'] = $account['debit_balance'] - $account['credit_balance'];
            $account['balance_type'] = $account['net_balance'] >= 0 ? 'debit' : 'credit';
            $account['balance_amount'] = abs($account['net_balance']);
        }

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'trial_balance' => array_values($trialBalance),
            'totals' => [
                'total_debits' => collect($trialBalance)->sum('debit_balance'),
                'total_credits' => collect($trialBalance)->sum('credit_balance'),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get GL entries for a period (mock implementation)
     */
    private function getGLEntriesForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        // In a real implementation, this would query the GL entries table
        // For now, return sample data
        return [
            [
                'account_code' => '4000-001',
                'account_name' => 'Shipping Revenue',
                'debit' => 0,
                'credit' => 50000,
            ],
            [
                'account_code' => '5000-001',
                'account_name' => 'Worker Salaries',
                'debit' => 25000,
                'credit' => 0,
            ],
            [
                'account_code' => '1000-002',
                'account_name' => 'Cash',
                'debit' => 20000,
                'credit' => 0,
            ],
        ];
    }

    /**
     * Validate GL entries for balance
     */
    public function validateGLEntries(array $entries): array
    {
        $totalDebits = collect($entries)->sum('debit');
        $totalCredits = collect($entries)->sum('credit');

        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;

        $issues = [];

        if (!$isBalanced) {
            $issues[] = [
                'type' => 'imbalance',
                'message' => sprintf(
                    'GL entries are not balanced. Debits: %.2f, Credits: %.2f, Difference: %.2f',
                    $totalDebits,
                    $totalCredits,
                    abs($totalDebits - $totalCredits)
                ),
            ];
        }

        // Check for entries without proper accounts
        $invalidAccounts = collect($entries)->filter(function ($entry) {
            return !isset($this->chartOfAccounts[$entry['entry_type']][$entry['account_code']]);
        });

        if ($invalidAccounts->count() > 0) {
            $issues[] = [
                'type' => 'invalid_accounts',
                'message' => 'Some entries reference invalid account codes',
                'count' => $invalidAccounts->count(),
            ];
        }

        return [
            'is_valid' => empty($issues),
            'is_balanced' => $isBalanced,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'issues' => $issues,
        ];
    }

    /**
     * Generate financial statements
     */
    public function generateFinancialStatements(Carbon $startDate, Carbon $endDate): array
    {
        $trialBalance = $this->generateTrialBalance($startDate, $endDate);

        // Income Statement
        $incomeStatement = $this->generateIncomeStatement($trialBalance);

        // Balance Sheet
        $balanceSheet = $this->generateBalanceSheet($trialBalance);

        return [
            'period' => $trialBalance['period'],
            'income_statement' => $incomeStatement,
            'balance_sheet' => $balanceSheet,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate income statement
     */
    private function generateIncomeStatement(array $trialBalance): array
    {
        $revenues = [];
        $expenses = [];

        foreach ($trialBalance['trial_balance'] as $account) {
            if (str_starts_with($account['account_code'], '4')) { // Revenue accounts
                $revenues[] = $account;
            } elseif (str_starts_with($account['account_code'], '5')) { // Expense accounts
                $expenses[] = $account;
            }
        }

        $totalRevenue = collect($revenues)->sum('credit_balance');
        $totalExpenses = collect($expenses)->sum('debit_balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    /**
     * Generate balance sheet
     */
    private function generateBalanceSheet(array $trialBalance): array
    {
        $assets = [];
        $liabilities = [];
        $equity = [];

        foreach ($trialBalance['trial_balance'] as $account) {
            if (str_starts_with($account['account_code'], '1')) { // Asset accounts
                $assets[] = $account;
            } elseif (str_starts_with($account['account_code'], '2')) { // Liability accounts
                $liabilities[] = $account;
            } elseif (str_starts_with($account['account_code'], '3')) { // Equity accounts
                $equity[] = $account;
            }
        }

        $totalAssets = collect($assets)->sum(function ($account) {
            return $account['debit_balance'] - $account['credit_balance'];
        });

        $totalLiabilities = collect($liabilities)->sum(function ($account) {
            return $account['credit_balance'] - $account['debit_balance'];
        });

        $totalEquity = collect($equity)->sum(function ($account) {
            return $account['credit_balance'] - $account['debit_balance'];
        });

        return [
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'balances' => $totalAssets == ($totalLiabilities + $totalEquity),
        ];
    }
}