<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    protected SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Show current user's active sessions.
     */
    public function index()
    {
        $user = Auth::user();
        $sessions = $this->sessionManager->getActiveSessions($user);
        return view('branch.security.sessions', compact('sessions'));
    }

    /**
     * Revoke a specific session.
     */
    public function revoke($sessionId)
    {
        $user = Auth::user();
        $this->sessionManager->revokeSession($user, $sessionId);
        return redirect()->back()->with('status', 'Session revoked successfully');
    }
}
