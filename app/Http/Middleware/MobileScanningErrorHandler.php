<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MobileScanningErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
            
            // Log successful mobile scan requests for monitoring
            if ($this->isMobileScanningEndpoint($request)) {
                $this->logSuccessfulScan($request, $response);
            }
            
            return $response;
            
        } catch (ValidationException $e) {
            return $this->handleValidationError($request, $e);
        } catch (NotFoundHttpException $e) {
            return $this->handleNotFoundError($request, $e);
        } catch (UnauthorizedHttpException $e) {
            return $this->handleUnauthorizedError($request, $e);
        } catch (TooManyRequestsHttpException $e) {
            return $this->handleRateLimitError($request, $e);
        } catch (BadRequestHttpException $e) {
            return $this->handleBadRequestError($request, $e);
        } catch (MethodNotAllowedHttpException $e) {
            return $this->handleMethodNotAllowedError($request, $e);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->handleDatabaseError($request, $e);
        } catch (\Exception $e) {
            return $this->handleGenericError($request, $e);
        }
    }

    /**
     * Handle validation errors
     */
    private function handleValidationError(Request $request, ValidationException $e): JsonResponse
    {
        $errors = $e->errors();
        $firstError = reset($errors)[0] ?? 'Validation failed';
        
        Log::warning('Mobile scanning validation error', [
            'request' => $request->all(),
            'errors' => $errors,
            'user_agent' => $request->userAgent(),
            'device_id' => $request->header('X-Device-ID'),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'message' => $firstError,
            'code' => 'VALIDATION_ERROR',
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], 422);
    }

    /**
     * Handle 404 errors
     */
    private function handleNotFoundError(Request $request, NotFoundHttpException $e): JsonResponse
    {
        Log::warning('Mobile scanning resource not found', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'device_id' => $request->header('X-Device-ID'),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Resource not found',
            'message' => 'The requested resource could not be found',
            'code' => 'NOT_FOUND',
            'timestamp' => now()->toISOString(),
        ], 404);
    }

    /**
     * Handle unauthorized errors
     */
    private function handleUnauthorizedError(Request $request, UnauthorizedHttpException $e): JsonResponse
    {
        Log::warning('Mobile scanning unauthorized access', [
            'url' => $request->fullUrl(),
            'device_id' => $request->header('X-Device-ID'),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Unauthorized access',
            'message' => 'Device authentication required or invalid',
            'code' => 'UNAUTHORIZED',
            'timestamp' => now()->toISOString(),
        ], 401);
    }

    private function handleBadRequestError(Request $request, BadRequestHttpException $e): JsonResponse
    {
        Log::notice('Mobile scanning bad request', [
            'url' => $request->fullUrl(),
            'message' => $e->getMessage(),
            'device_id' => $request->header('X-Device-ID'),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Bad request',
            'message' => $e->getMessage(),
            'code' => 'BAD_REQUEST',
            'timestamp' => now()->toISOString(),
        ], 400);
    }

    private function handleRateLimitError(Request $request, TooManyRequestsHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage() ?: 'Rate limit exceeded',
            'code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            'timestamp' => now()->toISOString(),
        ], 429);
    }

    /**
     * Handle method not allowed errors
     */
    private function handleMethodNotAllowedError(Request $request, MethodNotAllowedHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Method not allowed',
            'message' => 'The HTTP method is not allowed for this endpoint',
            'code' => 'METHOD_NOT_ALLOWED',
            'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
            'timestamp' => now()->toISOString(),
        ], 405);
    }

    /**
     * Handle database errors
     */
    private function handleDatabaseError(Request $request, \Illuminate\Database\QueryException $e): JsonResponse
    {
        Log::error('Mobile scanning database error', [
            'message' => $e->getMessage(),
            'query' => $e->getSql(),
            'bindings' => $e->getBindings(),
            'url' => $request->fullUrl(),
            'device_id' => $request->header('X-Device-ID'),
        ]);

        // Check if it's a connection error (offline scenario)
        if (str_contains($e->getMessage(), 'Connection refused') || 
            str_contains($e->getMessage(), 'could not find driver')) {
            return response()->json([
                'success' => false,
                'error' => 'Database connection error',
                'message' => 'Unable to connect to database - please try again later',
                'code' => 'DATABASE_CONNECTION_ERROR',
                'retryable' => true,
                'timestamp' => now()->toISOString(),
            ], 503);
        }

        return response()->json([
            'success' => false,
            'error' => 'Database error',
            'message' => 'A database error occurred - please try again',
            'code' => 'DATABASE_ERROR',
            'timestamp' => now()->toISOString(),
        ], 500);
    }

    /**
     * Handle generic errors
     */
    private function handleGenericError(Request $request, \Exception $e): JsonResponse
    {
        Log::error('Mobile scanning generic error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'url' => $request->fullUrl(),
            'device_id' => $request->header('X-Device-ID'),
            'user_agent' => $request->userAgent(),
        ]);

        $isClientError = $e instanceof \RuntimeException || 
                        $e instanceof \InvalidArgumentException ||
                        $e instanceof \UnexpectedValueException;

        $statusCode = $isClientError ? 400 : 500;
        $errorCode = $isClientError ? 'CLIENT_ERROR' : 'SERVER_ERROR';

        return response()->json([
            'success' => false,
            'error' => $isClientError ? 'Client error' : 'Server error',
            'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            'code' => $errorCode,
            'timestamp' => now()->toISOString(),
            'debug' => config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        ], $statusCode);
    }

    /**
     * Check if request is for mobile scanning endpoint
     */
    private function isMobileScanningEndpoint(Request $request): bool
    {
        return str_contains($request->path(), 'mobile') || 
               str_contains($request->path(), 'scan') ||
               str_contains($request->path(), 'device');
    }

    /**
     * Log successful scan for monitoring
     */
    private function logSuccessfulScan(Request $request, $response): void
    {
        // Only log successful responses
        if ($response->isSuccessful()) {
            Log::info('Mobile scan successful', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'device_id' => $request->header('X-Device-ID'),
                'response_status' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}