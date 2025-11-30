<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SpaController extends Controller
{
    /**
     * Serve the compiled React single page application.
     * Admin users are redirected to the Laravel-powered admin dashboard.
     */
    public function __invoke(): Response|BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();

        // Admin users should use the Laravel-powered admin dashboard
        if ($user && $user->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
            return redirect()->route('admin.dashboard');
        }

        // Branch operators should land on the Laravel-powered branch dashboard
        if ($user && $user->hasPermission('branch_read')) {
            return redirect()->route('branch.dashboard');
        }

        // For non-authenticated or other users, serve the public SPA if available
        $spaEntry = $this->resolveSpaEntry();

        if (!$spaEntry) {
            // If no SPA and no authenticated user, redirect to login
            if (!$user) {
                return redirect()->route('login');
            }
            return response('Dashboard not available.', 503);
        }

        return response()->file($spaEntry);
    }

    private function resolveSpaEntry(): ?string
    {
        $candidates = [
            public_path('app/index.html'),
            public_path('index.html'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
