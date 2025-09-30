<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (! in_array($request->method(), $methods)) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            return response()->json(['error' => 'Idempotency-Key header required'], 400);
        }

        $userId = $request->user() ? $request->user()->id : 'guest';
        $path = $request->path();
        $body = $request->getContent();

        $key = hash('sha256', $idempotencyKey.$body.$path.$userId);

        $cachedResponse = Redis::get($key);
        if ($cachedResponse) {
            return unserialize($cachedResponse);
        }

        $response = $next($request);

        Redis::setex($key, 1800, serialize($response)); // 30 minutes

        return $response;
    }
}
