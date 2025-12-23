<?php

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Security\LockoutManager;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountLockout
{
    protected LockoutManager $lockoutManager;

    public function __construct(LockoutManager $lockoutManager)
    {
        $this->lockoutManager = $lockoutManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return $next($request);
        }
        
        if ($this->lockoutManager->isLocked($user)) {
            $lockoutInfo = $this->lockoutManager->getLockoutInfo($user);
            
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->withErrors(['email' => $lockoutInfo['message']]);
        }
        
        return $next($request);
    }
}
