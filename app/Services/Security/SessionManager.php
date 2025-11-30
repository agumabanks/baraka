<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SessionManager
{
    /**
     * Track a new login session
     *
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function trackLogin(User $user, Request $request): void
    {
        $deviceInfo = $this->parseUserAgent($request->userAgent());
        
        DB::table('login_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'device_name' => $deviceInfo['device_name'],
            'device_type' => $deviceInfo['device_type'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'location' => $this->getLocation($request->ip()),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update user's last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        
        // Enforce concurrent session limits
        $this->enforceConcurrentSessionLimit($user);
    }

    /**
     * Update session activity timestamp
     *
     * @param string $sessionId
     * @return void
     */
    public function updateActivity(string $sessionId): void
    {
        DB::table('login_sessions')
            ->where('session_id', $sessionId)
            ->whereNull('logged_out_at')
            ->update([
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Mark session as logged out
     *
     * @param string $sessionId
     * @return void
     */
    public function markLoggedOut(string $sessionId): void
    {
        DB::table('login_sessions')
            ->where('session_id', $sessionId)
            ->update([
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Get all active sessions for a user
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public function getSessions(User $user)
    {
        return \App\Models\LoginSession::where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }

    /**
     * Revoke a specific session
     *
     * @param User $user
     * @param string $sessionId
     * @return bool
     */
    public function revokeSession(User $user, string $sessionId): bool
    {
        // Don't allow revoking current session this way
        if ($sessionId === session()->getId()) {
            return false;
        }
        
        // Mark as logged out in our tracking
        $this->markLoggedOut($sessionId);
        
        // Delete from Laravel sessions table
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();
        
        return true;
    }

    /**
     * Revoke all sessions except current
     *
     * @param User $user
     * @param bool $includeCurrentSession
     * @return int Number of sessions revoked
     */
    public function revokeAllSessions(User $user, bool $includeCurrentSession = false): int
    {
        $currentSessionId = session()->getId();
        
        // Get all session IDs to revoke
        $query = DB::table('login_sessions')
            ->where('user_id', $user->id)
            ->whereNull('logged_out_at');
        
        if (!$includeCurrentSession) {
            $query->where('session_id', '!=', $currentSessionId);
        }
        
        $sessionIds = $query->pluck('session_id');
        $count = $sessionIds->count();
        
        // Mark all as logged out
        DB::table('login_sessions')
            ->whereIn('session_id', $sessionIds)
            ->update([
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);
        
        // Delete from Laravel sessions table
        $sessionsQuery = DB::table('sessions')
            ->where('user_id', $user->id);
        
        if (!$includeCurrentSession) {
            $sessionsQuery->where('id', '!=', $currentSessionId);
        }
        
        $sessionsQuery->delete();
        
        return $count;
    }

    /**
     * Check for inactive sessions and expire them
     *
     * @param User $user
     * @return void
     */
    public function checkInactivity(User $user): void
    {
        $timeoutMinutes = config('account_security.session.inactivity_timeout_minutes', 30);
        $inactivityThreshold = now()->subMinutes($timeoutMinutes);
        
        $inactiveSessions = DB::table('login_sessions')
            ->where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->where('last_activity_at', '<', $inactivityThreshold)
            ->pluck('session_id');
        
        if ($inactiveSessions->isEmpty()) {
            return;
        }
        
        // Mark as logged out
        DB::table('login_sessions')
            ->whereIn('session_id', $inactiveSessions)
            ->update([
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);
        
        // Delete from sessions table
        DB::table('sessions')
            ->whereIn('id', $inactiveSessions)
            ->delete();
    }

    /**
     * Enforce concurrent session limit
     *
     * @param User $user
     * @return void
     */
    protected function enforceConcurrentSessionLimit(User $user): void
    {
        $maxSessions = config('account_security.session.max_concurrent_sessions', 5);
        
        $activeSessions = DB::table('login_sessions')
            ->where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->orderBy('last_activity_at', 'desc')
            ->get();
        
        if ($activeSessions->count() <= $maxSessions) {
            return;
        }
        
        // Revoke oldest sessions beyond limit
        $sessionsToRevoke = $activeSessions->slice($maxSessions);
        
        foreach ($sessionsToRevoke as $session) {
            $this->revokeSession($user, $session->session_id);
        }
    }

    /**
     * Parse user agent to extract device information
     *
     * @param string|null $userAgent
     * @return array
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'device_name' => 'Unknown Device',
                'device_type' => 'unknown',
            ];
        }
        
        // Detect mobile/tablet/desktop
        $isMobile = preg_match('/Mobile|Android|iPhone/', $userAgent);
        $isTablet = preg_match('/iPad|Android(?!.*Mobile)|Tablet/', $userAgent);
        
        $deviceType = $isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop');
        
        // Extract device name
        $deviceName = 'Unknown Device';
        
        if (preg_match('/iPhone/', $userAgent)) {
            $deviceName = 'iPhone';
        } elseif (preg_match('/iPad/', $userAgent)) {
            $deviceName = 'iPad';
        } elseif (preg_match('/Android/', $userAgent)) {
            $deviceName = 'Android Device';
        } elseif (preg_match('/Windows/', $userAgent)) {
            $deviceName = 'Windows PC';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $deviceName = 'Mac';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $deviceName = 'Linux PC';
        }
        
        // Add browser info
        if (preg_match('/Chrome\/[\d.]+/', $userAgent, $matches)) {
            $deviceName .= ' - Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/', $userAgent, $matches)) {
            $deviceName .= ' - Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/', $userAgent, $matches) && !preg_match('/Chrome/', $userAgent)) {
            $deviceName .= ' - Safari';
        } elseif (preg_match('/Edge\/[\d.]+/', $userAgent, $matches)) {
            $deviceName .= ' - Edge';
        }
        
        return [
            'device_name' => $deviceName,
            'device_type' => $deviceType,
        ];
    }

    /**
     * Get approximate location from IP address
     *
     * @param string $ip
     * @return string|null
     */
    protected function getLocation(string $ip): ?string
    {
        // For localhost/private IPs
        if (in_array($ip, ['127.0.0.1', '::1']) || preg_match('/^(10|172|192)\./', $ip)) {
            return 'Local';
        }
        
        // TODO: Integrate with IP geolocation service (e.g., ipapi.co, ipinfo.io)
        // For now, return null
        return null;
    }

    /**
     * Get login history for a user
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLoginHistory(User $user, int $limit = 30)
    {
        return DB::table('login_sessions')
            ->where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Cleanup old login session records
     *
     * @param int $retentionDays
     * @return int Number of records deleted
     */
    public function cleanup(int $retentionDays = 90): int
    {
        return DB::table('login_sessions')
            ->where('logged_in_at', '<', now()->subDays($retentionDays))
            ->delete();
    }
}
