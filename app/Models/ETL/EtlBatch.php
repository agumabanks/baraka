<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EtlBatch extends Model
{
    use HasFactory;

    protected $table = 'etl_batches';
    public $timestamps = true;

    protected $fillable = [
        'batch_id',
        'pipeline_name',
        'status',
        'started_at',
        'completed_at',
        'records_processed',
        'records_successful',
        'records_failed',
        'error_message',
        'execution_metrics',
        'triggered_by'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_metrics' => 'array',
        'records_processed' => 'integer',
        'records_successful' => 'integer',
        'records_failed' => 'integer'
    ];

    /**
     * Create a new ETL batch record
     */
    public static function createBatch(string $pipelineName, string $triggeredBy = null): self
    {
        $batch = new self();
        $batch->batch_id = self::generateBatchId($pipelineName);
        $batch->pipeline_name = $pipelineName;
        $batch->status = 'PENDING';
        $batch->triggered_by = $triggeredBy;
        $batch->records_processed = 0;
        $batch->records_successful = 0;
        $batch->records_failed = 0;
        $batch->started_at = now();
        
        $batch->save();
        
        Log::info("Created ETL batch", [
            'batch_id' => $batch->batch_id,
            'pipeline' => $pipelineName
        ]);
        
        return $batch;
    }

    /**
     * Update batch status
     */
    public function updateStatus(string $status, ?string $errorMessage = null, ?array $metrics = null): void
    {
        $this->status = $status;
        
        if ($metrics) {
            $this->records_processed = $metrics['records_processed'] ?? 0;
            $this->records_successful = $metrics['records_successful'] ?? 0;
            $this->records_failed = $metrics['records_failed'] ?? 0;
        }
        
        if ($status === 'COMPLETED' || $status === 'FAILED') {
            $this->completed_at = now();
            
            // Calculate execution metrics
            $executionTime = $this->started_at->diffInSeconds($this->completed_at);
            $this->execution_metrics = array_merge(
                $this->execution_metrics ?? [],
                [
                    'execution_time_seconds' => $executionTime,
                    'throughput_records_per_second' => $executionTime > 0 
                        ? round($this->records_processed / $executionTime, 2) 
                        : 0,
                    'success_rate' => $this->records_processed > 0 
                        ? round(($this->records_successful / $this->records_processed) * 100, 2)
                        : 0
                ]
            );
        }
        
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        
        $this->save();
        
        Log::info("Updated ETL batch status", [
            'batch_id' => $this->batch_id,
            'status' => $status,
            'records' => $this->records_processed
        ]);
    }

    /**
     * Get batch execution summary
     */
    public function getExecutionSummary(): array
    {
        if (!$this->completed_at) {
            return [
                'status' => $this->status,
                'duration' => $this->started_at->diffForHumans(),
                'records_processed' => $this->records_processed,
                'is_running' => true
            ];
        }

        $duration = $this->started_at->diffInMinutes($this->completed_at);
        $throughput = $this->records_processed > 0 && $duration > 0
            ? round($this->records_processed / $duration, 2)
            : 0;

        return [
            'status' => $this->status,
            'duration_minutes' => $duration,
            'records_processed' => $this->records_processed,
            'records_successful' => $this->records_successful,
            'records_failed' => $this->records_failed,
            'success_rate' => $this->records_processed > 0 
                ? round(($this->records_successful / $this->records_processed) * 100, 2)
                : 0,
            'throughput_per_minute' => $throughput,
            'is_running' => false
        ];
    }

    /**
     * Get failed batches for retry
     */
    public static function getFailedBatches(int $maxRetries = 3): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'FAILED')
            ->where('error_message', 'LIKE', '%timeout%')
            ->orWhere('error_message', 'LIKE', '%connection%')
            ->whereNotIn('batch_id', function ($query) {
                $query->select('batch_id')
                    ->from('etl_batches_retry_log')
                    ->where('retry_count', '>=', DB::raw('max_retries'));
            })
            ->limit(10)
            ->get();
    }

    /**
     * Get recent batch history
     */
    public static function getRecentHistory(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('started_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get pipeline performance metrics
     */
    public static function getPipelinePerformance(string $pipelineName, int $days = 7): array
    {
        $batches = self::where('pipeline_name', $pipelineName)
            ->where('started_at', '>=', Carbon::now()->subDays($days))
            ->where('status', 'COMPLETED')
            ->get();

        $successRate = $batches->count() > 0
            ? ($batches->where('status', 'COMPLETED')->count() / $batches->count()) * 100
            : 0;

        $avgExecutionTime = $batches->count() > 0
            ? $batches->where('status', 'COMPLETED')->avg(function ($batch) {
                return $batch->started_at->diffInSeconds($batch->completed_at);
            })
            : 0;

        $totalRecords = $batches->sum('records_processed');

        return [
            'total_batches' => $batches->count(),
            'completed_batches' => $batches->where('status', 'COMPLETED')->count(),
            'success_rate' => round($successRate, 2),
            'avg_execution_time_seconds' => round($avgExecutionTime, 2),
            'total_records_processed' => $totalRecords,
            'avg_records_per_batch' => $batches->count() > 0 ? round($totalRecords / $batches->count(), 2) : 0
        ];
    }

    /**
     * Generate batch ID
     */
    protected static function generateBatchId(string $pipelineName): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return strtoupper(substr($pipelineName, 0, 10)) . '_' . $timestamp . '_' . $random;
    }

    /**
     * Relationship to audit logs
     */
    public function auditLogs()
    {
        return $this->hasMany(EtlAuditLog::class, 'batch_id', 'batch_id');
    }

    /**
     * Relationship to data lineage
     */
    public function dataLineage()
    {
        return $this->hasMany(EtlDataLineage::class, 'batch_id', 'batch_id');
    }

    /**
     * Relationship to data quality violations
     */
    public function qualityViolations()
    {
        return $this->hasMany(EtlDataQualityViolation::class, 'batch_id', 'batch_id');
    }

    /**
     * Relationship to anomalies
     */
    public function anomalies()
    {
        return $this->hasMany(EtlAnomalyDetection::class, 'batch_id', 'batch_id');
    }

    // Define the related models (these should be created as separate model files)
    
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'COMPLETED');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }
    
    public function scopeRunning($query)
    {
        return $query->where('status', 'RUNNING');
    }
    
    public function scopeByPipeline($query, string $pipelineName)
    {
        return $query->where('pipeline_name', $pipelineName);
    }
}

// Additional model classes for relationships

class EtlAuditLog extends Model
{
    use HasFactory;
    protected $table = 'etl_audit_log';
    public $timestamps = true;
    protected $fillable = [
        'table_name', 'record_id', 'operation', 'change_type', 'before_values', 
        'after_values', 'changed_fields', 'batch_id', 'source_system', 'user_id',
        'ip_address', 'user_agent', 'data_quality_score', 'validation_errors',
        'anomaly_flags', 'created_at', 'updated_at'
    ];
    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'changed_fields' => 'array',
        'validation_errors' => 'array',
        'anomaly_flags' => 'array'
    ];
}

class EtlDataLineage extends Model
{
    use HasFactory;
    protected $table = 'etl_data_lineage';
    public $timestamps = false;
    protected $fillable = [
        'source_table', 'source_record_id', 'target_table', 'target_record_id',
        'transformation_rules', 'batch_id', 'created_at'
    ];
    protected $casts = [
        'transformation_rules' => 'array'
    ];
}

class EtlDataQualityViolation extends Model
{
    use HasFactory;
    protected $table = 'etl_data_quality_violations';
    public $timestamps = true;
    protected $fillable = [
        'rule_id', 'table_name', 'record_id', 'violation_type', 'violation_description',
        'violation_details', 'severity', 'status', 'batch_id', 'resolved_by',
        'resolution_notes', 'resolved_at', 'created_at', 'updated_at'
    ];
    protected $casts = [
        'violation_details' => 'array'
    ];
}

class EtlAnomalyDetection extends Model
{
    use HasFactory;
    protected $table = 'etl_anomaly_detection';
    public $timestamps = true;
    protected $fillable = [
        'table_name', 'record_id', 'anomaly_type', 'anomaly_category', 'description',
        'severity_score', 'detection_method', 'anomaly_data', 'context_data',
        'batch_id', 'status', 'investigation_notes', 'investigated_by',
        'investigated_at', 'created_at', 'updated_at'
    ];
    protected $casts = [
        'anomaly_data' => 'array',
        'context_data' => 'array'
    ];
}