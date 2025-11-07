<?php

namespace App\Services\ApiGateway\Middleware;

use App\Services\ApiGateway\ApiGatewayContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

/**
 * Data transformation middleware for API Gateway
 */
class TransformationMiddleware implements MiddlewareInterface
{
    protected $next;
    protected $transformer;

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
            // Get transformation configuration
            $transformConfig = $this->getTransformConfig($route);
            
            // Transform request data if configured
            if ($transformConfig['transform_request']) {
                $context = $this->transformRequest($context, $transformConfig);
            }

            // Continue to next middleware
            $result = $this->getNext() ? $this->getNext()->handle($context) : true;

            // Transform response if configured and we have a response
            if ($transformConfig['transform_response'] && $context->getResponse()) {
                $this->transformResponse($context, $transformConfig);
            }

            return $result;

        } catch (\Exception $e) {
            $context->log('error', 'Transformation middleware error', [
                'error' => $e->getMessage(),
                'route' => $route['path'] ?? 'unknown',
            ]);

            return $this->handleTransformationError($context, $e);
        }
    }

    /**
     * Get transformation configuration for the route
     */
    protected function getTransformConfig(array $route): array
    {
        $config = [
            'transform_request' => true,
            'transform_response' => true,
            'request_transformers' => [],
            'response_transformers' => [],
            'field_mappings' => [],
            'data_format' => 'json', // json, xml, csv
            'normalize_fields' => true,
            'add_metadata' => true,
        ];

        // Get route-specific configuration
        if (isset($route['transform_config'])) {
            $routeConfig = $route['transform_config'];
            
            if (is_array($routeConfig)) {
                $config = array_merge($config, $routeConfig);
            }
        }

        return $config;
    }

    /**
     * Transform request data
     */
    protected function transformRequest(ApiGatewayContext $context, array $config): ApiGatewayContext
    {
        $request = $context->getRequest();
        $transformedData = $request->all();

        // Apply field mappings
        if (!empty($config['field_mappings'])) {
            $transformedData = $this->applyFieldMappings($transformedData, $config['field_mappings']);
        }

        // Apply custom transformers
        if (!empty($config['request_transformers'])) {
            foreach ($config['request_transformers'] as $transformer) {
                if (method_exists($this, $transformer)) {
                    $transformedData = $this->$transformer($transformedData, $context);
                }
            }
        }

        // Normalize field names
        if ($config['normalize_fields']) {
            $transformedData = $this->normalizeFieldNames($transformedData);
        }

        // Add metadata
        if ($config['add_metadata']) {
            $transformedData['_metadata'] = $this->generateRequestMetadata($context);
        }

        // Set transformed data
        $context->setTransformedData($transformedData);
        
        return $context;
    }

    /**
     * Transform response data
     */
    protected function transformResponse(ApiGatewayContext $context, array $config): void
    {
        $response = $context->getResponse();
        $content = $response->getContent();
        
        try {
            // Parse response content
            $data = json_decode($content, true);
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                // If not JSON, try to parse as other format or leave as is
                return;
            }

            // Apply response transformers
            if (!empty($config['response_transformers'])) {
                foreach ($config['response_transformers'] as $transformer) {
                    if (method_exists($this, $transformer)) {
                        $data = $this->$transformer($data, $context);
                    }
                }
            }

            // Apply reverse field mappings
            if (!empty($config['field_mappings'])) {
                $data = $this->applyReverseFieldMappings($data, $config['field_mappings']);
            }

            // Add response metadata
            if ($config['add_metadata']) {
                $data['_metadata'] = array_merge(
                    $data['_metadata'] ?? [],
                    $this->generateResponseMetadata($context)
                );
            }

            // Convert back to specified format
            $transformedContent = $this->formatData($data, $config['data_format']);
            
            // Update response content
            $response->setContent($transformedContent);
            $context->setResponse($response);

        } catch (\Exception $e) {
            $context->log('warning', 'Response transformation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Apply field mappings
     */
    protected function applyFieldMappings(array $data, array $mappings): array
    {
        $transformed = [];
        
        foreach ($mappings as $targetField => $sourceField) {
            $value = Arr::get($data, $sourceField);
            if ($value !== null) {
                $transformed[$targetField] = $value;
            }
        }
        
        return $transformed;
    }

    /**
     * Apply reverse field mappings
     */
    protected function applyReverseFieldMappings(array $data, array $mappings): array
    {
        $transformed = $data;
        
        foreach ($mappings as $targetField => $sourceField) {
            if (isset($data[$targetField])) {
                $transformed[$sourceField] = $data[$targetField];
                unset($transformed[$targetField]);
            }
        }
        
        return $transformed;
    }

    /**
     * Normalize field names to snake_case
     */
    protected function normalizeFieldNames(array $data): array
    {
        $normalized = [];
        
        foreach ($data as $key => $value) {
            $normalizedKey = strtolower(preg_replace('/([A-Z])/', '_$1', $key));
            $normalizedKey = ltrim($normalizedKey, '_');
            
            if (is_array($value)) {
                $normalized[$normalizedKey] = $this->normalizeFieldNames($value);
            } else {
                $normalized[$normalizedKey] = $value;
            }
        }
        
        return $normalized;
    }

    /**
     * Generate request metadata
     */
    protected function generateRequestMetadata(ApiGatewayContext $context): array
    {
        $request = $context->getRequest();
        
        return [
            'timestamp' => now()->toISOString(),
            'request_id' => $context->getMetadata('request_id'),
            'client_ip' => $context->getMetadata('client_ip'),
            'user_agent' => $context->getMetadata('user_agent'),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'processing_time' => 0, // Will be updated at end
        ];
    }

    /**
     * Generate response metadata
     */
    protected function generateResponseMetadata(ApiGatewayContext $context): array
    {
        return [
            'processing_time' => $context->getProcessingTime(),
            'gateway_version' => config('api_gateway.version', '1.0.0'),
            'transformed' => true,
        ];
    }

    /**
     * Format data to specified format
     */
    protected function formatData(array $data, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'xml':
                return $this->arrayToXml($data);
            case 'csv':
                return $this->arrayToCsv($data);
            default:
                return json_encode($data);
        }
    }

    /**
     * Convert array to XML
     */
    protected function arrayToXml(array $data, string $rootElement = 'response'): string
    {
        $xml = new \SimpleXMLElement("<{$rootElement}/>");
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }

    /**
     * Recursive helper for array to XML conversion
     */
    protected function arrayToXmlRecursive(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * Convert array to CSV
     */
    protected function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        $headers = array_keys($data);
        fputcsv($output, $headers);
        
        // Write data
        fputcsv($output, $data);
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Custom transformer: Snake case field names
     */
    protected function snakeCaseTransformer(array $data, ApiGatewayContext $context): array
    {
        return $this->normalizeFieldNames($data);
    }

    /**
     * Custom transformer: Convert timestamps
     */
    protected function timestampTransformer(array $data, ApiGatewayContext $context): array
    {
        $timestampFields = ['created_at', 'updated_at', 'timestamp', 'date'];
        
        foreach ($timestampFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtotime($data[$field]);
            }
        }
        
        return $data;
    }

    /**
     * Custom transformer: Currency formatting
     */
    protected function currencyTransformer(array $data, ApiGatewayContext $context): array
    {
        $currencyFields = ['amount', 'price', 'cost', 'revenue', 'total'];
        
        foreach ($currencyFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = number_format($data[$field], 2, '.', '');
            }
        }
        
        return $data;
    }

    /**
     * Custom transformer: Remove sensitive fields
     */
    protected function sanitizeTransformer(array $data, ApiGatewayContext $context): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
        
        return $data;
    }

    /**
     * Handle transformation error
     */
    protected function handleTransformationError(ApiGatewayContext $context, \Exception $e): bool
    {
        $context->log('error', 'Transformation service error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Continue processing even if transformation fails
        return $this->getNext() ? $this->getNext()->handle($context) : true;
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
        return 40; // Fourth priority
    }

    /**
     * Check if middleware should be executed for this request
     */
    public function shouldExecute(ApiGatewayContext $context): bool
    {
        $route = $context->getRoute();
        
        // Always execute transformation for now
        // Can be made configurable based on route settings
        return true;
    }

    /**
     * Get middleware name
     */
    public function getName(): string
    {
        return 'TransformationMiddleware';
    }
}