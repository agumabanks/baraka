<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();

        $response = $next($request);

        // Check if origin is allowed
        if ($this->isOriginAllowed($origin, $allowedOrigins)) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response->header('Access-Control-Allow-Methods', $this->getAllowedMethods());
            $response->header('Access-Control-Allow-Headers', $this->getAllowedHeaders());
            $response->header('Access-Control-Max-Age', '3600');
        }

        return $response;
    }

    private function getAllowedOrigins(): array
    {
        $origins = config('cors.allowed_origins', []);

        // Add dynamic origins from environment
        if (config('app.env') === 'local') {
            $origins[] = 'http://localhost:3000';
            $origins[] = 'http://localhost:8000';
            $origins[] = 'http://127.0.0.1:3000';
        }

        // Add configured domain origins
        if (config('app.url')) {
            $origins[] = config('app.url');
        }

        return array_filter($origins);
    }

    private function getAllowedMethods(): string
    {
        return implode(', ', config('cors.allowed_methods', [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS',
        ]));
    }

    private function getAllowedHeaders(): string
    {
        return implode(', ', config('cors.allowed_headers', [
            'Accept',
            'Accept-Language',
            'Content-Language',
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'X-CSRF-Token',
            'Idempotency-Key',
            'X-Webhook-Signature',
        ]));
    }

    private function isOriginAllowed(?string $origin, array $allowedOrigins): bool
    {
        if (!$origin) {
            return false;
        }

        // Check exact match
        if (in_array($origin, $allowedOrigins, true)) {
            return true;
        }

        // Check wildcard patterns
        foreach ($allowedOrigins as $allowed) {
            if ($this->matchesPattern($origin, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $origin, string $pattern): bool
    {
        if (strpos($pattern, '*') === false) {
            return false;
        }

        $regex = str_replace('*', '.*', preg_quote($pattern, '/'));
        return preg_match("/^{$regex}$/", $origin) === 1;
    }
}
