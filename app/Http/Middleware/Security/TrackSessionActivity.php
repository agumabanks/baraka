<?php

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
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
        $user = auth()->user();

        // SessionManager currently tracks internal users only (login_sessions.user_id -> users.id)
        if ($user instanceof User) {
            $this->sessionManager->updateActivity(session()->getId());
            $this->sessionManager->checkInactivity($user);
        }
        
        return $next($request);
    }
}
