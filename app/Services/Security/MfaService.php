<?php

namespace App\Services\Security;

use App\Models\Security\SecurityMfaDevice;
use App\Models\Security\SecurityAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Exception;

class MfaService
{
    private const MAX_ATTEMPTS = 3;
    private const LOCKOUT_TIME = 900; // 15 minutes
    private const TOTP_WINDOW = 1; // 30-second window

    /**
     * Generate TOTP secret
     */
    public function generateTotpSecret(): string
    {
        return base32_encode(random_bytes(20)); // 160-bit secret
    }

    /**
     * Generate TOTP code
     */
    public function generateTotpCode(string $secret): string
    {
        $time = floor(time() / 30);
        $hash = hash_hmac('sha1', pack('N*', 0, $time), base32_decode($secret), true);
        $offset = ord($hash[19]) & 0xf;
        
        $code = (
            ((ord($hash[$offset+0]) & 0x7f) << 24) |
            ((ord($hash[$offset+1]) & 0xff) << 16) |
            ((ord($hash[$offset+2]) & 0xff) << 8) |
            (ord($hash[$offset+3]) & 0xff)
        ) % pow(10, 6);
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify TOTP code
     */
    public function verifyTotpCode(string $secret, string $code): bool
    {
        // Check current time window
        for ($i = -self::TOTP_WINDOW; $i <= self::TOTP_WINDOW; $i++) {
            $time = floor(time() / 30) + $i;
            $hash = hash_hmac('sha1', pack('N*', 0, $time), base32_decode($secret), true);
            $offset = ord($hash[19]) & 0xf;
            
            $generatedCode = (
                ((ord($hash[$offset+0]) & 0x7f) << 24) |
                ((ord($hash[$offset+1]) & 0xff) << 16) |
                ((ord($hash[$offset+2]) & 0xff) << 8) |
                (ord($hash[$offset+3]) & 0xff)
            ) % pow(10, 6);
            
            $generatedCode = str_pad($generatedCode, 6, '0', STR_PAD_LEFT);
            
            if (hash_equals($generatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Send SMS verification code
     */
    public function sendSmsCode(User $user, string $phone): bool
    {
        try {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $cacheKey = "mfa_sms_{$user->id}_{$phone}";
            
            // Store code in cache for 5 minutes
            Cache::put($cacheKey, $code, 300);
            
            // Send SMS via your SMS provider
            $this->sendSms($phone, "Your verification code is: {$code}");
            
            // Log the attempt
            SecurityAuditLog::create([
                'event_type' => 'mfa_code_sent',
                'event_category' => 'security',
                'severity' => 'low',
                'user_id' => $user->id,
                'resource_type' => 'mfa',
                'metadata' => ['phone' => $phone, 'method' => 'sms'],
                'status' => 'success',
                'description' => "MFA code sent via SMS to {$phone}",
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('SMS MFA code failed', [
                'user_id' => $user->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email verification code
     */
    public function sendEmailCode(User $user): bool
    {
        try {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $cacheKey = "mfa_email_{$user->id}";
            
            // Store code in cache for 5 minutes
            Cache::put($cacheKey, $code, 300);
            
            // Send email
            Mail::raw("Your verification code is: {$code}", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Two-Factor Authentication Code');
            });
            
            // Log the attempt
            SecurityAuditLog::create([
                'event_type' => 'mfa_code_sent',
                'event_category' => 'security',
                'severity' => 'low',
                'user_id' => $user->id,
                'resource_type' => 'mfa',
                'metadata' => ['email' => $user->email, 'method' => 'email'],
                'status' => 'success',
                'description' => "MFA code sent via email to {$user->email}",
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Email MFA code failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify MFA code
     */
    public function verifyMfaCode(User $user, string $code, string $method = 'sms', string $identifier = null): bool
    {
        try {
            $cacheKey = "mfa_{$method}_{$user->id}" . ($identifier ? "_{$identifier}" : "");
            $cachedCode = Cache::get($cacheKey);
            
            if (!$cachedCode || !hash_equals($cachedCode, $code)) {
                $this->recordFailedAttempt($user, $method);
                return false;
            }
            
            // Clear the used code
            Cache::forget($cacheKey);
            
            // Update device last used timestamp
            $this->updateDeviceLastUsed($user, $method, $identifier);
            
            // Log successful verification
            SecurityAuditLog::create([
                'event_type' => 'mfa_verified',
                'event_category' => 'security',
                'severity' => 'low',
                'user_id' => $user->id,
                'resource_type' => 'mfa',
                'metadata' => ['method' => $method, 'identifier' => $identifier],
                'status' => 'success',
                'description' => "MFA verification successful via {$method}",
            ]);
            
            // Clear any lockout
            $this->clearLockout($user, $method);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('MFA verification failed', [
                'user_id' => $user->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Register a new MFA device
     */
    public function registerDevice(User $user, string $deviceName, string $deviceType, string $identifier, array $data = []): SecurityMfaDevice
    {
        $device = SecurityMfaDevice::create([
            'user_id' => $user->id,
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'device_identifier' => $identifier,
            'is_verified' => false,
            'is_primary' => SecurityMfaDevice::where('user_id', $user->id)->count() === 0, // First device is primary
            'metadata' => $data,
        ]);

        // Generate backup codes for the device
        $backupCodes = $device->generateBackupCodes();
        $device->update(['backup_codes' => $backupCodes]);

        SecurityAuditLog::create([
            'event_type' => 'mfa_device_registered',
            'event_category' => 'security',
            'severity' => 'medium',
            'user_id' => $user->id,
            'resource_type' => 'mfa',
            'metadata' => ['device_type' => $deviceType, 'device_name' => $deviceName],
            'status' => 'success',
            'description' => "MFA device registered: {$deviceName} ({$deviceType})",
        ]);

        return $device;
    }

    /**
     * Check if user is locked out due to too many failed attempts
     */
    public function isLockedOut(User $user, string $method): bool
    {
        $cacheKey = "mfa_lockout_{$user->id}_{$method}";
        return Cache::has($cacheKey);
    }

    /**
     * Check if user has MFA enabled
     */
    public function hasMfaEnabled(User $user): bool
    {
        return SecurityMfaDevice::where('user_id', $user->id)
            ->where('is_verified', true)
            ->count() > 0;
    }

    /**
     * Get user's MFA devices
     */
    public function getUserDevices(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityMfaDevice::where('user_id', $user->id)
            ->verified()
            ->orderBy('is_primary', 'desc')
            ->get();
    }

    /**
     * Get primary MFA device
     */
    public function getPrimaryDevice(User $user): ?SecurityMfaDevice
    {
        return SecurityMfaDevice::where('user_id', $user->id)
            ->where('is_verified', true)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Update device last used timestamp
     */
    private function updateDeviceLastUsed(User $user, string $method, ?string $identifier): void
    {
        $query = SecurityMfaDevice::where('user_id', $user->id)->where('device_type', $method);
        
        if ($identifier) {
            $query->where('device_identifier', $identifier);
        }
        
        $query->update(['last_used_at' => now()]);
    }

    /**
     * Record failed MFA attempt
     */
    private function recordFailedAttempt(User $user, string $method): void
    {
        $cacheKey = "mfa_attempts_{$user->id}_{$method}";
        $attempts = Cache::get($cacheKey, 0) + 1;
        
        Cache::put($cacheKey, $attempts, 3600); // Store for 1 hour
        
        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockoutKey = "mfa_lockout_{$user->id}_{$method}";
            Cache::put($lockoutKey, true, self::LOCKOUT_TIME);
            
            SecurityAuditLog::create([
                'event_type' => 'mfa_lockout',
                'event_category' => 'security',
                'severity' => 'high',
                'user_id' => $user->id,
                'resource_type' => 'mfa',
                'metadata' => ['method' => $method, 'attempts' => $attempts],
                'status' => 'warning',
                'description' => "MFA lockout triggered after {$attempts} failed attempts via {$method}",
            ]);
        }
    }

    /**
     * Clear MFA lockout
     */
    private function clearLockout(User $user, string $method): void
    {
        $cacheKey = "mfa_lockout_{$user->id}_{$method}";
        Cache::forget($cacheKey);
        
        $attemptsKey = "mfa_attempts_{$user->id}_{$method}";
        Cache::forget($attemptsKey);
    }

    /**
     * Send SMS via external service
     */
    private function sendSms(string $phone, string $message): void
    {
        // Implement your SMS service here
        // Example: Twilio, AWS SNS, etc.
        // For now, just log the message
        Log::info("SMS to {$phone}: {$message}");
    }

    /**
     * Remove MFA device
     */
    public function removeDevice(User $user, int $deviceId): bool
    {
        try {
            $device = SecurityMfaDevice::where('user_id', $user->id)->findOrFail($deviceId);
            
            // If removing primary device, make another device primary
            if ($device->is_primary) {
                $otherDevice = SecurityMfaDevice::where('user_id', $user->id)
                    ->where('id', '!=', $deviceId)
                    ->where('is_verified', true)
                    ->first();
                    
                if ($otherDevice) {
                    $otherDevice->update(['is_primary' => true]);
                }
            }
            
            $device->delete();
            
            SecurityAuditLog::create([
                'event_type' => 'mfa_device_removed',
                'event_category' => 'security',
                'severity' => 'medium',
                'user_id' => $user->id,
                'resource_type' => 'mfa',
                'metadata' => ['device_type' => $device->device_type, 'device_name' => $device->device_name],
                'status' => 'success',
                'description' => "MFA device removed: {$device->device_name}",
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('MFA device removal failed', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}