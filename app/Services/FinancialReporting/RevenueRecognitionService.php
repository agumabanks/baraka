<?php

namespace App\Services\FinancialReporting;

use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactShipment;
use App\Models\Financial\RevenueRecognition;
use App\Models\Financial\CODCollection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueRecognitionService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const FORECAST_MONTHS = 12;

    /**
     * Calculate and track real-time revenue recognition with accrual calculations
     */
    public function calculateRevenueRecognition(array $filters = []): array
    {
        try {
            $query = FactFinancialTransaction::revenue()
                ->with(['shipment', 'client', 'transactionDate'])
                ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                    $q->byDateRange($dateRange['start'], $dateRange['end']);
                })
                ->when($filters['client_key'] ?? false, function ($q, $clientKey) {
                    $q->byClient($clientKey);
                });

            $transactions = $query->get();
            
            $results = [
                'total_revenue' => 0,
                'recognized_revenue' => 0,
                'deferred_revenue' => 0,
                'recognition_rate' => 0,
                'pending_recognitions' => [],
                'overdue_recognitions' => [],
                'revenue_by_period' => [],
                'revenue_forecast' => []
            ];

            foreach ($transactions as $transaction) {
                $recognition = $this->getOrCreateRevenueRecognition($transaction);
                $results['total_revenue'] += $transaction->amount;
                $results['recognized_revenue'] += $recognition->recognized_revenue;
                $results['deferred_revenue'] += $recognition->deferred_revenue;

                if (!$recognition->is_fully_recognized) {
                    $results['pending_recognitions'][] = [
                        'shipment_key' => $transaction->shipment_key,
                        'amount' => $recognition->deferred_revenue,
                        'method' => $recognition->recognition_method,
                        'due_date' => $recognition->service_completion_date
                    ];
                }

                if ($recognition->isOverdue()) {
                    $results['overdue_recognitions'][] = [
                        'shipment_key' => $transaction->shipment_key,
                        'amount' => $recognition->deferred_revenue,
                        'days_overdue' => $recognition->getDaysOverdue()
                    ];
                }
            }

            $results['recognition_rate'] = $results['total_revenue'] > 0 
                ? ($results['recognized_revenue'] / $results['total_revenue']) * 100 
                : 0;

            $results['revenue_by_period'] = $this->getRevenueByPeriod($filters);
            $results['revenue_forecast'] = $this->generateRevenueForecast($filters);

            return $results;

        } catch (\Exception $e) {
            Log::error('Revenue recognition calculation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate accrual-based revenue recognition
     */
    public function calculateAccrualRevenue(array $filters = []): array
    {
        try {
            $query = FactFinancialTransaction::revenue()
                ->with(['shipment', 'client'])
                ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                    $q->byDateRange($dateRange['start'], $dateRange['end']);
                });

            $transactions = $query->get();
            
            $accrualResults = [
                'accrued_revenue' => 0,
                'accrued_expenses' => 0,
                'net_accrual' => 0,
                'accruals_by_category' => [],
                'accruals_by_service_type' => [],
                'accrual_journal_entries' => []
            ];

            foreach ($transactions as $transaction) {
                $shipment = $transaction->shipment;
                if (!$shipment) continue;

                $accrualAmount = $this->calculateAccrualAmount($transaction, $shipment);
                $accrualResults['accrued_revenue'] += $accrualAmount['revenue'];
                $accrualResults['accrued_expenses'] += $accrualAmount['expenses'];
                
                // Categorize accruals
                $category = $transaction->category ?? 'general';
                $accrualResults['accruals_by_category'][$category] = 
                    ($accrualResults['accruals_by_category'][$category] ?? 0) + abs($accrualAmount['net']);
            }

            $accrualResults['net_accrual'] = $accrualResults['accrued_revenue'] - $accrualResults['accrued_expenses'];
            $accrualResults['accrual_journal_entries'] = $this->generateAccrualJournalEntries($accrualResults);

            return $accrualResults;

        } catch (\Exception $e) {
            Log::error('Accrual revenue calculation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate revenue forecasting based on historical data
     */
    public function generateRevenueForecast(array $filters = [], int $months = self::FORECAST_MONTHS): array
    {
        try {
            // Get historical data for forecasting
            $historicalData = $this->getHistoricalRevenueData($filters);
            
            $forecast = [];
            $trend = $this->calculateRevenueTrend($historicalData);
            $seasonality = $this->calculateSeasonality($historicalData);
            
            $lastPeriod = end($historicalData);
            $baseAmount = $lastPeriod['amount'] ?? 0;
            $baseDate = Carbon::parse($lastPeriod['date'] ?? now());
            
            for ($i = 1; $i <= $months; $i++) {
                $forecastDate = $baseDate->copy()->addMonths($i);
                $trendAmount = $baseAmount + ($trend * $i);
                $seasonalAmount = $trendAmount * $seasonality[$forecastDate->format('n')] ?? 1;
                
                $forecast[] = [
                    'period' => $forecastDate->format('Y-m'),
                    'forecasted_revenue' => round($seasonalAmount, 2),
                    'confidence_level' => max(0.6, 1 - ($i * 0.03)), // Decreasing confidence
                    'upper_bound' => round($seasonalAmount * 1.1, 2),
                    'lower_bound' => round($seasonalAmount * 0.9, 2)
                ];
            }
            
            return $forecast;
            
        } catch (\Exception $e) {
            Log::error('Revenue forecast generation error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Process revenue recognition for a specific transaction
     */
    public function processRevenueRecognition(string $transactionKey): bool
    {
        try {
            $transaction = FactFinancialTransaction::find($transactionKey);
            if (!$transaction || !$transaction->isRevenue()) {
                return false;
            }

            $recognition = $this->getOrCreateRevenueRecognition($transaction);
            
            // Determine recognition amount based on method
            $recognitionAmount = $this->calculateRecognitionAmount($recognition);
            
            if ($recognitionAmount > 0) {
                $recognition->updateRecognition($recognitionAmount, 'Automated recognition');
                $this->logRevenueRecognition($recognition, $recognitionAmount);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Revenue recognition processing error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get deferred revenue analysis
     */
    public function getDeferredRevenueAnalysis(array $filters = []): array
    {
        try {
            $query = RevenueRecognition::deferred()
                ->with(['client', 'shipment'])
                ->when($filters['client_key'] ?? false, function ($q, $clientKey) {
                    $q->where('client_key', $clientKey);
                })
                ->when($filters['service_type'] ?? false, function ($q, $serviceType) {
                    $q->where('service_type', $serviceType);
                });

            $deferredRecords = $query->get();
            
            $analysis = [
                'total_deferred' => 0,
                'deferred_by_client' => [],
                'deferred_by_service_type' => [],
                'deferred_by_time_period' => [],
                'aging_analysis' => []
            ];

            foreach ($deferredRecords as $record) {
                $analysis['total_deferred'] += $record->deferred_revenue;
                
                $clientName = $record->client->client_name ?? 'Unknown';
                $analysis['deferred_by_client'][$clientName] = 
                    ($analysis['deferred_by_client'][$clientName] ?? 0) + $record->deferred_revenue;
                
                $serviceType = $record->service_type ?? 'general';
                $analysis['deferred_by_service_type'][$serviceType] = 
                    ($analysis['deferred_by_service_type'][$serviceType] ?? 0) + $record->deferred_revenue;
                
                // Time period analysis
                $timeBucket = $this->getTimeBucket($record->recognition_period_end);
                $analysis['deferred_by_time_period'][$timeBucket] = 
                    ($analysis['deferred_by_time_period'][$timeBucket] ?? 0) + $record->deferred_revenue;
            }
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error('Deferred revenue analysis error: ' . $e->getMessage());
            return [];
        }
    }

    // Private helper methods

    private function getOrCreateRevenueRecognition(FactFinancialTransaction $transaction): RevenueRecognition
    {
        return RevenueRecognition::firstOrCreate(
            ['transaction_key' => $transaction->transaction_key],
            [
                'shipment_key' => $transaction->shipment_key,
                'client_key' => $transaction->client_key,
                'total_revenue' => $transaction->amount,
                'recognized_revenue' => 0,
                'deferred_revenue' => $transaction->amount,
                'recognition_method' => $this->determineRecognitionMethod($transaction),
                'service_completion_date' => $this->determineServiceCompletionDate($transaction),
                'revenue_stream' => $transaction->category ?? 'general',
                'service_type' => $transaction->subcategory ?? 'standard'
            ]
        );
    }

    private function determineRecognitionMethod(FactFinancialTransaction $transaction): string
    {
        $category = $transaction->category ?? '';
        
        return match($category) {
            'express' => RevenueRecognition::METHOD_POINT_IN_TIME,
            'standard' => RevenueRecognition::METHOD_STRAIGHT_LINE,
            'subscription' => RevenueRecognition::METHOD_OVER_TIME,
            'milestone' => RevenueRecognition::METHOD_MILESTONE,
            default => RevenueRecognition::METHOD_PERCENTAGE_COMPLETE
        };
    }

    private function determineServiceCompletionDate(FactFinancialTransaction $transaction): Carbon
    {
        $shipment = $transaction->shipment;
        if ($shipment && $shipment->delivery_date) {
            return Carbon::parse($shipment->delivery_date);
        }
        
        return now()->addDays(1); // Default to next day
    }

    private function calculateAccrualAmount(FactFinancialTransaction $transaction, FactShipment $shipment): array
    {
        $revenue = $transaction->amount;
        $expenses = $shipment->total_cost ?? 0;
        
        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => $revenue - $expenses
        ];
    }

    private function calculateRecognitionAmount(RevenueRecognition $recognition): float
    {
        $remainingAmount = $recognition->getRemainingAmount();
        
        return match($recognition->recognition_method) {
            RevenueRecognition::METHOD_POINT_IN_TIME => $remainingAmount,
            RevenueRecognition::METHOD_STRAIGHT_LINE => $remainingAmount / max(1, $recognition->remaining_periods),
            RevenueRecognition::METHOD_PERCENTAGE_COMPLETE => min($remainingAmount, $recognition->total_revenue * 0.1), // 10% chunks
            default => $remainingAmount
        };
    }

    private function getHistoricalRevenueData(array $filters): array
    {
        $query = FactFinancialTransaction::revenue()
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as amount')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->limit(24); // Last 24 periods

        return $query->get()->toArray();
    }

    private function calculateRevenueTrend(array $historicalData): float
    {
        if (count($historicalData) < 2) {
            return 0;
        }

        $n = count($historicalData);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($historicalData as $index => $data) {
            $x = $index + 1;
            $y = $data['amount'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }

    private function calculateSeasonality(array $historicalData): array
    {
        $monthlyAverages = array_fill(1, 12, 0);
        $monthlyCounts = array_fill(1, 12, 0);
        $overallAverage = 0;

        foreach ($historicalData as $data) {
            $month = Carbon::parse($data['date'])->format('n');
            $monthlyAverages[$month] += $data['amount'];
            $monthlyCounts[$month]++;
            $overallAverage += $data['amount'];
        }

        $overallAverage /= count($historicalData);

        $seasonality = [];
        for ($month = 1; $month <= 12; $month++) {
            if ($monthlyCounts[$month] > 0) {
                $seasonality[$month] = $monthlyAverages[$month] / $monthlyCounts[$month] / $overallAverage;
            } else {
                $seasonality[$month] = 1;
            }
        }

        return $seasonality;
    }

    private function getTimeBucket(?Carbon $date): string
    {
        if (!$date) return 'unknown';
        
        $now = now();
        $daysDiff = $now->diffInDays($date);
        
        return match(true) {
            $daysDiff <= 30 => '0-30 days',
            $daysDiff <= 90 => '31-90 days',
            $daysDiff <= 180 => '91-180 days',
            default => '180+ days'
        };
    }

    private function logRevenueRecognition(RevenueRecognition $recognition, float $amount): void
    {
        Log::info('Revenue recognition processed', [
            'transaction_key' => $recognition->transaction_key,
            'recognition_amount' => $amount,
            'remaining_deferred' => $recognition->deferred_revenue,
            'recognition_rate' => $recognition->calculateRecognitionRate()
        ]);
    }

    private function getRevenueByPeriod(array $filters): array
    {
        $query = FactFinancialTransaction::revenue()
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as period, SUM(amount) as total')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->limit(12);

        return $query->get()->toArray();
    }

    private function generateAccrualJournalEntries(array $accrualResults): array
    {
        return [
            [
                'entry_type' => 'revenue_accrual',
                'debit_account' => 'Accounts Receivable',
                'credit_account' => 'Unearned Revenue',
                'amount' => $accrualResults['accrued_revenue'],
                'description' => 'Accrued revenue recognition'
            ],
            [
                'entry_type' => 'expense_accrual',
                'debit_account' => 'Cost of Goods Sold',
                'credit_account' => 'Accrued Expenses',
                'amount' => $accrualResults['accrued_expenses'],
                'description' => 'Accrued expense recognition'
            ]
        ];
    }
}