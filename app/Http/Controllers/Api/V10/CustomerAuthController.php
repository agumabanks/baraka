<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        if (! config('otp.self_registration')) {
            return response()->json(['success' => false, 'message' => 'Self registration disabled'], 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'password' => 'nullable|string|min:8',
            'consent' => 'required|boolean',
            'consent_version' => 'required|string',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $otp = app(OtpService::class);
        try {
            $e164 = $otp->normalizePhoneE164($request->phone);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid phone'], 422);
        }

        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'password' => $request->filled('password') ? Hash::make($request->password) : null,
                'mobile' => $e164,
                'phone_e164' => $e164,
                'user_type' => 'customer',
            ]
        );

        // Store GDPR consent log
        \App\Models\UserConsent::create([
            'user_id' => $user->id,
            'type' => 'privacy',
            'version' => (string) $request->consent_version,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent'),
        ]);

        // Send OTP to preferred channel (default sms)
        $channel = in_array('whatsapp', config('otp.channels')) ? 'whatsapp' : 'sms';
        $otp->issue($e164, $channel, $user);

        return response()->json([
            'success' => true,
            'message' => 'Registration started. Please verify OTP.',
            'phone_e164' => $e164,
        ]);
    }

    public function sendOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'address' => 'required|string', // email or phone E.164
            'channel' => 'nullable|in:sms,whatsapp,email',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $channel = $request->channel ?: 'sms';
        if (! in_array($channel, config('otp.channels'))) {
            return response()->json(['success' => false, 'message' => 'Channel not enabled'], 422);
        }

        $addr = $request->address;
        // Normalize if phone-like
        if ($channel !== 'email' && preg_match('/\d{6,}/', $addr)) {
            try {
                $addr = app(OtpService::class)->normalizePhoneE164($addr);
            } catch (\Throwable $e) {
            }
        }

        try {
            app(OtpService::class)->issue($addr, $channel);

            return response()->json(['success' => true, 'message' => 'OTP sent']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 429);
        }
    }

    public function verifyOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'address' => 'required|string',
            'code' => 'required|string|min:4|max:8',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $addr = $request->address;
        if (preg_match('/\d{6,}/', $addr)) {
            try {
                $addr = app(OtpService::class)->normalizePhoneE164($addr);
            } catch (\Throwable $e) {
            }
        }

        $ok = app(OtpService::class)->verify($addr, $request->code);
        if (! $ok) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired code'], 401);
        }

        // Mark user verified where applicable
        $user = User::where('phone_e164', $addr)->orWhere('mobile', $addr)
            ->orWhere('email', $addr)->first();
        if ($user) {
            if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            } else {
                $user->forceFill(['verification_status' => 1, 'mobile' => $addr, 'phone_e164' => $addr])->save();
            }

            // Return API token for immediate use
            return response()->json(['success' => true, 'token' => $user->createToken('otp')->plainTextToken]);
        }

        return response()->json(['success' => true]);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:8',
            'code' => 'nullable|string|min:4|max:8',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        // OTP login (preferred for self-service)
        if ($request->filled('code')) {
            $addr = $request->phone ?: $request->email;
            if (! $addr) {
                return response()->json(['success' => false, 'message' => 'Address required'], 422);
            }
            if ($request->phone) {
                try {
                    $addr = app(OtpService::class)->normalizePhoneE164($addr);
                } catch (\Throwable $e) {
                }
            }
            $ok = app(OtpService::class)->verify($addr, $request->code);
            if (! $ok) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired code'], 401);
            }
            $user = User::where('phone_e164', $addr)->orWhere('mobile', $addr)->orWhere('email', $addr)->first();
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            return response()->json(['success' => true, 'token' => $user->createToken('otp')->plainTextToken]);
        }

        // Password login
        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->filled('phone')) {
            $e164 = app(OtpService::class)->normalizePhoneE164($request->phone);
            $user = User::where('phone_e164', $e164)->orWhere('mobile', $e164)->first();
        } else {
            return response()->json(['success' => false, 'message' => 'Credentials required'], 422);
        }

        if (! $user || ! $user->password || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        return response()->json(['success' => true, 'token' => $user->createToken('password')->plainTextToken]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['success' => true]);
    }
}
