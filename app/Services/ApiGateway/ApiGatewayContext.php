<?php

namespace App\Services\ApiGateway;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ApiGatewayContext
{
    protected Request $request;
    protected array $route;
    protected array $data = [];
    protected ?Response $response = null;
    protected array $headers = [];
    protected array $errors = [];
    protected array $metadata = [];

    public function __construct(Request $request, array $route)
    {
        $this->request = $request;
        $this->route = $route;
        $this->initializeContext();
    }

    /**
     * Initialize context with default values
     */
    protected function initializeContext(): void
    {
        $this->metadata = [
            'start_time' => microtime(true),
            'request_id' => $this->request->get('request_id'),
            'client_ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ];
    }

    /**
     * Get the request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the route configuration
     */
    public function getRoute(): array
    {
        return $this->route;
    }

    /**
     * Get route parameter
     */
    public function getRouteParam(string $key, $default = null)
    {
        return $this->route[$key] ?? $default;
    }

    /**
     * Get request data
     */
    public function getData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }
        
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data in context
     */
    public function setData(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get request header
     */
    public function getHeader(string $key, $default = null)
    {
        return $this->request->header($key, $default);
    }

    /**
     * Get response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Set response
     */
    public function setResponse(Response $response): self
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Add response header
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add error
     */
    public function addError(string $code, string $message, array $context = []): self
    {
        $this->errors[] = [
            'code' => $code,
            'message' => $message,
            'context' => $context,
            'timestamp' => now(),
        ];
        return $this;
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if context has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get metadata
     */
    public function getMetadata(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->metadata;
        }
        
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set metadata
     */
    public function setMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Check if request is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->getData('is_authenticated', false);
    }

    /**
     * Get authenticated user
     */
    public function getUser()
    {
        return $this->getData('user');
    }

    /**
     * Set authenticated user
     */
    public function setUser($user): self
    {
        $this->setData('user', $user);
        $this->setData('is_authenticated', true);
        return $this;
    }

    /**
     * Get rate limit info
     */
    public function getRateLimitInfo(): array
    {
        return $this->getData('rate_limit_info', [
            'limit' => 0,
            'remaining' => 0,
            'reset_time' => null,
        ]);
    }

    /**
     * Set rate limit info
     */
    public function setRateLimitInfo(array $info): self
    {
        $this->setData('rate_limit_info', $info);
        return $this;
    }

    /**
     * Get transformation data
     */
    public function getTransformedData()
    {
        return $this->getData('transformed_data');
    }

    /**
     * Set transformed data
     */
    public function setTransformedData($data): self
    {
        $this->setData('transformed_data', $data);
        return $this;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->getData('validation_errors', []);
    }

    /**
     * Set validation errors
     */
    public function setValidationErrors(array $errors): self
    {
        $this->setData('validation_errors', $errors);
        return $this;
    }

    /**
     * Log context information
     */
    public function log(string $level = 'info', string $message = '', array $context = []): self
    {
        $logContext = array_merge($context, [
            'request_id' => $this->getMetadata('request_id'),
            'route' => $this->getRouteParam('path'),
            'method' => $this->getRequest()->method(),
            'client_ip' => $this->getMetadata('client_ip'),
        ]);

        Log::log($level, $message, $logContext);
        return $this;
    }

    /**
     * Get processing time
     */
    public function getProcessingTime(): float
    {
        $startTime = $this->getMetadata('start_time', 0);
        return microtime(true) - $startTime;
    }

    /**
     * Check if content type is JSON
     */
    public function isJsonRequest(): bool
    {
        $contentType = $this->getHeader('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * Check if request accepts JSON
     */
    public function acceptsJson(): bool
    {
        $acceptHeader = $this->getHeader('Accept', '');
        return str_contains($acceptHeader, 'application/json') || $acceptHeader === '*/*';
    }

    /**
     * Get client information
     */
    public function getClientInfo(): array
    {
        return [
            'ip' => $this->getMetadata('client_ip'),
            'user_agent' => $this->getMetadata('user_agent'),
            'accept' => $this->getHeader('Accept'),
            'content_type' => $this->getHeader('Content-Type'),
        ];
    }

    /**
     * Create success response
     */
    public function createSuccessResponse($data = null, int $statusCode = 200): Response
    {
        $responseData = [
            'success' => true,
            'data' => $data,
            'meta' => [
                'request_id' => $this->getMetadata('request_id'),
                'processing_time' => $this->getProcessingTime(),
            ],
        ];

        if ($this->hasErrors()) {
            $responseData['warnings'] = $this->getErrors();
        }

        $response = response()->json($responseData, $statusCode);
        $this->setResponse($response);
        
        return $response;
    }

    /**
     * Create error response
     */
    public function createErrorResponse(string $message, string $code = 'GENERIC_ERROR', int $statusCode = 400, array $context = []): Response
    {
        $responseData = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'context' => $context,
            ],
            'meta' => [
                'request_id' => $this->getMetadata('request_id'),
                'processing_time' => $this->getProcessingTime(),
            ],
        ];

        $response = response()->json($responseData, $statusCode);
        $this->setResponse($response);
        
        return $response;
    }

    /**
     * Clone context for parallel processing
     */
    public function clone(): self
    {
        $clone = new self($this->request, $this->route);
        $clone->data = $this->data;
        $clone->headers = $this->headers;
        $clone->errors = $this->errors;
        $clone->metadata = $this->metadata;
        
        return $clone;
    }

    /**
     * Merge data from another context
     */
    public function merge(self $other): self
    {
        $this->data = array_merge($this->data, $other->getData());
        $this->headers = array_merge($this->headers, $other->getHeaders());
        $this->errors = array_merge($this->errors, $other->getErrors());
        $this->metadata = array_merge($this->metadata, $other->getMetadata());
        
        return $this;
    }
}