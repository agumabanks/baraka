<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Login user and create Sanctum token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $token = $user->createToken('react-dashboard-token')->plainTextToken;

        $locale = $user->preferred_language ?? config('app.locale', 'en');
        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'supported_languages' => translation_supported_languages(),
            ]
        ]);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user info
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user(),
                'supported_languages' => translation_supported_languages(),
            ]
        ]);
    }

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'preferred_language' => ['nullable', Rule::in(User::SUPPORTED_LANGUAGES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'preferred_language' => $request->input('preferred_language', 'en'),
        ]);

        $token = $user->createToken('react-dashboard-token')->plainTextToken;

        $locale = $user->preferred_language ?? config('app.locale', 'en');
        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'supported_languages' => translation_supported_languages(),
            ]
        ], 201);
    }

    /**
     * Update language and other preferences for the authenticated user.
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'preferred_language' => ['required', Rule::in(User::SUPPORTED_LANGUAGES)],
        ]);

        $user->preferred_language = $validated['preferred_language'];
        $user->save();

        $request->session()->put('locale', $user->preferred_language);
        App::setLocale($user->preferred_language);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data' => [
                'user' => $user,
                'supported_languages' => translation_supported_languages(),
            ],
        ]);
    }
}