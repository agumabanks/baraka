<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class PasswordStrengthChecker
{
    /**
     * Check password strength (0-100 score)
     *
     * @param string $password
     * @return int
     */
    public function checkStrength(string $password): int
    {
        $score = 0;
        
        // Length scoring
        $length = strlen($password);
        if ($length >= 8) $score += 20;
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 10;
        
        // Character variety
        if (preg_match('/[a-z]/', $password)) $score += 15; // Lowercase
        if (preg_match('/[A-Z]/', $password)) $score += 15; // Uppercase
        if (preg_match('/[0-9]/', $password)) $score += 15; // Numbers
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 15; // Special chars
        
        return min($score, 100);
    }

    /**
     * Get password strength label
     *
     * @param int $score
     * @return string
     */
    public function getStrengthLabel(int $score): string
    {
        if ($score < 40) return 'Weak';
        if ($score < 60) return 'Fair';
        if ($score < 80) return 'Good';
        return 'Strong';
    }

    /**
     * Check if password meets minimum requirements
     *
     * @param string $password
     * @return array [passes, errors]
     */
    public function meetsRequirements(string $password): array
    {
        $errors = [];
        $config = config('account_security.password', []);
        
        $minLength = $config['min_length'] ?? 12;
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }
        
        if (($config['require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (($config['require_lowercase'] ?? true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (($config['require_numbers'] ?? true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (($config['require_symbols'] ?? true) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one symbol";
        }
        
        return [
            'passes' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if password is in user's history (alias for checkHistory)
     *
     * @param User $user
     * @param string $password
     * @return bool True if password is in history (should be rejected)
     */
    public function isInHistory(User $user, string $password): bool
    {
        return $this->checkHistory($user, $password);
    }

    /**
     * Check if password was used in user's history
     *
     * @param User $user
     * @param string $password
     * @return bool True if password is in history (should be rejected)
     */
    public function checkHistory(User $user, string $password): bool
    {
        $historyCount = config('account_security.password.history_count', 5);
        
        $history = DB::table('password_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->pluck('password_hash');
        
        foreach ($history as $hash) {
            if (Hash::check($password, $hash)) {
                return true; // Password found in history
            }
        }
        
        return false; // Password is new
    }

    /**
     * Check if password has been breached (haveibeenpwned API)
     *
     * @param string $password
     * @return bool True if breached
     */
    public function checkBreach(string $password): bool
    {
        if (!config('account_security.password.check_breach', true)) {
            return false;
        }
        
        try {
            // Hash password with SHA-1
            $sha1 = strtoupper(sha1($password));
            $prefix = substr($sha1, 0, 5);
            $suffix = substr($sha1, 5);
            
            // Query haveibeenpwned API
            $response = Http::timeout(3)->get("https://api.pwnedpasswords.com/range/{$prefix}");
            
            if (!$response->successful()) {
                return false; // Don't block if API fails
            }
            
            // Check if our suffix is in the response
            $hashes = explode("\n", $response->body());
            foreach ($hashes as $line) {
                if (strpos($line, $suffix) === 0) {
                    return true; // Password found in breach database
                }
            }
            
            return false;
        } catch (\Exception $e) {
            // Don't block on API errors
            \Log::warning('Password breach check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add password to user's history
     *
     * @param User $user
     * @param string $passwordHash
     * @return void
     */
    public function addToHistory(User $user, string $passwordHash): void
    {
        DB::table('password_history')->insert([
            'user_id' => $user->id,
            'password_hash' => $passwordHash,
            'created_at' => now(),
        ]);
        
        // Cleanup old history beyond retention count
        $historyCount = config('account_security.password.history_count', 5);
        $historyIds = DB::table('password_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->pluck('id');
        
        DB::table('password_history')
            ->where('user_id', $user->id)
            ->whereNotIn('id', $historyIds)
            ->delete();
    }

    /**
     * Calculate password expiry date
     *
     * @return \Carbon\Carbon|null
     */
    public function calculateExpiryDate(): ?\Carbon\Carbon
    {
        $expiryDays = config('account_security.password.expires_days');
        
        if (!$expiryDays || $expiryDays <= 0) {
            return null;
        }
        
        return now()->addDays($expiryDays);
    }

    /**
     * Check if user's password is expired or expiring soon
     *
     * @param User $user
     * @return array [expired, expiring_soon, days_until_expiry]
     */
    public function checkExpiry(User $user): array
    {
        if (!$user->password_expires_at) {
            return [
                'expired' => false,
                'expiring_soon' => false,
                'days_until_expiry' => null,
            ];
        }
        
        $daysUntilExpiry = now()->diffInDays($user->password_expires_at, false);
        
        return [
            'expired' => $daysUntilExpiry < 0,
            'expiring_soon' => $daysUntilExpiry >= 0 && $daysUntilExpiry <= 7,
            'days_until_expiry' => max(0, (int) $daysUntilExpiry),
        ];
    }

    /**
     * Get password age in days
     *
     * @param User $user
     * @return int|null
     */
    public function getPasswordAge(User $user): ?int
    {
        if (!$user->last_password_change_at) {
            return null;
        }
        
        return $user->last_password_change_at->diffInDays(now());
    }

    /**
     * Comprehensive password validation
     *
     * @param User $user
     * @param string $password
     * @return array [valid, errors, warnings]
     */
    public function validate(User $user, string $password): array
    {
        $errors = [];
        $warnings = [];
        
        // Check requirements
        $requirements = $this->meetsRequirements($password);
        if (!$requirements['passes']) {
            $errors = array_merge($errors, $requirements['errors']);
        }
        
        // Check history
        if ($this->checkHistory($user, $password)) {
            $errors[] = "Password has been used recently. Please choose a different password.";
        }
        
        // Check strength
        $strength = $this->checkStrength($password);
        if ($strength < 50) {
            $warnings[] = "Password strength is weak. Consider adding more characters or variety.";
        }
        
        // Check breach
        if ($this->checkBreach($password)) {
            $errors[] = "This password has been found in data breaches. Please choose a different password.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'strength_score' => $strength,
            'strength_label' => $this->getStrengthLabel($strength),
        ];
    }
}
