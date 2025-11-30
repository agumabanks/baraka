<?php

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\SessionManager;
use Symfony\Component\HttpFoundation\Response;

class TrackSessionActivity
{
    protected SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // Update last activity timestamp
            $this->sessionManager->updateActivity(session()->getId());
            
            // Check for inactivity timeout
            $this->sessionManager->checkInactivity(auth()->user());
        }
        
        return $next($request);
    }
}
