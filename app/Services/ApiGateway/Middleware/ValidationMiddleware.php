<?php

namespace App\Services\ApiGateway\Middleware;

use App\Services\ApiGateway\ApiGatewayContext;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Request validation middleware for API Gateway
 */
class ValidationMiddleware implements MiddlewareInterface
{
    protected $next;

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
            // Get validation configuration
            $validationConfig = $this->getValidationConfig($route);
            
            if ($validationConfig['enabled']) {
                $validationResult = $this->validateRequest($request, $validationConfig);
                
                if (!$validationResult['valid']) {
                    return $this->handleValidationFailed($context, $validationResult);
                }

                // Set validated data in context
                $context->setData('validated_data', $validationResult['data']);
            }

            // Continue to next middleware
            return $this->getNext() ? $this->getNext()->handle($context) : true;

        } catch (\Exception $e) {
            $context->log('error', 'Validation middleware error', [
                'error' => $e->getMessage(),
                'route' => $route['path'] ?? 'unknown',
            ]);

            return $this->handleValidationError($context, $e);
        }
    }

    /**
     * Get validation configuration for the route
     */
    protected function getValidationConfig(array $route): array
    {
        $config = [
            'enabled' => true,
            'validate_input' => true,
            'validate_headers' => false,
            'sanitize_input' => true,
            'rules' => [],
            'custom_rules' => [],
            'required_fields' => [],
            'field_types' => [],
            'max_request_size' => 10485760, // 10MB
            'allowed_content_types' => ['application/json', 'application/xml', 'text/plain'],
        ];

        // Get route-specific configuration
        if (isset($route['validation_config'])) {
            $routeConfig = $route['validation_config'];
            
            if (is_array($routeConfig)) {
                $config = array_merge($config, $routeConfig);
            }
        }

        return $config;
    }

    /**
     * Validate the request
     */
    protected function validateRequest(Request $request, array $config): array
    {
        $errors = [];
        $validatedData = [];

        // Check request size
        if ($request->getContentLength() > $config['max_request_size']) {
            $errors[] = 'Request size exceeds maximum allowed size';
        }

        // Check content type
        if ($config['validate_input'] && !$this->isValidContentType($request, $config)) {
            $errors[] = 'Invalid or unsupported content type';
        }

        // Validate headers if required
        if ($config['validate_headers']) {
            $headerErrors = $this->validateHeaders($request, $config);
            $errors = array_merge($errors, $headerErrors);
        }

        // Validate required fields
        if (!empty($config['required_fields'])) {
            $missingFields = $this->validateRequiredFields($request, $config['required_fields']);
            $errors = array_merge($errors, $missingFields);
        }

        // Validate field types and apply custom rules
        if (!empty($config['rules']) || !empty($config['custom_rules'])) {
            $data = $request->all();
            
            // Apply custom validation rules
            if (!empty($config['custom_rules'])) {
                foreach ($config['custom_rules'] as $field => $rule) {
                    if (method_exists($this, $rule)) {
                        $result = $this->$rule($request->input($field), $field);
                        if (!$result['valid']) {
                            $errors[] = $result['message'];
                        } else {
                            $data[$field] = $result['value'];
                        }
                    }
                }
            }

            // Apply Laravel validation rules
            if (!empty($config['rules'])) {
                $validator = Validator::make($data, $config['rules']);
                
                if ($validator->fails()) {
                    $errors = array_merge($errors, $validator->errors()->all());
                } else {
                    $validatedData = $validator->validated();
                }
            }
        } else {
            // If no specific rules, use raw data
            $validatedData = $request->all();
        }

        // Sanitize data if required
        if ($config['sanitize_input'] && !empty($validatedData)) {
            $validatedData = $this->sanitizeData($validatedData);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validatedData,
        ];
    }

    /**
     * Check if content type is valid
     */
    protected function isValidContentType(Request $request, array $config): bool
    {
        $contentType = $request->header('Content-Type', '');
        
        // Allow requests without body (GET, DELETE)
        if (in_array($request->method(), ['GET', 'DELETE']) && empty($contentType)) {
            return true;
        }

        foreach ($config['allowed_content_types'] as $allowedType) {
            if (str_contains($contentType, $allowedType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate request headers
     */
    protected function validateHeaders(Request $request, array $config): array
    {
        $errors = [];
        
        // Check for required headers (example)
        $requiredHeaders = $config['required_headers'] ?? [];
        
        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                $errors[] = "Required header '{$header}' is missing";
            }
        }

        // Validate header formats (example)
        $headerRules = $config['header_rules'] ?? [];
        
        foreach ($headerRules as $header => $rule) {
            $value = $request->header($header);
            if ($value && !$this->validateHeaderValue($value, $rule)) {
                $errors[] = "Header '{$header}' format is invalid";
            }
        }

        return $errors;
    }

    /**
     * Validate header value format
     */
    protected function validateHeaderValue(string $value, string $rule): bool
    {
        switch ($rule) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'uuid':
                return Str::isUuid($value);
            case 'ip':
                return filter_var($value, FILTER_VALIDATE_IP) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            default:
                return true;
        }
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(Request $request, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            $value = $request->input($field);
            
            if ($value === null || $value === '') {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        return $errors;
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove potentially dangerous characters
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                // Recursively sanitize arrays
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Custom validation rule: Phone number
     */
    protected function validatePhoneNumber($value, string $field): array
    {
        if (empty($value)) {
            return ['valid' => true, 'value' => $value];
        }

        // Basic phone validation (can be enhanced)
        $pattern = '/^[\+]?[1-9][\d]{0,15}$/';
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $value);
        
        if (preg_match($pattern, $cleaned)) {
            return ['valid' => true, 'value' => $cleaned];
        }
        
        return ['valid' => false, 'message' => "Field '{$field}' must be a valid phone number"];
    }

    /**
     * Custom validation rule: Postal code
     */
    protected function validatePostalCode($value, string $field): array
    {
        if (empty($value)) {
            return ['valid' => true, 'value' => $value];
        }

        // Basic postal code validation
        $pattern = '/^[A-Z0-9\s\-]{3,10}$/i';
        
        if (preg_match($pattern, $value)) {
            return ['valid' => true, 'value' => strtoupper($value)];
        }
        
        return ['valid' => false, 'message' => "Field '{$field}' must be a valid postal code"];
    }

    /**
     * Handle validation failure
     */
    protected function handleValidationFailed(ApiGatewayContext $context, array $validationResult): Response
    {
        $context->log('warning', 'Request validation failed', [
            'errors' => $validationResult['errors'],
            'client_ip' => $context->getMetadata('client_ip'),
        ]);

        return $context->createErrorResponse(
            'Request validation failed',
            'VALIDATION_ERROR',
            422,
            [
                'errors' => $validationResult['errors'],
                'valid' => false,
            ]
        );
    }

    /**
     * Handle validation error
     */
    protected function handleValidationError(ApiGatewayContext $context, \Exception $e): Response
    {
        $context->log('error', 'Validation service error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $context->createErrorResponse(
            'Validation service unavailable',
            'VALIDATION_SERVICE_ERROR',
            503
        );
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
        return 30; // Third priority after auth
    }

    /**
     * Check if middleware should be executed for this request
     */
    public function shouldExecute(ApiGatewayContext $context): bool
    {
        $route = $context->getRoute();
        
        // Skip validation for certain routes if configured
        $skipPaths = config('api_gateway.skip_validation_paths', [
            '/health',
            '/status',
        ]);
        
        $path = $context->getRouteParam('path', '');
        
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get middleware name
     */
    public function getName(): string
    {
        return 'ValidationMiddleware';
    }
}