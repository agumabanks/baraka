<?php

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\PasswordStrengthChecker;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiry
{
    protected PasswordStrengthChecker $passwordChecker;

    public function __construct(PasswordStrengthChecker $passwordChecker)
    {
        $this->passwordChecker = $passwordChecker;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Skip check if user is changing password
        if ($request->routeIs('branch.account.security.*') || $request->routeIs('security.password*') || $request->routeIs('admin.security.*')) {
            return $next($request);
        }
        
        // Determine the appropriate security route based on user role
        $securityRoute = 'branch.account.security';
        if (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
            // For admin users, skip password expiry redirect if no admin security route exists
            // They can manage passwords via the admin security dashboard
            if (!$user->force_password_change) {
                return $next($request);
            }
            // If admin security route exists, use it; otherwise skip the redirect for admins
            if (\Illuminate\Support\Facades\Route::has('admin.security.dashboard')) {
                $securityRoute = 'admin.security.dashboard';
            } else {
                // Skip password change redirect for admins without a dedicated route
                return $next($request);
            }
        }
        
        // Check if password change is forced
        if ($user->force_password_change) {
            return redirect()->route($securityRoute)
                ->with('warning', 'You must change your password before continuing.');
        }
        
        // Check if password is expired
        $expiryCheck = $this->passwordChecker->checkExpiry($user);
        
        if ($expiryCheck['expired']) {
            $user->update(['force_password_change' => true]);
            
            return redirect()->route($securityRoute)
                ->with('error', 'Your password has expired. Please change it now.');
        }
        
        return $next($request);
    }
}
