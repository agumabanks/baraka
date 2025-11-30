<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class SecurityService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $newPassword): bool
    {
        $user->password = Hash::make($newPassword);
        return $user->save();
    }

    /**
     * Generate 2FA secret for user
     */
    public function generate2FASecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'Baraka'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    /**
     * Verify 2FA code
     */
    public function verify2FACode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Enable 2FA for user
     */
    public function enable2FA(User $user, string $secret): bool
    {
        // Store in mfa_devices table or user settings
        // For now, we'll store in user's settings
        $settings = json_decode($user->settings ?? '{}', true);
        $settings['2fa_secret'] = $secret;
        $settings['2fa_enabled'] = true;
        
        $user->settings = json_encode($settings);
        return $user->save();
    }

    /**
     * Disable 2FA for user
     */
    public function disable2FA(User $user): bool
    {
        $settings = json_decode($user->settings ?? '{}', true);
        unset($settings['2fa_secret']);
        $settings['2fa_enabled'] = false;
        
        $user->settings = json_encode($settings);
        return $user->save();
    }

    /**
     * Check if user has 2FA enabled
     */
    public function has2FAEnabled(User $user): bool
    {
        $settings = json_decode($user->settings ?? '{}', true);
        return $settings['2fa_enabled'] ?? false;
    }
}
