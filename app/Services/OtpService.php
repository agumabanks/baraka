<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class OtpService
{
    public function normalizePhoneE164(string $phone, ?string $region = 'KE'): string
    {
        $util = PhoneNumberUtil::getInstance();
        $num = $util->parse($phone, $region ?: 'KE');
        if (! $util->isValidNumber($num)) {
            throw new \InvalidArgumentException('Invalid phone number');
        }

        return $util->format($num, PhoneNumberFormat::E164);
    }

    public function canSend(string $address, string $channel): array
    {
        $minInterval = config('otp.min_interval_seconds');
        $last = OtpCode::where('address', $address)->where('channel', $channel)->latest()->first();
        if ($last && $last->last_sent_at && now()->diffInSeconds($last->last_sent_at) < $minInterval) {
            return [false, 'Please wait before requesting another OTP'];
        }

        return [true, null];
    }

    public function issue(string $address, string $channel, ?User $user = null): OtpCode
    {
        [$ok, $reason] = $this->canSend($address, $channel);
        if (! $ok) {
            throw new \RuntimeException($reason);
        }
        $code = (string) random_int(100000, 999999);
        $ttl = config('otp.ttl_seconds');
        $otp = OtpCode::create([
            'address' => $address,
            'channel' => $channel,
            'code' => $code,
            'expires_at' => now()->addSeconds($ttl),
            'sent_count' => 1,
            'last_sent_at' => now(),
            'meta' => $user ? ['user_id' => $user->id] : null,
        ]);

        $this->deliver($otp, $user);

        return $otp;
    }

    public function deliver(OtpCode $otp, ?User $user = null): void
    {
        try {
            if (in_array($otp->channel, ['sms', 'whatsapp'])) {
                // Reuse existing SMS service. WhatsApp via Twilio if configured.
                app(\App\Http\Services\SmsService::class)->sendOtp($otp->address, $otp->code);
            } elseif ($otp->channel === 'email') {
                Mail::raw('Your verification code is: '.$otp->code, function ($m) use ($otp) {
                    $m->to($otp->address)->subject('Your verification code');
                });
            }
        } catch (\Throwable $e) {
            Log::error('OTP delivery failed: '.$e->getMessage());
        }
    }

    public function verify(string $address, string $code): bool
    {
        $otp = OtpCode::where('address', $address)
            ->whereNull('consumed_at')
            ->latest()->first();
        if (! $otp) {
            return false;
        }

        // Lockout check
        if ($otp->locked_until && now()->lessThan($otp->locked_until)) {
            return false;
        }

        // Expiry check
        if (now()->greaterThan($otp->expires_at)) {
            return false;
        }

        if (hash_equals($otp->code, trim($code))) {
            $otp->update(['consumed_at' => now()]);

            return true;
        }

        // Increment attempts and lock if needed
        $maxAttempts = config('otp.max_attempts');
        $lockSeconds = config('otp.lockout_seconds');
        $attempts = $otp->attempts + 1;
        $updates = ['attempts' => $attempts];
        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = now()->addSeconds($lockSeconds);
        }
        $otp->update($updates);

        return false;
    }
}
