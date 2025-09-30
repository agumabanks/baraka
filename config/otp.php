<?php

return [
    'self_registration' => env('SELF_REGISTRATION', false),
    'channels' => array_filter(array_map('trim', explode(',', env('OTP_CHANNELS', 'sms')))),
    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 300),
    // Parse "1/30s" to min interval seconds for subsequent sends
    'min_interval_seconds' => (function () {
        $v = env('OTP_RATE_LIMIT_PER_PHONE', '1/30s');
        if (preg_match('/^\s*(\d+)\/(\d+)s\s*$/i', $v, $m)) {
            return max(1, (int) $m[2]);
        }

        return 30;
    })(),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'lockout_seconds' => (int) env('OTP_LOCKOUT_SECONDS', 900),
];
