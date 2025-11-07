<?php

namespace App\Services\ETL;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ETL\EtlBatch;
use App\Services\ETL\Transformers\DataTransformer;
use App\Services\ETL\Validators\DataValidator;
use App\Services\Cache\AnalyticsCacheService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EtlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;
    public $backoff = [60, 300, 900];
    public $maxExceptions = 5;

    protected string $batchId;
    protected array $pipelineConfig;
    protected string $pipelineName;
    protected int $retryCount = 0;

    public function __construct(string $batchId, string $pipelineName, array $pipelineConfig)
    {
        $this->batchId = $batchId;
        $this->pipelineName = $pipelineName;
        $this->pipelineConfig = $pipelineConfig;
    }

    public function handle(): void
    {
        $batch = EtlBatch::find($this->batchId);
        
        if (!$batch) {
            Log::error("ETL Batch not found: {$this->batchId}");
            return;
        }

        try {
            $batch->updateStatus('RUNNING');
            Log::info("Starting ETL pipeline: {$this->pipelineName}", ['batch_id' => $this->batchId]);

            // 1. Extract data from all sources
            $extractedData = $this->extract();
            
            // 2. Load to staging
            $stagingData = $this->loadToStaging($extractedData);
            
            // 3. Transform data
            $transformedData = $this->transform($stagingData);
            
            // 4. Validate data
            $validatedData = $this->validate($transformedData);
            
            // 5. Load to data warehouse
            $loadResult = $this->loadToWarehouse($validatedData);
            
            // 6. Update aggregations
            if ($loadResult['success']) {
                $this->updateAggregations();
                $this->clearCaches();
            }
            
            // 7. Update batch status
            $batch->updateStatus('COMPLETED', null, $loadResult);
            
            Log::info("ETL pipeline completed: {$this->pipelineName}", [
                'batch_id' => $this->batchId,
                'records_processed' => $loadResult['records_processed'] ?? 0
            ]);

        } catch (\Exception $e) {
            $this->handleError($batch, $e);
            throw $e;
        }
    }

    protected function extract(): array
    {
        $data = [];
        $startTime = microtime(true);
        
        foreach ($this->pipelineConfig['sources'] as $sourceName => $config) {
            try {
                switch ($config['type']) {
                    case 'api':
                        $data[$sourceName] = $this->extractFromApi($config);
                        break;
                    case 'database':
                        $data[$sourceName] = $this->extractFromDatabase($config);
                        break;
                    case 'fact_table':
                        $data[$sourceName] = $this->extractFromFactTable($config);
                        break;
                    default:
                        Log::warning("Unknown extraction type: {$config['type']}", ['source' => $sourceName]);
                }
                
                Log::debug("Data extracted from source: {$sourceName}", [
                    'count' => count($data[$sourceName] ?? []),
                    'batch_id' => $this->batchId
                ]);
                
            } catch (\Exception $e) {
                Log::error("Failed to extract from source: {$sourceName}", [
                    'error' => $e->getMessage(),
                    'batch_id' => $this->batchId
                ]);
                throw $e;
            }
        }

        $extractionTime = microtime(true) - $startTime;
        Log::info("Data extraction completed", [
            'pipeline' => $this->pipelineName,
            'batch_id' => $this->batchId,
            'sources_count' => count($data),
            'extraction_time' => round($extractionTime, 2)
        ]);

        return $data;
    }

    protected function extractFromApi(array $config): array
    {
        $client = new \GuzzleHttp\Client([
            'timeout' => $config['timeout'] ?? 30,
            'headers' => $this->getAuthHeaders($config)
        ]);

        $lastUpdate = $this->getLastExtractionTimestamp($config['table'] ?? $config['endpoint']);
        $records = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore && $page <= ($config['max_pages'] ?? 10)) {
            $queryParams = [
                'page' => $page,
                'limit' => $config['batch_size'] ?? 1000,
            ];

            if ($config['incremental_field'] && $lastUpdate) {
                $queryParams['since'] = $lastUpdate;
            }

            $response = $client->get($config['endpoint'], ['query' => $queryParams]);
            $responseData = json_decode($response->getBody(), true);

            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $records = array_merge($records, $responseData['data']);
                $hasMore = $responseData['has_more'] ?? false;
                $page++;
            } else {
                $hasMore = false;
            }
        }

        return $records;
    }

    protected function extractFromDatabase(array $config): array
    {
        $lastUpdate = $this->getLastExtractionTimestamp($config['table']);
        
        $query = \DB::connection($config['connection'] ?? 'mysql')
            ->table($config['table'])
            ->limit($config['batch_size'] ?? 1000);

        if ($config['incremental_field'] && $lastUpdate) {
            $query->where($config['incremental_field'], '>', $lastUpdate);
        }

        if (isset($config['where_clause'])) {
            $query->whereRaw($config['where_clause']);
        }

        return $query->get()->toArray();
    }

    protected function extractFromFactTable(array $config): array
    {
        // This is for aggregations and complex queries
        $bindings = [];
        $query = $config['query'];
        
        if (strpos($query, '?') !== false) {
            $dateKey = now()->subDay()->format('Ymd');
            $bindings = [$dateKey];
        }

        return \DB::select($query, $bindings);
    }

    protected function loadToStaging(array $data): array
    {
        $stagingTable = 'stg_' . ($this->pipelineConfig['table'] ?? 'shipments');
        $stagingRecords = [];

        // Combine all source data
        foreach ($data as $sourceName => $records) {
            foreach ($records as $record) {
                $stagingRecords[] = [
                    'stg_batch_id' => $this->batchId,
                    'source_data' => json_encode($record),
                    'source_system' => $sourceName,
                    'extraction_timestamp' => now()->toISOString(),
                    'processing_status' => 'PENDING'
                ];
            }
        }

        // Insert to staging in batches
        foreach (array_chunk($stagingRecords, 1000) as $chunk) {
            \DB::table($stagingTable)->insert($chunk);
        }

        return \DB::table($stagingTable)
            ->where('stg_batch_id', $this->batchId)
            ->get()
            ->toArray();
    }

    protected function transform(array $data): array
    {
        $transformer = new DataTransformer();
        $transformedData = [];

        foreach ($data as $record) {
            try {
                $sourceData = json_decode($record->source_data, true);
                $transformed = $transformer->transform($sourceData, $this->pipelineConfig);
                $transformed['stg_id'] = $record->stg_id;
                $transformed['batch_id'] = $this->batchId;
                $transformedData[] = $transformed;
                
                // Update staging status
                \DB::table('stg_shipments')
                    ->where('stg_id', $record->stg_id)
                    ->update(['processing_status' => 'TRANSFORMED']);

            } catch (\Exception $e) {
                Log::error("Transformation failed for record", [
                    'stg_id' => $record->stg_id,
                    'error' => $e->getMessage()
                ]);
                
                \DB::table('stg_shipments')
                    ->where('stg_id', $record->stg_id)
                    ->update([
                        'processing_status' => 'FAILED',
                        'processing_errors' => $e->getMessage()
                    ]);
            }
        }

        return $transformedData;
    }

    protected function validate(array $data): array
    {
        $validator = new DataValidator();
        $validatedData = [];
        $validationErrors = 0;

        foreach ($data as $record) {
            try {
                $result = $validator->validate($record, $this->pipelineConfig['validations'] ?? []);
                
                if ($result->isValid()) {
                    $record['validation_score'] = $result->getScore();
                    $validatedData[] = $record;
                } else {
                    $validationErrors++;
                    \DB::table('stg_shipments')
                        ->where('stg_id', $record['stg_id'])
                        ->update([
                            'processing_status' => 'FAILED',
                            'processing_errors' => json_encode($result->getErrors())
                        ]);
                }
                
            } catch (\Exception $e) {
                $validationErrors++;
                Log::error("Validation failed for record", [
                    'stg_id' => $record['stg_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($validationErrors > 0) {
            Log::warning("Validation completed with errors", [
                'total_records' => count($data),
                'failed_records' => $validationErrors,
                'batch_id' => $this->batchId
            ]);
        }

        return $validatedData;
    }

    protected function loadToWarehouse(array $data): array
    {
        $result = [
            'success' => false,
            'records_processed' => 0,
            'records_successful' => 0,
            'records_failed' => 0
        ];

        foreach ($this->pipelineConfig['destinations'] as $destName => $config) {
            try {
                $loadResult = $this->loadToDestination($data, $config);
                $result['records_processed'] += $loadResult['records_processed'];
                $result['records_successful'] += $loadResult['records_successful'];
                $result['records_failed'] += $loadResult['records_failed'];
                
            } catch (\Exception $e) {
                Log::error("Failed to load to destination: {$destName}", [
                    'error' => $e->getMessage(),
                    'batch_id' => $this->batchId
                ]);
                throw $e;
            }
        }

        $result['success'] = $result['records_failed'] === 0;
        return $result;
    }

    protected function loadToDestination(array $data, array $config): array
    {
        $result = [
            'records_processed' => 0,
            'records_successful' => 0,
            'records_failed' => 0
        ];

        switch ($config['load_type']) {
            case 'append':
                $result = $this->loadAppend($data, $config);
                break;
            case 'upsert':
                $result = $this->loadUpsert($data, $config);
                break;
            default:
                throw new \Exception("Unknown load type: {$config['load_type']}");
        }

        return $result;
    }

    protected function loadAppend(array $data, array $config): array
    {
        $batchSize = $config['batch_size'] ?? 1000;
        $recordsSuccessful = 0;
        $recordsFailed = 0;

        foreach (array_chunk($data, $batchSize) as $chunk) {
            try {
                \DB::table($config['table'])->insert($chunk);
                $recordsSuccessful += count($chunk);
            } catch (\Exception $e) {
                $recordsFailed += count($chunk);
                Log::error("Batch insert failed", [
                    'table' => $config['table'],
                    'batch_size' => count($chunk),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'records_processed' => count($data),
            'records_successful' => $recordsSuccessful,
            'records_failed' => $recordsFailed
        ];
    }

    protected function loadUpsert(array $data, array $config): array
    {
        $batchSize = $config['batch_size'] ?? 1000;
        $recordsSuccessful = 0;
        $recordsFailed = 0;
        $mergeKey = $config['merge_key'];

        foreach (array_chunk($data, $batchSize) as $chunk) {
            try {
                $keys = array_column($chunk, $mergeKey);
                \DB::table($config['table'])
                    ->whereIn($mergeKey, $keys)
                    ->delete();

                \DB::table($config['table'])->insert($chunk);
                $recordsSuccessful += count($chunk);
            } catch (\Exception $e) {
                $recordsFailed += count($chunk);
                Log::error("Batch upsert failed", [
                    'table' => $config['table'],
                    'merge_key' => $mergeKey,
                    'batch_size' => count($chunk),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'records_processed' => count($data),
            'records_successful' => $recordsSuccessful,
            'records_failed' => $recordsFailed
        ];
    }

    protected function updateAggregations(): void
    {
        // Update daily aggregations
        \DB::statement('CALL sp_update_daily_aggregations()');
        
        Log::info("Aggregations updated", ['batch_id' => $this->batchId]);
    }

    protected function clearCaches(): void
    {
        $cacheService = new AnalyticsCacheService();
        
        // Clear specific cache patterns based on pipeline
        switch ($this->pipelineName) {
            case 'shipments_realtime':
                $cacheService->invalidatePattern('dashboard:*');
                $cacheService->invalidatePattern('operational:*');
                break;
            case 'financial_transactions':
                $cacheService->invalidatePattern('financial:*');
                break;
            case 'performance_metrics':
                $cacheService->invalidatePattern('performance:*');
                break;
        }
        
        Log::info("Caches cleared", ['pipeline' => $this->pipelineName]);
    }

    protected function getAuthHeaders(array $config): array
    {
        switch ($config['auth'] ?? 'none') {
            case 'bearer_token':
                return ['Authorization' => 'Bearer ' . $config['token']];
            case 'api_key':
                return ['X-API-Key' => $config['api_key']];
            case 'basic':
                return [
                    'Authorization' => 'Basic ' . base64_encode(
                        $config['username'] . ':' . $config['password']
                    )
                ];
            default:
                return [];
        }
    }

    protected function getLastExtractionTimestamp(string $source): ?string
    {
        return \DB::table('etl_batches')
            ->where('pipeline_name', $this->pipelineName)
            ->where('status', 'COMPLETED')
            ->orderBy('started_at', 'desc')
            ->value('started_at');
    }

    protected function handleError(EtlBatch $batch, \Exception $e): void
    {
        $this->retryCount++;
        
        if ($this->retryCount <= $this->tries) {
            $batch->updateStatus('RETRY', "Attempt {$this->retryCount} failed: " . $e->getMessage());
            Log::warning("ETL job retrying", [
                'batch_id' => $this->batchId,
                'attempt' => $this->retryCount,
                'error' => $e->getMessage()
            ]);
        } else {
            $batch->updateStatus('FAILED', $e->getMessage());
            Log::error("ETL job failed permanently", [
                'batch_id' => $this->batchId,
                'attempts' => $this->retryCount,
                'error' => $e->getMessage()
            ]);
        }
    }
}