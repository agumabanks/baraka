<?php

namespace App\Services\Security;

use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountLockedNotification;

class LockoutManager
{
    /**
     * Record a failed login attempt
     *
     * @param string $email
     * @param string $ip
     * @return void
     */
    public function recordFailedAttempt(string $email, string $ip): void
    {
        // Log security event
        DB::table('security_events')->insert([
            'user_id' => User::where('email', $email)->value('id'),
            'event_type' => 'failed_login',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode(['email' => $email]),
            'risk_score' => $this->calculateRiskScore($email, $ip),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update user's failed attempt counter
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->increment('failed_login_attempts');
            $user->update(['last_failed_login_at' => now()]);
        }
    }

    /**
     * Check if account should be locked based on failed attempts
     * Uses database-backed settings with safe fallbacks
     *
     * @param string $email
     * @return bool
     */
    public function shouldLock(string $email): bool
    {
        // Use SystemSettings with fallback to config and finally hardcoded default
        $maxAttempts = SystemSettings::maxLoginAttempts() 
            ?: config('account_security.lockout.max_attempts', 5);
        $windowMinutes = (int) SystemSettings::get('system.lockout_duration', 
            config('account_security.lockout.window_minutes', 15));
        
        $recentFailures = DB::table('security_events')
            ->where('event_type', 'failed_login')
            ->where('metadata->email', $email)
            ->where('occurred_at', '>', now()->subMinutes($windowMinutes))
            ->count();
        
        return $recentFailures >= $maxAttempts;
    }

    /**
     * Lock a user account
     *
     * @param User $user
     * @param string $reason
     * @return void
     */
    public function lock(User $user, string $reason = 'Too many failed login attempts'): void
    {
        $durationMinutes = config('account_security.lockout.duration_minutes', 60);
        
        $user->update([
            'locked_until' => now()->addMinutes($durationMinutes),
        ]);
        
        // Log lockout event
        DB::table('security_events')->insert([
            'user_id' => $user->id,
            'event_type' => 'account_locked',
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'reason' => $reason,
                'locked_until' => $user->locked_until,
                'failed_attempts' => $user->failed_login_attempts,
            ]),
            'risk_score' => 75,
            'was_blocked' => true,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Send notification email
        try {
            Mail::to($user->email)->send(new AccountLockedNotification($user));
        } catch (\Exception $e) {
            \Log::error('Failed to send account locked email: ' . $e->getMessage());
        }
    }

    /**
     * Unlock a user account
     *
     * @param User $user
     * @param User|null $unlockedBy Admin who unlocked
     * @return void
     */
    public function unlock(User $user, ?User $unlockedBy = null): void
    {
        $user->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
        ]);
        
        // Log unlock event
        DB::table('security_events')->insert([
            'user_id' => $user->id,
            'event_type' => 'account_unlocked',
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'unlocked_by' => $unlockedBy ? $unlockedBy->id : 'auto',
                'unlocked_by_name' => $unlockedBy ? $unlockedBy->name : 'System',
            ]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Check if user is currently locked
     *
     * @param User $user
     * @return bool
     */
    public function isLocked(User $user): bool
    {
        if (!$user->locked_until) {
            return false;
        }
        
        // Auto-unlock if lockout period expired
        if ($user->locked_until->isPast()) {
            $this->unlock($user);
            return false;
        }
        
        return true;
    }

    /**
     * Get lockout info for user
     *
     * @param User $user
     * @return array|null
     */
    public function getLockoutInfo(User $user): ?array
    {
        if (!$this->isLocked($user)) {
            return null;
        }
        
        $minutesRemaining = now()->diffInMinutes($user->locked_until, false);
        
        return [
            'locked' => true,
            'locked_until' => $user->locked_until,
            'minutes_remaining' => max(0, (int) $minutesRemaining),
            'message' => "Account locked due to multiple failed login attempts. Try again in " . max(1, (int) $minutesRemaining) . " minutes.",
        ];
    }

    /**
     * Reset failed login counter (called on successful login)
     *
     * @param User $user
     * @return void
     */
    public function resetFailedAttempts(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
        ]);
    }

    /**
     * Calculate risk score for a login attempt
     *
     * @param string $email
     * @param string $ip
     * @return int 0-100
     */
    protected function calculateRiskScore(string $email, string $ip): int
    {
        $score = 0;
        
        // Check recent failures from this IP
        $recentIpFailures = DB::table('security_events')
            ->where('event_type', 'failed_login')
            ->where('ip_address', $ip)
            ->where('occurred_at', '>', now()->subHour())
            ->count();
        
        $score += min($recentIpFailures * 10, 50);
        
        // Check recent failures for this email
        $recentEmailFailures = DB::table('security_events')
            ->where('event_type', 'failed_login')
            ->where('metadata->email', $email)
            ->where('occurred_at', '>', now()->subHour())
            ->count();
        
        $score += min($recentEmailFailures * 10, 50);
        
        return min($score, 100);
    }

    /**
     * Get recent security events for a user
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getSecurityEvents(User $user, int $limit = 20)
    {
        return DB::table('security_events')
            ->where('user_id', $user->id)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check for brute force attack pattern
     *
     * @param string $ip
     * @return bool
     */
    public function detectBruteForce(string $ip): bool
    {
        $recentAttempts = DB::table('security_events')
            ->where('event_type', 'failed_login')
            ->where('ip_address', $ip)
            ->where('occurred_at', '>', now()->subMinutes(5))
            ->count();
        
        if ($recentAttempts >= 10) {
            // Log brute force event
            DB::table('security_events')->insert([
                'user_id' => null,
                'event_type' => 'brute_force_detected',
                'ip_address' => $ip,
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode(['attempts' => $recentAttempts]),
                'risk_score' => 100,
                'was_blocked' => true,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Cleanup old security events
     *
     * @param int $retentionDays
     * @return int
     */
    public function cleanup(int $retentionDays = 90): int
    {
        return DB::table('security_events')
            ->where('occurred_at', '<', now()->subDays($retentionDays))
            ->delete();
    }
}
