<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyResponse;

class ValidateApiJson
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Force JSON response for API routes
        $request->headers->set('Accept', 'application/json');
        
        // Add JSON validation headers
        $request->headers->set('X-Content-Type-Options', 'nosniff');
        $request->headers->set('X-Frame-Options', 'DENY');
        $request->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Validate JSON payload for POST/PUT/PATCH requests
        $method = $request->method();
        
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $content = $request->getContent();
            
            if (!empty($content) && !json_decode($content)) {
                Log::channel('api')->warning('Invalid JSON payload detected', [
                    'method' => $method,
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 200),
                    'user_agent' => $request->userAgent(),
                ]);
                
                return $this->jsonErrorResponse([
                    'success' => false,
                    'type' => 'validation.invalid_json',
                    'message' => 'The request body contains invalid JSON.',
                    'error_code' => 'VALIDATION_JSON_INVALID',
                    'details' => [
                        'received_content_type' => $request->header('Content-Type'),
                        'expected_content_type' => 'application/json',
                    ],
                    'timestamp' => now()->toISOString(),
                ], 400);
            }
            
            // Additional JSON validation
            if (!empty($content)) {
                $decodedContent = json_decode($content, true);
                
                if ($decodedContent === null) {
                    return $this->jsonErrorResponse([
                        'success' => false,
                        'type' => 'validation.json_decode_error',
                        'message' => 'Failed to decode JSON content.',
                        'json_error' => json_last_error_msg(),
                        'timestamp' => now()->toISOString(),
                    ], 400);
                }
                
                // Validate JSON size (prevent DoS attacks)
                $jsonSize = $this->getJsonSize($content);
                if ($jsonSize > config('api.max_json_size_mb', 10) * 1024 * 1024) {
                    return $this->jsonErrorResponse([
                        'success' => false,
                        'type' => 'validation.json_too_large',
                        'message' => 'JSON payload is too large.',
                        'details' => [
                            'size_mb' => round($jsonSize / 1024 / 1024, 2),
                            'max_size_mb' => config('api.max_json_size_mb', 10),
                        ],
                        'timestamp' => now()->toISOString(),
                    ], 413);
                }
                
                // Prevent deeply nested JSON (potential DoS)
                $jsonDepth = $this->getJsonDepth($decodedContent);
                if ($jsonDepth > config('api.max_json_depth', 50)) {
                    return $this->jsonErrorResponse([
                        'success' => false,
                        'type' => 'validation.json_too_deep',
                        'message' => 'JSON structure is too deeply nested.',
                        'details' => [
                            'depth' => $jsonDepth,
                            'max_depth' => config('api.max_json_depth', 50),
                        ],
                        'timestamp' => now()->toISOString(),
                    ], 413);
                }
            }
        }
        
        // Log JSON validation for debugging
        if (config('app.debug')) {
            Log::channel('api')->debug('JSON validation passed', [
                'method' => $method,
                'path' => $request->path(),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
            ]);
        }
        
        return $next($request);
    }
    
    /**
     * Create a JSON error response.
     */
    protected function jsonErrorResponse(array $data, int $status = 400): JsonResponse
    {
        return new JsonResponse($data, $status, [
            'Content-Type' => 'application/json',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
        ]);
    }
    
    /**
     * Get approximate JSON size in bytes.
     */
    protected function getJsonSize(string $json): int
    {
        return strlen($json);
    }
    
    /**
     * Get JSON nesting depth.
     */
    protected function getJsonDepth($value, $depth = 0): int
    {
        if (!is_array($value) && !is_object($value)) {
            return $depth;
        }
        
        $maxDepth = $depth;
        
        if (is_array($value)) {
            foreach ($value as $item) {
                $currentDepth = $this->getJsonDepth($item, $depth + 1);
                if ($currentDepth > $maxDepth) {
                    $maxDepth = $currentDepth;
                }
            }
        } elseif (is_object($value)) {
            foreach ($value as $item) {
                $currentDepth = $this->getJsonDepth($item, $depth + 1);
                if ($currentDepth > $maxDepth) {
                    $maxDepth = $currentDepth;
                }
            }
        }
        
        return $maxDepth;
    }
}
