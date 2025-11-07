<?php

namespace App\Services\FinancialReporting;

use App\Models\Financial\PaymentProcessing;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\Backend\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class PaymentProcessingService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const RECONCILIATION_THRESHOLD = 0.01; // 1 cent threshold for reconciliation
    private const SETTLEMENT_BATCH_SIZE = 100;

    /**
     * Manage payment processing workflow with reconciliation status
     */
    public function managePaymentProcessing(array $filters = []): array
    {
        try {
            $payments = $this->getPaymentProcessingData($filters);
            
            $processingData = [
                'workflow_summary' => [
                    'total_payments' => 0,
                    'total_amount' => 0,
                    'processing_fee' => 0,
                    'net_amount' => 0,
                    'success_rate' => 0
                ],
                'workflow_status' => [
                    'pending' => ['count' => 0, 'amount' => 0],
                    'processing' => ['count' => 0, 'amount' => 0],
                    'completed' => ['count' => 0, 'amount' => 0],
                    'failed' => ['count' => 0, 'amount' => 0],
                    'disputed' => ['count' => 0, 'amount' => 0]
                ],
                'payment_methods' => [
                    'credit_card' => ['count' => 0, 'amount' => 0, 'success_rate' => 0],
                    'debit_card' => ['count' => 0, 'amount' => 0, 'success_rate' => 0],
                    'bank_transfer' => ['count' => 0, 'amount' => 0, 'success_rate' => 0],
                    'digital_wallet' => ['count' => 0, 'amount' => 0, 'success_rate' => 0],
                    'cod' => ['count' => 0, 'amount' => 0, 'success_rate' => 0]
                ],
                'reconciliation_status' => [
                    'pending' => ['count' => 0, 'amount' => 0],
                    'reconciled' => ['count' => 0, 'amount' => 0],
                    'discrepancy' => ['count' => 0, 'amount' => 0],
                    'under_review' => ['count' => 0, 'amount' => 0]
                ],
                'gateways' => [
                    'stripe' => ['count' => 0, 'amount' => 0, 'fees' => 0],
                    'paypal' => ['count' => 0, 'amount' => 0, 'fees' => 0],
                    'square' => ['count' => 0, 'amount' => 0, 'fees' => 0],
                    'custom' => ['count' => 0, 'amount' => 0, 'fees' => 0]
                ],
                'processing_metrics' => [
                    'avg_processing_time' => 0,
                    'success_rate_by_method' => [],
                    'failure_analysis' => [],
                    'bottlenecks' => []
                ]
            ];

            foreach ($payments as $payment) {
                // Update workflow status
                $statusKey = $payment->payment_status;
                if (isset($processingData['workflow_status'][$statusKey])) {
                    $processingData['workflow_status'][$statusKey]['count']++;
                    $processingData['workflow_status'][$statusKey]['amount'] += $payment->payment_amount;
                }

                // Update payment methods
                $methodKey = $payment->payment_method;
                if (isset($processingData['payment_methods'][$methodKey])) {
                    $methodData = &$processingData['payment_methods'][$methodKey];
                    $methodData['count']++;
                    $methodData['amount'] += $payment->payment_amount;
                    $methodData['fees'] = $processingData['gateways'][$payment->gateway_id]['fees'] ?? 0;
                }

                // Update reconciliation status
                $reconKey = $payment->reconciliation_status;
                if (isset($processingData['reconciliation_status'][$reconKey])) {
                    $processingData['reconciliation_status'][$reconKey]['count']++;
                    $processingData['reconciliation_status'][$reconKey]['amount'] += $payment->net_amount;
                }

                // Update gateway metrics
                $gatewayKey = $payment->gateway_id ?? 'custom';
                if (isset($processingData['gateways'][$gatewayKey])) {
                    $gatewayData = &$processingData['gateways'][$gatewayKey];
                    $gatewayData['count']++;
                    $gatewayData['amount'] += $payment->payment_amount;
                    $gatewayData['fees'] += $payment->processing_fee;
                }
            }

            // Calculate summary metrics
            $processingData['workflow_summary'] = $this->calculateWorkflowSummary($payments);
            
            // Calculate success rates
            $processingData['payment_methods'] = $this->calculateSuccessRates(
                $processingData['payment_methods']
            );
            
            // Generate processing metrics
            $processingData['processing_metrics'] = $this->calculateProcessingMetrics($payments);
            
            // Analyze failures
            $processingData['processing_metrics']['failure_analysis'] = $this->analyzeFailures($payments);
            
            // Identify bottlenecks
            $processingData['processing_metrics']['bottlenecks'] = $this->identifyBottlenecks($payments);

            return $processingData;

        } catch (\Exception $e) {
            Log::error('Payment processing management error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Track reconciliation status and manage reconciliation process
     */
    public function trackReconciliationStatus(array $filters = []): array
    {
        try {
            $reconciliationData = [
                'reconciliation_summary' => [
                    'total_payments' => 0,
                    'reconciled_payments' => 0,
                    'pending_reconciliation' => 0,
                    'discrepancies' => 0,
                    'reconciliation_rate' => 0,
                    'avg_reconciliation_time' => 0
                ],
                'reconciliation_by_status' => [
                    'pending' => [],
                    'in_progress' => [],
                    'completed' => [],
                    'failed' => []
                ],
                'discrepancy_analysis' => [
                    'amount_discrepancies' => [],
                    'missing_transactions' => [],
                    'duplicate_transactions' => [],
                    'date_discrepancies' => []
                ],
                'reconciliation_trends' => [
                    'daily_trends' => [],
                    'weekly_trends' => [],
                    'monthly_trends' => []
                ],
                'reconciliation_exceptions' => [
                    'high_value_exceptions' => [],
                    'repeated_exceptions' => [],
                    'system_errors' => []
                ]
            ];

            $payments = $this->getPaymentProcessingData($filters);
            
            foreach ($payments as $payment) {
                if ($payment->needsReconciliation()) {
                    $reconciliationData['reconciliation_summary']['pending_reconciliation']++;
                }
                
                if ($payment->isReconciled()) {
                    $reconciliationData['reconciliation_summary']['reconciled_payments']++;
                }
                
                // Analyze discrepancies
                if ($payment->reconciliation_status === PaymentProcessing::RECONCILIATION_DISCREPANCY) {
                    $this->analyzeDiscrepancy($payment, $reconciliationData);
                }
            }

            // Calculate reconciliation rate
            $total = count($payments);
            $reconciled = $reconciliationData['reconciliation_summary']['reconciled_payments'];
            $reconciliationData['reconciliation_summary']['reconciliation_rate'] = $total > 0 
                ? ($reconciled / $total) * 100 
                : 0;

            // Process reconciliation exceptions
            $reconciliationData['reconciliation_exceptions'] = $this->processReconciliationExceptions($payments);
            
            // Generate reconciliation trends
            $reconciliationData['reconciliation_trends'] = $this->generateReconciliationTrends($filters);
            
            // Calculate average reconciliation time
            $reconciliationData['reconciliation_summary']['avg_reconciliation_time'] = 
                $this->calculateAverageReconciliationTime($payments);

            return $reconciliationData;

        } catch (\Exception $e) {
            Log::error('Reconciliation status tracking error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze payment method performance and optimization opportunities
     */
    public function analyzePaymentMethods(array $filters = []): array
    {
        try {
            $methodAnalysis = [
                'method_performance' => [
                    'credit_card' => [
                        'total_transactions' => 0,
                        'total_amount' => 0,
                        'success_rate' => 0,
                        'avg_processing_time' => 0,
                        'processing_fee_rate' => 0,
                        'chargeback_rate' => 0
                    ],
                    'debit_card' => [
                        'total_transactions' => 0,
                        'total_amount' => 0,
                        'success_rate' => 0,
                        'avg_processing_time' => 0,
                        'processing_fee_rate' => 0,
                        'chargeback_rate' => 0
                    ],
                    'bank_transfer' => [
                        'total_transactions' => 0,
                        'total_amount' => 0,
                        'success_rate' => 0,
                        'avg_processing_time' => 0,
                        'processing_fee_rate' => 0,
                        'chargeback_rate' => 0
                    ],
                    'digital_wallet' => [
                        'total_transactions' => 0,
                        'total_amount' => 0,
                        'success_rate' => 0,
                        'avg_processing_time' => 0,
                        'processing_fee_rate' => 0,
                        'chargeback_rate' => 0
                    ],
                    'cod' => [
                        'total_transactions' => 0,
                        'total_amount' => 0,
                        'success_rate' => 0,
                        'avg_processing_time' => 0,
                        'processing_fee_rate' => 0,
                        'chargeback_rate' => 0
                    ]
                ],
                'optimization_opportunities' => [],
                'cost_analysis' => [
                    'method_costs' => [],
                    'total_processing_costs' => 0,
                    'cost_per_transaction' => 0,
                    'cost_per_dollar' => 0
                ],
                'customer_preference' => [
                    'preferred_methods' => [],
                    'method_trends' => [],
                    'geographic_variations' => []
                ],
                'gateway_performance' => [
                    'gateway_comparison' => [],
                    'reliability_metrics' => [],
                    'cost_efficiency' => []
                ]
            ];

            $payments = $this->getPaymentProcessingData($filters);
            
            foreach ($payments as $payment) {
                $method = $payment->payment_method;
                if (!isset($methodAnalysis['method_performance'][$method])) {
                    continue;
                }
                
                $methodData = &$methodAnalysis['method_performance'][$method];
                $methodData['total_transactions']++;
                $methodData['total_amount'] += $payment->payment_amount;
                
                // Track success/failure
                if ($payment->isCompleted()) {
                    $methodData['successful_transactions'] = 
                        ($methodData['successful_transactions'] ?? 0) + 1;
                } elseif ($payment->isFailed()) {
                    $methodData['failed_transactions'] = 
                        ($methodData['failed_transactions'] ?? 0) + 1;
                }
                
                // Track processing time
                if ($payment->payment_date) {
                    $processingTime = $this->calculateProcessingTime($payment);
                    $methodData['total_processing_time'] = 
                        ($methodData['total_processing_time'] ?? 0) + $processingTime;
                }
            }

            // Calculate performance metrics
            foreach ($methodAnalysis['method_performance'] as &$methodData) {
                if ($methodData['total_transactions'] > 0) {
                    $methodData['success_rate'] = 
                        ($methodData['successful_transactions'] ?? 0) / $methodData['total_transactions'] * 100;
                    
                    $methodData['avg_processing_time'] = 
                        ($methodData['total_processing_time'] ?? 0) / $methodData['total_transactions'];
                    
                    $methodData['processing_fee_rate'] = 
                        $this->calculateProcessingFeeRate($methodData);
                }
            }

            // Generate optimization opportunities
            $methodAnalysis['optimization_opportunities'] = $this->generateMethodOptimization(
                $methodAnalysis['method_performance']
            );
            
            // Cost analysis
            $methodAnalysis['cost_analysis'] = $this->calculateMethodCosts($payments);
            
            // Customer preference analysis
            $methodAnalysis['customer_preference'] = $this->analyzeCustomerPreferences($payments);
            
            // Gateway performance analysis
            $methodAnalysis['gateway_performance'] = $this->analyzeGatewayPerformance($payments);

            return $methodAnalysis;

        } catch (\Exception $e) {
            Log::error('Payment method analysis error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate settlement reporting and reconciliation
     */
    public function generateSettlementReporting(array $filters = []): array
    {
        try {
            $settlementData = [
                'settlement_summary' => [
                    'total_settlements' => 0,
                    'total_settlement_amount' => 0,
                    'total_fees' => 0,
                    'net_settlement' => 0,
                    'settlement_success_rate' => 0,
                    'avg_settlement_time' => 0
                ],
                'settlement_batches' => [
                    'pending_batches' => [],
                    'processing_batches' => [],
                    'completed_batches' => [],
                    'failed_batches' => []
                ],
                'settlement_reconciliation' => [
                    'bank_reconciliation' => [],
                    'gateway_reconciliation' => [],
                    'discrepancy_resolution' => []
                ],
                'settlement_trends' => [
                    'daily_settlements' => [],
                    'weekly_volumes' => [],
                    'monthly_growth' => []
                ],
                'settlement_exceptions' => [
                    'high_value_settlements' => [],
                    'failed_settlements' => [],
                    'reconciliation_issues' => []
                ]
            ];

            $settlements = $this->getSettlementData($filters);
            
            foreach ($settlements as $settlement) {
                $settlementData['settlement_summary']['total_settlements']++;
                $settlementData['settlement_summary']['total_settlement_amount'] += $settlement->settlement_amount;
                $settlementData['settlement_summary']['total_fees'] += $settlement->settlement_fees;
                
                // Categorize settlement batches
                $batchKey = $this->categorizeSettlementBatch($settlement);
                if (isset($settlementData['settlement_batches'][$batchKey])) {
                    $settlementData['settlement_batches'][$batchKey][] = $this->formatSettlementData($settlement);
                }
            }

            // Calculate net settlement
            $settlementData['settlement_summary']['net_settlement'] = 
                $settlementData['settlement_summary']['total_settlement_amount'] - 
                $settlementData['settlement_summary']['total_fees'];

            // Generate settlement reconciliation
            $settlementData['settlement_reconciliation'] = $this->performSettlementReconciliation($settlements);
            
            // Generate settlement trends
            $settlementData['settlement_trends'] = $this->generateSettlementTrends($filters);
            
            // Process settlement exceptions
            $settlementData['settlement_exceptions'] = $this->processSettlementExceptions($settlements);

            return $settlementData;

        } catch (\Exception $e) {
            Log::error('Settlement reporting error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle payment exceptions and retry mechanisms
     */
    public function handlePaymentExceptions(array $filters = []): array
    {
        try {
            $exceptionData = [
                'exception_summary' => [
                    'total_exceptions' => 0,
                    'payment_failures' => 0,
                    'system_errors' => 0,
                    'reconciliation_errors' => 0,
                    'disputed_payments' => 0,
                    'resolution_rate' => 0
                ],
                'exception_categories' => [
                    'gateway_errors' => [],
                    'insufficient_funds' => [],
                    'invalid_credentials' => [],
                    'network_timeouts' => [],
                    'duplicate_transactions' => [],
                    'reconciliation_mismatches' => []
                ],
                'retry_analysis' => [
                    'retry_attempts' => [],
                    'success_after_retry' => [],
                    'max_retries_reached' => [],
                    'retry_patterns' => []
                ],
                'resolution_strategies' => [
                    'automatic_resolutions' => [],
                    'manual_interventions' => [],
                    'escalation_required' => [],
                    'customer_communications' => []
                ],
                'prevention_measures' => [
                    'error_prevention' => [],
                    'monitoring_alerts' => [],
                    'fallback_mechanisms' => []
                ]
            ];

            $payments = $this->getPaymentProcessingData($filters);
            
            foreach ($payments as $payment) {
                if ($payment->isFailed()) {
                    $exceptionData['exception_summary']['payment_failures']++;
                    $this->categorizeException($payment, $exceptionData);
                }
                
                if ($payment->retry_count > 0) {
                    $this->analyzeRetryPattern($payment, $exceptionData);
                }
            }

            // Calculate resolution rate
            $totalExceptions = $exceptionData['exception_summary']['total_exceptions'];
            $resolvedExceptions = $this->getResolvedExceptions($payments);
            $exceptionData['exception_summary']['resolution_rate'] = $totalExceptions > 0 
                ? (count($resolvedExceptions) / $totalExceptions) * 100 
                : 0;

            // Generate resolution strategies
            $exceptionData['resolution_strategies'] = $this->generateResolutionStrategies($exceptionData);
            
            // Generate prevention measures
            $exceptionData['prevention_measures'] = $this->generatePreventionMeasures($exceptionData);

            return $exceptionData;

        } catch (\Exception $e) {
            Log::error('Payment exception handling error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Private helper methods

    private function getPaymentProcessingData(array $filters): Collection
    {
        return PaymentProcessing::with(['payment', 'client', 'shipment'])
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->whereBetween('payment_date', [$dateRange['start'], $dateRange['end']]);
            })
            ->when($filters['payment_status'] ?? false, function ($q, $status) {
                $q->where('payment_status', $status);
            })
            ->when($filters['payment_method'] ?? false, function ($q, $method) {
                $q->where('payment_method', $method);
            })
            ->when($filters['gateway_id'] ?? false, function ($q, $gateway) {
                $q->where('gateway_id', $gateway);
            })
            ->get();
    }

    private function getSettlementData(array $filters): Collection
    {
        // This would typically query a settlements table
        // For now, we'll derive settlement data from payment processing
        return PaymentProcessing::whereNotNull('settlement_date')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->whereBetween('settlement_date', [$dateRange['start'], $dateRange['end']]);
            })
            ->get();
    }

    private function calculateWorkflowSummary(Collection $payments): array
    {
        $totalAmount = $payments->sum('payment_amount');
        $totalFees = $payments->sum('processing_fee');
        $netAmount = $totalAmount - $totalFees;
        $completed = $payments->where('payment_status', PaymentProcessing::STATUS_COMPLETED)->count();
        $total = $payments->count();
        
        return [
            'total_payments' => $total,
            'total_amount' => $totalAmount,
            'processing_fee' => $totalFees,
            'net_amount' => $netAmount,
            'success_rate' => $total > 0 ? ($completed / $total) * 100 : 0
        ];
    }

    private function calculateSuccessRates(array $paymentMethods): array
    {
        foreach ($paymentMethods as &$method) {
            if ($method['count'] > 0) {
                // Calculate success rate based on completed vs total transactions
                // This would need actual success data from the database
                $method['success_rate'] = 85; // Placeholder
            }
        }
        
        return $paymentMethods;
    }

    private function calculateProcessingMetrics(Collection $payments): array
    {
        $completedPayments = $payments->where('payment_status', PaymentProcessing::STATUS_COMPLETED);
        
        $avgProcessingTime = $completedPayments->avg(function ($payment) {
            if ($payment->settlement_date) {
                return $payment->payment_date->diffInMinutes($payment->settlement_date);
            }
            return 0;
        });
        
        return [
            'avg_processing_time' => $avgProcessingTime,
            'success_rate_by_method' => $this->calculateSuccessRatesByMethod($payments),
            'failure_analysis' => $this->analyzeFailures($payments),
            'bottlenecks' => $this->identifyBottlenecks($payments)
        ];
    }

    private function calculateSuccessRatesByMethod(Collection $payments): array
    {
        $methodRates = [];
        $grouped = $payments->groupBy('payment_method');
        
        foreach ($grouped as $method => $methodPayments) {
            $completed = $methodPayments->where('payment_status', PaymentProcessing::STATUS_COMPLETED)->count();
            $total = $methodPayments->count();
            
            $methodRates[$method] = $total > 0 ? ($completed / $total) * 100 : 0;
        }
        
        return $methodRates;
    }

    private function analyzeFailures(Collection $payments): array
    {
        $failures = $payments->where('payment_status', PaymentProcessing::STATUS_FAILED);
        $failureAnalysis = [];
        
        foreach ($failures as $payment) {
            $reason = $payment->failure_reason ?? 'Unknown';
            if (!isset($failureAnalysis[$reason])) {
                $failureAnalysis[$reason] = 0;
            }
            $failureAnalysis[$reason]++;
        }
        
        return $failureAnalysis;
    }

    private function identifyBottlenecks(Collection $payments): array
    {
        $bottlenecks = [];
        
        // Analyze processing time by gateway
        $gatewayTimes = $payments->groupBy('gateway_id')->map(function ($gatewayPayments) {
            return $gatewayPayments->avg(function ($payment) {
                return $payment->settlement_date 
                    ? $payment->payment_date->diffInMinutes($payment->settlement_date)
                    : 0;
            });
        });
        
        foreach ($gatewayTimes as $gateway => $avgTime) {
            if ($avgTime > 30) { // More than 30 minutes
                $bottlenecks[] = [
                    'type' => 'gateway_performance',
                    'gateway' => $gateway,
                    'avg_processing_time' => $avgTime,
                    'severity' => $avgTime > 60 ? 'high' : 'medium'
                ];
            }
        }
        
        return $bottlenecks;
    }

    private function calculateProcessingTime(PaymentProcessing $payment): int
    {
        if ($payment->settlement_date) {
            return $payment->payment_date->diffInMinutes($payment->settlement_date);
        }
        return 0;
    }

    private function analyzeDiscrepancy(PaymentProcessing $payment, array &$reconciliationData): void
    {
        // This would analyze the specific type of discrepancy
        // For now, we'll use placeholder logic
        $reconciliationData['discrepancy_analysis']['amount_discrepancies'][] = [
            'payment_id' => $payment->id,
            'expected_amount' => $payment->payment_amount,
            'actual_amount' => 0, // Would come from bank/gateway data
            'discrepancy' => $payment->payment_amount
        ];
    }

    private function processReconciliationExceptions(Collection $payments): array
    {
        $exceptions = [
            'high_value_exceptions' => [],
            'repeated_exceptions' => [],
            'system_errors' => []
        ];
        
        foreach ($payments as $payment) {
            if ($payment->payment_amount > 10000) {
                $exceptions['high_value_exceptions'][] = [
                    'payment_id' => $payment->id,
                    'amount' => $payment->payment_amount,
                    'status' => $payment->payment_status
                ];
            }
        }
        
        return $exceptions;
    }

    private function generateReconciliationTrends(array $filters): array
    {
        // Generate daily, weekly, and monthly reconciliation trends
        return [
            'daily_trends' => [],
            'weekly_trends' => [],
            'monthly_trends' => []
        ];
    }

    private function calculateAverageReconciliationTime(Collection $payments): float
    {
        $reconciled = $payments->where('reconciliation_status', PaymentProcessing::RECONCILIATION_RECONCILED);
        
        return $reconciled->avg(function ($payment) {
            if ($payment->reconciliation_date && $payment->payment_date) {
                return $payment->payment_date->diffInDays($payment->reconciliation_date);
            }
            return 0;
        });
    }

    private function calculateProcessingFeeRate(array $methodData): float
    {
        if ($methodData['total_amount'] <= 0) {
            return 0;
        }
        
        // This would need actual fee data
        return 2.9; // Placeholder 2.9% fee rate
    }

    private function generateMethodOptimization(array $methodPerformance): array
    {
        $opportunities = [];
        
        foreach ($methodPerformance as $method => $data) {
            if ($data['success_rate'] < 90) {
                $opportunities[] = [
                    'method' => $method,
                    'issue' => 'Low success rate',
                    'current_rate' => $data['success_rate'],
                    'target_rate' => 95,
                    'recommendation' => 'Review gateway settings and error handling'
                ];
            }
        }
        
        return $opportunities;
    }

    private function calculateMethodCosts(Collection $payments): array
    {
        $methodCosts = [];
        $totalCosts = 0;
        
        $grouped = $payments->groupBy('payment_method');
        
        foreach ($grouped as $method => $methodPayments) {
            $totalAmount = $methodPayments->sum('payment_amount');
            $totalFees = $methodPayments->sum('processing_fee');
            
            $methodCosts[$method] = [
                'total_amount' => $totalAmount,
                'total_fees' => $totalFees,
                'cost_per_transaction' => $methodPayments->count() > 0 
                    ? $totalFees / $methodPayments->count() 
                    : 0,
                'cost_per_dollar' => $totalAmount > 0 
                    ? ($totalFees / $totalAmount) * 100 
                    : 0
            ];
            
            $totalCosts += $totalFees;
        }
        
        $totalTransactions = $payments->count();
        $totalAmount = $payments->sum('payment_amount');
        
        return [
            'method_costs' => $methodCosts,
            'total_processing_costs' => $totalCosts,
            'cost_per_transaction' => $totalTransactions > 0 ? $totalCosts / $totalTransactions : 0,
            'cost_per_dollar' => $totalAmount > 0 ? ($totalCosts / $totalAmount) * 100 : 0
        ];
    }

    private function analyzeCustomerPreferences(Collection $payments): array
    {
        // This would analyze customer payment preferences
        return [
            'preferred_methods' => [],
            'method_trends' => [],
            'geographic_variations' => []
        ];
    }

    private function analyzeGatewayPerformance(Collection $payments): array
    {
        $gatewayPerformance = [];
        $grouped = $payments->groupBy('gateway_id');
        
        foreach ($grouped as $gateway => $gatewayPayments) {
            $completed = $gatewayPayments->where('payment_status', PaymentProcessing::STATUS_COMPLETED)->count();
            $total = $gatewayPayments->count();
            
            $gatewayPerformance[] = [
                'gateway' => $gateway,
                'total_transactions' => $total,
                'success_rate' => $total > 0 ? ($completed / $total) * 100 : 0,
                'avg_processing_time' => $this->calculateAverageGatewayProcessingTime($gatewayPayments),
                'total_volume' => $gatewayPayments->sum('payment_amount')
            ];
        }
        
        return [
            'gateway_comparison' => $gatewayPerformance,
            'reliability_metrics' => [],
            'cost_efficiency' => []
        ];
    }

    private function calculateAverageGatewayProcessingTime(Collection $payments): float
    {
        $completed = $payments->where('payment_status', PaymentProcessing::STATUS_COMPLETED);
        
        return $completed->avg(function ($payment) {
            if ($payment->settlement_date) {
                return $payment->payment_date->diffInMinutes($payment->settlement_date);
            }
            return 0;
        });
    }

    private function categorizeSettlementBatch($settlement): string
    {
        // Categorize settlement based on status
        return match($settlement->settlement_status ?? 'pending') {
            'pending' => 'pending_batches',
            'processing' => 'processing_batches',
            'completed' => 'completed_batches',
            'failed' => 'failed_batches',
            default => 'pending_batches'
        };
    }

    private function formatSettlementData($settlement): array
    {
        return [
            'batch_id' => $settlement->id,
            'settlement_amount' => $settlement->settlement_amount ?? 0,
            'settlement_fees' => $settlement->settlement_fees ?? 0,
            'settlement_date' => $settlement->settlement_date,
            'payment_count' => 1, // This would be calculated from batch
            'status' => $settlement->settlement_status ?? 'pending'
        ];
    }

    private function performSettlementReconciliation(Collection $settlements): array
    {
        // Perform bank and gateway reconciliation
        return [
            'bank_reconciliation' => [],
            'gateway_reconciliation' => [],
            'discrepancy_resolution' => []
        ];
    }

    private function generateSettlementTrends(array $filters): array
    {
        return [
            'daily_settlements' => [],
            'weekly_volumes' => [],
            'monthly_growth' => []
        ];
    }

    private function processSettlementExceptions(Collection $settlements): array
    {
        return [
            'high_value_settlements' => [],
            'failed_settlements' => [],
            'reconciliation_issues' => []
        ];
    }

    private function categorizeException(PaymentProcessing $payment, array &$exceptionData): void
    {
        $exceptionData['exception_summary']['total_exceptions']++;
        
        $reason = $payment->failure_reason ?? 'Unknown';
        
        if (str_contains(strtolower($reason), 'gateway')) {
            $exceptionData['exception_categories']['gateway_errors'][] = $payment->id;
        } elseif (str_contains(strtolower($reason), 'insufficient')) {
            $exceptionData['exception_categories']['insufficient_funds'][] = $payment->id;
        } elseif (str_contains(strtolower($reason), 'timeout')) {
            $exceptionData['exception_categories']['network_timeouts'][] = $payment->id;
        } else {
            $exceptionData['exception_categories']['gateway_errors'][] = $payment->id; // Default category
        }
    }

    private function analyzeRetryPattern(PaymentProcessing $payment, array &$exceptionData): void
    {
        $exceptionData['retry_analysis']['retry_attempts'][$payment->retry_count] = 
            ($exceptionData['retry_analysis']['retry_attempts'][$payment->retry_count] ?? 0) + 1;
    }

    private function getResolvedExceptions(Collection $payments): Collection
    {
        return $payments->where('payment_status', PaymentProcessing::STATUS_COMPLETED);
    }

    private function generateResolutionStrategies(array $exceptionData): array
    {
        return [
            'automatic_resolutions' => [],
            'manual_interventions' => [],
            'escalation_required' => [],
            'customer_communications' => []
        ];
    }

    private function generatePreventionMeasures(array $exceptionData): array
    {
        return [
            'error_prevention' => [],
            'monitoring_alerts' => [],
            'fallback_mechanisms' => []
        ];
    }
}