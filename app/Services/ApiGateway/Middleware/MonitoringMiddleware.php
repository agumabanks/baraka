<?php

namespace App\Services\ApiGateway\Middleware;

use App\Services\ApiGateway\ApiGatewayContext;
use App\Services\ApiGateway\Monitoring\MetricsCollector;
use App\Services\ApiGateway\Monitoring\LogCollector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Monitoring and logging middleware for API Gateway
 */
class MonitoringMiddleware implements MiddlewareInterface
{
    protected $next;
    protected $metricsCollector;
    protected $logCollector;

    public function __construct()
    {
        $this->metricsCollector = new MetricsCollector();
        $this->logCollector = new LogCollector();
    }

    /**
     * Process the request through the middleware
     */
    public function handle(ApiGatewayContext $context): bool
    {
        if (!$this->shouldExecute($context)) {
            return $this->getNext() ? $this->getNext()->handle($context) : true;
        }

        $request = $context->getRequest();
        $route = $context->getRoute();

        try {
            // Start monitoring
            $this->startRequestMonitoring($context);

            // Process request through next middleware
            $result = $this->getNext() ? $this->getNext()->handle($context) : true;

            // End monitoring and record metrics
            $this->endRequestMonitoring($context);

            return $result;

        } catch (\Exception $e) {
            // Record error metrics
            $this->recordErrorMetrics($context, $e);
            
            // Log error
            $context->log('error', 'Monitoring middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to be handled by other middleware
        }
    }

    /**
     * Start request monitoring
     */
    protected function startRequestMonitoring(ApiGatewayContext $context): void
    {
        $request = $context->getRequest();
        $route = $context->getRoute();

        // Set monitoring metadata
        $context->setMetadata('monitoring_start_time', microtime(true));
        $context->setMetadata('memory_usage_start', memory_get_usage(true));
        $context->setMetadata('request_size', $request->getContentLength() ?: 0);

        // Record incoming request metrics
        $this->metricsCollector->recordIncomingRequest([
            'method' => $request->method(),
            'path' => $route['path'] ?? $request->path(),
            'user_agent' => $request->userAgent(),
            'client_ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->getContentLength() ?: 0,
        ]);

        // Log incoming request
        $this->logCollector->logRequest($context, 'incoming');
    }

    /**
     * End request monitoring
     */
    protected function endRequestMonitoring(ApiGatewayContext $context): void
    {
        $request = $context->getRequest();
        $response = $context->getResponse();
        $route = $context->getRoute();

        $startTime = $context->getMetadata('monitoring_start_time', 0);
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $responseSize = $response ? strlen($response->getContent()) : 0;
        $endMemoryUsage = memory_get_usage(true);
        $memoryDelta = $endMemoryUsage - $context->getMetadata('memory_usage_start', 0);

        // Update metadata
        $context->setMetadata('processing_time', $processingTime);
        $context->setMetadata('response_size', $responseSize);
        $context->setMetadata('memory_usage_end', $endMemoryUsage);
        $context->setMetadata('memory_delta', $memoryDelta);

        // Record response metrics
        if ($response) {
            $this->metricsCollector->recordResponse([
                'method' => $request->method(),
                'path' => $route['path'] ?? $request->path(),
                'status_code' => $response->getStatusCode(),
                'processing_time' => $processingTime,
                'response_size' => $responseSize,
                'memory_usage' => $memoryDelta,
            ]);
        }

        // Log outgoing response
        $this->logCollector->logResponse($context, 'outgoing');

        // Record performance metrics
        $this->recordPerformanceMetrics($context);

        // Check for performance issues
        $this->checkPerformanceThresholds($context);
    }

    /**
     * Record error metrics
     */
    protected function recordErrorMetrics(ApiGatewayContext $context, \Exception $e): void
    {
        $request = $context->getRequest();
        $route = $context->getRoute();

        $this->metricsCollector->recordError([
            'method' => $request->method(),
            'path' => $route['path'] ?? $request->path(),
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'user_agent' => $request->userAgent(),
            'client_ip' => $request->ip(),
        ]);
    }

    /**
     * Record performance metrics
     */
    protected function recordPerformanceMetrics(ApiGatewayContext $context): void
    {
        $metrics = [
            'request_id' => $context->getMetadata('request_id'),
            'method' => $context->getRequest()->method(),
            'path' => $context->getRouteParam('path'),
            'processing_time' => $context->getMetadata('processing_time'),
            'memory_usage' => $context->getMetadata('memory_delta'),
            'request_size' => $context->getMetadata('request_size'),
            'response_size' => $context->getMetadata('response_size'),
            'user_id' => $context->getUser()?->id,
            'is_authenticated' => $context->isAuthenticated(),
        ];

        $this->metricsCollector->recordPerformance($metrics);
    }

    /**
     * Check performance thresholds
     */
    protected function checkPerformanceThresholds(ApiGatewayContext $context): void
    {
        $processingTime = $context->getMetadata('processing_time', 0);
        $memoryDelta = $context->getMetadata('memory_delta', 0);

        $alerts = [];

        // Check processing time threshold
        $slowRequestThreshold = config('api_gateway.monitoring.slow_request_threshold', 2000); // 2 seconds
        if ($processingTime > $slowRequestThreshold) {
            $alerts[] = [
                'type' => 'slow_request',
                'threshold' => $slowRequestThreshold,
                'actual' => $processingTime,
                'message' => "Slow request detected: {$processingTime}ms",
            ];
        }

        // Check memory usage threshold
        $highMemoryThreshold = config('api_gateway.monitoring.high_memory_threshold', 10485760); // 10MB
        if ($memoryDelta > $highMemoryThreshold) {
            $alerts[] = [
                'type' => 'high_memory',
                'threshold' => $highMemoryThreshold,
                'actual' => $memoryDelta,
                'message' => "High memory usage detected: " . number_format($memoryDelta / 1024 / 1024, 2) . "MB",
            ];
        }

        // Log alerts
        foreach ($alerts as $alert) {
            $context->log('warning', $alert['message'], [
                'type' => $alert['type'],
                'processing_time' => $processingTime,
                'memory_delta' => $memoryDelta,
                'path' => $context->getRouteParam('path'),
            ]);

            // Send alert to monitoring system
            $this->sendPerformanceAlert($context, $alert);
        }
    }

    /**
     * Send performance alert
     */
    protected function sendPerformanceAlert(ApiGatewayContext $context, array $alert): void
    {
        $alertData = [
            'timestamp' => now()->toISOString(),
            'request_id' => $context->getMetadata('request_id'),
            'path' => $context->getRouteParam('path'),
            'method' => $context->getRequest()->method(),
            'client_ip' => $context->getMetadata('client_ip'),
            'alert_type' => $alert['type'],
            'threshold' => $alert['threshold'],
            'actual_value' => $alert['actual'],
            'processing_time' => $context->getMetadata('processing_time'),
            'memory_usage' => $context->getMetadata('memory_delta'),
        ];

        // Store alert in database
        DB::table('api_performance_alerts')->insert($alertData);

        // Send to monitoring system (e.g., PagerDuty, Slack, etc.)
        $this->sendExternalAlert($alertData);
    }

    /**
     * Send external alert
     */
    protected function sendExternalAlert(array $alertData): void
    {
        // Implementation for sending alerts to external monitoring services
        // This could integrate with PagerDuty, Slack, email, etc.
        
        try {
            // Example: Send to Slack webhook
            $webhookUrl = config('api_gateway.monitoring.slack_webhook');
            if ($webhookUrl) {
                $this->sendSlackAlert($webhookUrl, $alertData);
            }

            // Example: Send to PagerDuty
            $pagerDutyKey = config('api_gateway.monitoring.pagerduty_key');
            if ($pagerDutyKey) {
                $this->sendPagerDutyAlert($pagerDutyKey, $alertData);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send external alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
        }
    }

    /**
     * Send Slack alert
     */
    protected function sendSlackAlert(string $webhookUrl, array $alertData): void
    {
        $message = [
            'text' => "🚨 API Gateway Performance Alert",
            'attachments' => [
                [
                    'color' => 'warning',
                    'fields' => [
                        [
                            'title' => 'Alert Type',
                            'value' => $alertData['alert_type'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Path',
                            'value' => $alertData['path'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Processing Time',
                            'value' => $alertData['processing_time'] . 'ms',
                            'short' => true,
                        ],
                        [
                            'title' => 'Memory Usage',
                            'value' => number_format($alertData['memory_usage'] / 1024 / 1024, 2) . 'MB',
                            'short' => true,
                        ],
                    ],
                    'footer' => 'API Gateway Monitor',
                    'ts' => time(),
                ]
            ]
        ];

        $client = new \GuzzleHttp\Client();
        $client->post($webhookUrl, [
            'json' => $message,
            'timeout' => 5,
        ]);
    }

    /**
     * Send PagerDuty alert
     */
    protected function sendPagerDutyAlert(string $apiKey, array $alertData): void
    {
        $payload = [
            'routing_key' => $apiKey,
            'event_action' => 'trigger',
            'payload' => [
                'summary' => "API Gateway Performance Issue: {$alertData['alert_type']} on {$alertData['path']}",
                'source' => 'api-gateway',
                'severity' => 'warning',
                'custom_details' => $alertData,
            ]
        ];

        $client = new \GuzzleHttp\Client();
        $client->post('https://events.pagerduty.com/v2/enqueue', [
            'json' => $payload,
            'timeout' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Get monitoring statistics
     */
    public function getStatistics(): array
    {
        return [
            'metrics_summary' => $this->metricsCollector->getSummary(),
            'log_stats' => $this->logCollector->getStatistics(),
            'recent_alerts' => DB::table('api_performance_alerts')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Set the next middleware in the chain
     */
    public function setNext(MiddlewareInterface $next): self
    {
        $this->next = $next;
        return $this;
    }

    /**
     * Get the next middleware in the chain
     */
    public function getNext(): ?MiddlewareInterface
    {
        return $this->next;
    }

    /**
     * Get middleware priority
     */
    public function getPriority(): int
    {
        return 50; // Last priority in the chain
    }

    /**
     * Check if middleware should be executed for this request
     */
    public function shouldExecute(ApiGatewayContext $context): bool
    {
        $route = $context->getRoute();
        
        // Always monitor requests for performance insights
        return true;
    }

    /**
     * Get middleware name
     */
    public function getName(): string
    {
        return 'MonitoringMiddleware';
    }
}