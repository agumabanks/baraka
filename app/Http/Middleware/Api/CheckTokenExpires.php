<?php

namespace App\Http\Middleware\Api;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class CheckTokenExpires
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip token checking for non-authenticated routes
        if (!$request->bearerToken()) {
            return $next($request);
        }

        try {
            $token = $request->user()->currentAccessToken();
            
            if (!$token) {
                Log::channel('api')->warning('API request without valid token', [
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'type' => 'auth.token_invalid',
                    'message' => 'Invalid or expired authentication token',
                    'timestamp' => now()->toISOString(),
                ], 401);
            }
            
            // Check if token has expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                Log::channel('api')->info('Expired token detected and revoked', [
                    'token_id' => $token->id,
                    'user_id' => $request->user()->id,
                    'expires_at' => $token->expires_at->toISOString(),
                    'ip' => $request->ip(),
                ]);
                
                // Delete expired token
                $token->delete();
                
                return response()->json([
                    'success' => false,
                    'type' => 'auth.token_expired',
                    'message' => 'The authentication token has expired. Please log in again.',
                    'code' => 'TOKEN_EXPIRED',
                    'timestamp' => now()->toISOString(),
                ], 401);
            }
            
            // Check if token is about to expire (within 30 days)
            if ($token->expires_at) {
                $daysUntilExpiry = now()->diffInDays($token->expires_at);
                
                if ($daysUntilExpiry <= 30 && $daysUntilExpiry > 0) {
                    // Add warning header for client to handle token refresh
                    $request->headers->set('X-Token-Expiry-Warning', $daysUntilExpiry);
                    $request->headers->set('X-Expires-At', $token->expires_at->toISOString());
                }
            }
            
            // Log successful token validation (for debugging)
            if (config('app.debug')) {
                Log::channel('api')->debug('API token validated', [
                    'token_id' => $token->id,
                    'user_id' => $request->user()->id,
                    'expires_at' => $token->expires_at?->toISOString(),
                    'path' => $request->path(),
                ]);
            }
            
        } catch (\Exception $e) {
            Log::channel('api')->error('Token validation error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'success' => false,
                'type' => 'auth.token_validation_error',
                'message' => 'Failed to validate authentication token',
                'timestamp' => now()->toISOString(),
            ], 500);
        }

        return $next($request);
    }
}
