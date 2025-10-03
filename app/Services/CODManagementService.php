<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CODManagementService
{
    /**
     * Record COD collection
     */
    public function recordCODCollection(Shipment $shipment, array $collectionData): array
    {
        if (!$shipment->cod_amount || $shipment->cod_amount <= 0) {
            return [
                'success' => false,
                'message' => 'Shipment does not have COD amount',
            ];
        }

        if ($shipment->cod_status === 'collected') {
            return [
                'success' => false,
                'message' => 'COD already collected for this shipment',
            ];
        }

        $collectedAmount = $collectionData['amount'];
        $collector = BranchWorker::find($collectionData['collector_id']);

        if (!$collector) {
            return [
                'success' => false,
                'message' => 'Invalid collector',
            ];
        }

        if (!$collector->canPerform('collect_cod')) {
            return [
                'success' => false,
                'message' => 'Collector does not have permission to collect COD',
            ];
        }

        DB::beginTransaction();
        try {
            // Create COD collection record
            $collection = $shipment->codCollections()->create([
                'collected_amount' => $collectedAmount,
                'collector_id' => $collector->id,
                'collection_date' => now(),
                'collection_method' => $collectionData['method'] ?? 'cash',
                'reference_number' => $collectionData['reference_number'] ?? null,
                'notes' => $collectionData['notes'] ?? null,
                'branch_id' => $collector->branch_id,
                'status' => 'collected',
                'metadata' => $collectionData,
            ]);

            // Update shipment COD status
            $totalCollected = $shipment->codCollections()->sum('collected_amount');

            if ($totalCollected >= $shipment->cod_amount) {
                $shipment->update([
                    'cod_status' => 'collected',
                    'cod_collected_at' => now(),
                    'cod_collected_by' => $collector->id,
                ]);
            } else {
                $shipment->update([
                    'cod_status' => 'partially_collected',
                ]);
            }

            // Log the collection
            activity()
                ->performedOn($shipment)
                ->causedBy($collector->user)
                ->withProperties([
                    'collected_amount' => $collectedAmount,
                    'total_collected' => $totalCollected,
                    'cod_amount' => $shipment->cod_amount,
                    'collection_method' => $collectionData['method'] ?? 'cash',
                ])
                ->log("COD collected: \${$collectedAmount}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'COD collection recorded successfully',
                'collection' => $collection,
                'shipment' => $shipment,
                'remaining_cod' => max(0, $shipment->cod_amount - $totalCollected),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('COD collection recording failed', [
                'shipment_id' => $shipment->id,
                'amount' => $collectedAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to record COD collection: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process COD remittance to customer
     */
    public function processCODRemittance(Shipment $shipment, array $remittanceData): array
    {
        if ($shipment->cod_status !== 'collected') {
            return [
                'success' => false,
                'message' => 'COD not fully collected for this shipment',
            ];
        }

        if ($shipment->cod_remitted_at) {
            return [
                'success' => false,
                'message' => 'COD already remitted for this shipment',
            ];
        }

        $totalCollected = $shipment->codCollections()->sum('collected_amount');
        $remittanceAmount = $remittanceData['amount'] ?? $totalCollected;

        DB::beginTransaction();
        try {
            // Create remittance record
            $remittance = $shipment->codRemittances()->create([
                'remitted_amount' => $remittanceAmount,
                'remittance_date' => now(),
                'remitted_to' => $shipment->customer_id,
                'remittance_method' => $remittanceData['method'] ?? 'bank_transfer',
                'reference_number' => $remittanceData['reference_number'] ?? null,
                'bank_details' => $remittanceData['bank_details'] ?? null,
                'processed_by' => auth()->user()->id ?? null,
                'notes' => $remittanceData['notes'] ?? null,
                'status' => 'completed',
                'metadata' => $remittanceData,
            ]);

            // Update shipment
            $shipment->update([
                'cod_remitted_at' => now(),
                'cod_remitted_by' => auth()->user()->id ?? null,
                'cod_remittance_reference' => $remittanceData['reference_number'] ?? null,
            ]);

            // Update customer COD balance
            $customer = $shipment->customer;
            $customer->update([
                'cod_balance' => ($customer->cod_balance ?? 0) - $remittanceAmount,
                'total_cod_remitted' => ($customer->total_cod_remitted ?? 0) + $remittanceAmount,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'COD remittance processed successfully',
                'remittance' => $remittance,
                'shipment' => $shipment,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('COD remittance processing failed', [
                'shipment_id' => $shipment->id,
                'amount' => $remittanceAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process COD remittance: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get COD collection summary for branch
     */
    public function getCODCollectionSummary(Branch $branch, Carbon $startDate, Carbon $endDate): array
    {
        $collections = DB::table('cod_collections')
            ->join('shipments', 'cod_collections.shipment_id', '=', 'shipments.id')
            ->where('cod_collections.branch_id', $branch->id)
            ->whereBetween('cod_collections.collection_date', [$startDate, $endDate])
            ->select([
                'cod_collections.*',
                'shipments.tracking_number',
                'shipments.customer_id',
                'shipments.cod_amount',
            ])
            ->get();

        $totalCollected = $collections->sum('collected_amount');
        $totalExpected = $collections->sum('cod_amount');
        $collectionRate = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;

        $collectionsByCollector = $collections->groupBy('collector_id')->map(function ($collectorCollections) {
            return [
                'collector_id' => $collectorCollections->first()->collector_id,
                'total_collections' => $collectorCollections->count(),
                'total_amount' => $collectorCollections->sum('collected_amount'),
                'average_collection' => $collectorCollections->avg('collected_amount'),
            ];
        });

        $collectionsByMethod = $collections->groupBy('collection_method')->map(function ($methodCollections) {
            return [
                'method' => $methodCollections->first()->collection_method,
                'count' => $methodCollections->count(),
                'total_amount' => $methodCollections->sum('collected_amount'),
            ];
        });

        return [
            'branch' => $branch->name,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_collections' => $collections->count(),
                'total_collected' => $totalCollected,
                'total_expected' => $totalExpected,
                'collection_rate' => round($collectionRate, 2),
                'outstanding_cod' => $totalExpected - $totalCollected,
            ],
            'collections_by_collector' => $collectionsByCollector->values(),
            'collections_by_method' => $collectionsByMethod->values(),
            'daily_breakdown' => $this->getDailyCODBreakdown($collections, $startDate, $endDate),
        ];
    }

    /**
     * Get daily COD breakdown
     */
    private function getDailyCODBreakdown(Collection $collections, Carbon $startDate, Carbon $endDate): array
    {
        $dailyData = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayCollections = $collections->filter(function ($collection) use ($currentDate) {
                return Carbon::parse($collection->collection_date)->isSameDay($currentDate);
            });

            $dailyData[] = [
                'date' => $currentDate->toDateString(),
                'collections_count' => $dayCollections->count(),
                'total_amount' => $dayCollections->sum('collected_amount'),
                'average_collection' => $dayCollections->count() > 0 ? $dayCollections->avg('collected_amount') : 0,
            ];

            $currentDate->addDay();
        }

        return $dailyData;
    }

    /**
     * Get COD remittance summary
     */
    public function getCODRemittanceSummary(Carbon $startDate, Carbon $endDate): array
    {
        $remittances = DB::table('cod_remittances')
            ->join('shipments', 'cod_remittances.shipment_id', '=', 'shipments.id')
            ->join('users', 'cod_remittances.remitted_to', '=', 'users.id')
            ->whereBetween('cod_remittances.remittance_date', [$startDate, $endDate])
            ->select([
                'cod_remittances.*',
                'shipments.tracking_number',
                'users.name as customer_name',
                'users.email as customer_email',
            ])
            ->get();

        $totalRemitted = $remittances->sum('remitted_amount');
        $remittanceCount = $remittances->count();

        $remittancesByMethod = $remittances->groupBy('remittance_method')->map(function ($methodRemittances) {
            return [
                'method' => $methodRemittances->first()->remittance_method,
                'count' => $methodRemittances->count(),
                'total_amount' => $methodRemittances->sum('remitted_amount'),
            ];
        });

        $topCustomers = $remittances->groupBy('remitted_to')->map(function ($customerRemittances) {
            return [
                'customer_id' => $customerRemittances->first()->remitted_to,
                'customer_name' => $customerRemittances->first()->customer_name,
                'remittance_count' => $customerRemittances->count(),
                'total_amount' => $customerRemittances->sum('remitted_amount'),
            ];
        })->sortByDesc('total_amount')->take(10)->values();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_remittances' => $remittanceCount,
                'total_amount_remitted' => $totalRemitted,
                'average_remittance' => $remittanceCount > 0 ? $totalRemitted / $remittanceCount : 0,
            ],
            'remittances_by_method' => $remittancesByMethod->values(),
            'top_customers' => $topCustomers,
            'daily_breakdown' => $this->getDailyRemittanceBreakdown($remittances, $startDate, $endDate),
        ];
    }

    /**
     * Get daily remittance breakdown
     */
    private function getDailyRemittanceBreakdown(Collection $remittances, Carbon $startDate, Carbon $endDate): array
    {
        $dailyData = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayRemittances = $remittances->filter(function ($remittance) use ($currentDate) {
                return Carbon::parse($remittance->remittance_date)->isSameDay($currentDate);
            });

            $dailyData[] = [
                'date' => $currentDate->toDateString(),
                'remittances_count' => $dayRemittances->count(),
                'total_amount' => $dayRemittances->sum('remitted_amount'),
            ];

            $currentDate->addDay();
        }

        return $dailyData;
    }

    /**
     * Get COD reconciliation report
     */
    public function getCODReconciliationReport(Carbon $startDate, Carbon $endDate): array
    {
        // Get all COD shipments in the period
        $codShipments = Shipment::whereNotNull('cod_amount')
            ->where('cod_amount', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['codCollections', 'codRemittances', 'customer'])
            ->get();

        $reconciliation = [
            'total_cod_shipments' => $codShipments->count(),
            'total_cod_value' => $codShipments->sum('cod_amount'),
            'collected_amount' => 0,
            'remitted_amount' => 0,
            'outstanding_collection' => 0,
            'outstanding_remittance' => 0,
            'discrepancies' => [],
        ];

        foreach ($codShipments as $shipment) {
            $collected = $shipment->codCollections()->sum('collected_amount');
            $remitted = $shipment->codRemittances()->sum('remitted_amount');

            $reconciliation['collected_amount'] += $collected;
            $reconciliation['remitted_amount'] += $remitted;

            // Check for discrepancies
            if ($collected > $shipment->cod_amount) {
                $reconciliation['discrepancies'][] = [
                    'type' => 'over_collection',
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'expected' => $shipment->cod_amount,
                    'collected' => $collected,
                    'difference' => $collected - $shipment->cod_amount,
                ];
            }

            if ($remitted > $collected) {
                $reconciliation['discrepancies'][] = [
                    'type' => 'over_remittance',
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'collected' => $collected,
                    'remitted' => $remitted,
                    'difference' => $remitted - $collected,
                ];
            }
        }

        $reconciliation['outstanding_collection'] = $reconciliation['total_cod_value'] - $reconciliation['collected_amount'];
        $reconciliation['outstanding_remittance'] = $reconciliation['collected_amount'] - $reconciliation['remitted_amount'];

        $reconciliation['collection_rate'] = $reconciliation['total_cod_value'] > 0
            ? ($reconciliation['collected_amount'] / $reconciliation['total_cod_value']) * 100
            : 0;

        $reconciliation['remittance_rate'] = $reconciliation['collected_amount'] > 0
            ? ($reconciliation['remitted_amount'] / $reconciliation['collected_amount']) * 100
            : 0;

        return $reconciliation;
    }

    /**
     * Process bulk COD collections
     */
    public function processBulkCODCollections(Collection $collections): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($collections as $collectionData) {
                $results['processed']++;

                try {
                    $shipment = Shipment::findOrFail($collectionData['shipment_id']);
                    $result = $this->recordCODCollection($shipment, $collectionData);

                    if ($result['success']) {
                        $results['successful']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'shipment_id' => $collectionData['shipment_id'],
                            'error' => $result['message'],
                        ];
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'shipment_id' => $collectionData['shipment_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if ($results['successful'] > 0) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk COD collection processing failed', [
                'total_processed' => $results['processed'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Bulk COD collection processing failed: ' . $e->getMessage(),
                'results' => $results,
            ];
        }

        return [
            'success' => true,
            'message' => 'Bulk COD collection processing completed',
            'results' => $results,
        ];
    }

    /**
     * Get COD aging report
     */
    public function getCODAgingReport(): array
    {
        $now = now();

        $agingBuckets = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
        ];

        $outstandingCOD = Shipment::whereNotNull('cod_amount')
            ->where('cod_amount', '>', 0)
            ->where(function ($query) {
                $query->where('cod_status', '!=', 'collected')
                      ->orWhereNull('cod_status');
            })
            ->with('customer')
            ->get();

        foreach ($outstandingCOD as $shipment) {
            $deliveredAt = $shipment->delivered_at ?? $shipment->created_at;
            $daysOutstanding = $now->diffInDays($deliveredAt);

            $collected = $shipment->codCollections()->sum('collected_amount');
            $outstanding = $shipment->cod_amount - $collected;

            if ($daysOutstanding <= 0) {
                $agingBuckets['current'] += $outstanding;
            } elseif ($daysOutstanding <= 30) {
                $agingBuckets['1_30_days'] += $outstanding;
            } elseif ($daysOutstanding <= 60) {
                $agingBuckets['31_60_days'] += $outstanding;
            } elseif ($daysOutstanding <= 90) {
                $agingBuckets['61_90_days'] += $outstanding;
            } else {
                $agingBuckets['over_90_days'] += $outstanding;
            }
        }

        return [
            'total_outstanding_cod' => array_sum($agingBuckets),
            'aging_breakdown' => $agingBuckets,
            'aging_percentage' => $this->calculateAgingPercentages($agingBuckets),
            'generated_at' => $now->toISOString(),
        ];
    }

    /**
     * Calculate aging percentages
     */
    private function calculateAgingPercentages(array $agingBuckets): array
    {
        $total = array_sum($agingBuckets);

        if ($total == 0) {
            return array_fill_keys(array_keys($agingBuckets), 0);
        }

        $percentages = [];
        foreach ($agingBuckets as $bucket => $amount) {
            $percentages[$bucket] = round(($amount / $total) * 100, 2);
        }

        return $percentages;
    }

    /**
     * Get COD performance metrics
     */
    public function getCODPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $codShipments = Shipment::whereNotNull('cod_amount')
            ->where('cod_amount', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalCODValue = $codShipments->sum('cod_amount');
        $collectedCOD = 0;
        $remittedCOD = 0;
        $averageCollectionTime = 0;
        $averageRemittanceTime = 0;

        $collectionTimes = [];
        $remittanceTimes = [];

        foreach ($codShipments as $shipment) {
            $collected = $shipment->codCollections()->sum('collected_amount');
            $remitted = $shipment->codRemittances()->sum('remitted_amount');

            $collectedCOD += $collected;
            $remittedCOD += $remitted;

            // Calculate collection time (from delivery to collection)
            if ($shipment->delivered_at && $shipment->codCollections()->exists()) {
                $firstCollection = $shipment->codCollections()->orderBy('collection_date')->first();
                if ($firstCollection) {
                    $collectionTimes[] = $shipment->delivered_at->diffInDays($firstCollection->collection_date);
                }
            }

            // Calculate remittance time (from collection to remittance)
            if ($shipment->codRemittances()->exists()) {
                $firstRemittance = $shipment->codRemittances()->orderBy('remittance_date')->first();
                if ($firstRemittance) {
                    $collectionDate = $shipment->codCollections()->min('collection_date');
                    if ($collectionDate) {
                        $remittanceTimes[] = Carbon::parse($collectionDate)->diffInDays($firstRemittance->remittance_date);
                    }
                }
            }
        }

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'metrics' => [
                'total_cod_shipments' => $codShipments->count(),
                'total_cod_value' => $totalCODValue,
                'collected_cod' => $collectedCOD,
                'remitted_cod' => $remittedCOD,
                'collection_rate' => $totalCODValue > 0 ? ($collectedCOD / $totalCODValue) * 100 : 0,
                'remittance_rate' => $collectedCOD > 0 ? ($remittedCOD / $collectedCOD) * 100 : 0,
                'average_collection_time_days' => count($collectionTimes) > 0 ? array_sum($collectionTimes) / count($collectionTimes) : 0,
                'average_remittance_time_days' => count($remittanceTimes) > 0 ? array_sum($remittanceTimes) / count($remittanceTimes) : 0,
            ],
        ];
    }

    /**
     * Generate COD collection report for worker
     */
    public function getWorkerCODReport(BranchWorker $worker, Carbon $startDate, Carbon $endDate): array
    {
        $collections = DB::table('cod_collections')
            ->join('shipments', 'cod_collections.shipment_id', '=', 'shipments.id')
            ->where('cod_collections.collector_id', $worker->id)
            ->whereBetween('cod_collections.collection_date', [$startDate, $endDate])
            ->select([
                'cod_collections.*',
                'shipments.tracking_number',
                'shipments.cod_amount',
                'shipments.customer_id',
            ])
            ->get();

        $totalCollected = $collections->sum('collected_amount');
        $collectionCount = $collections->count();
        $averageCollection = $collectionCount > 0 ? $totalCollected / $collectionCount : 0;

        $collectionsByDay = $collections->groupBy(function ($collection) {
            return Carbon::parse($collection->collection_date)->toDateString();
        })->map(function ($dayCollections) {
            return [
                'date' => $dayCollections->first()->collection_date,
                'count' => $dayCollections->count(),
                'amount' => $dayCollections->sum('collected_amount'),
            ];
        })->values();

        return [
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->full_name,
                'branch' => $worker->branch->name,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_collections' => $collectionCount,
                'total_amount' => $totalCollected,
                'average_collection' => round($averageCollection, 2),
                'daily_average' => round($totalCollected / $startDate->diffInDays($endDate) ?: 1, 2),
            ],
            'daily_breakdown' => $collectionsByDay,
        ];
    }
}