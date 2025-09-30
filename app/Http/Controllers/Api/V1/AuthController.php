<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\Device;
use App\Models\User;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 */
class AuthController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Login with device binding",
     *     description="Authenticate user and create token with device binding",
     *     operationId="login",
     *     tags={"Authentication"},
     *
     *     @OA\Parameter(
     *         name="device_uuid",
     *         in="header",
     *         description="Unique device identifier",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="platform",
     *         in="header",
     *         description="Device platform (ios, android, web)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="push_token",
     *         in="header",
     *         description="Push notification token",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="merchant@demo.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="merchant@demo.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|abc123...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Device UUID required",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="device_uuid header required")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect.")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        // Check device_uuid header
        $deviceUuid = $request->header('device_uuid');
        if (! $deviceUuid) {
            return response()->json(['error' => 'device_uuid header required'], 400);
        }

        // Find user by email or phone
        $user = User::where('email', $request->email)
            ->orWhere('phone_e164', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->responseWithError('The provided credentials are incorrect.', [], 422);
        }

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        // Create or update device
        Device::updateOrCreate(
            ['device_uuid' => $deviceUuid],
            [
                'user_id' => $user->id,
                'platform' => $request->header('platform', 'unknown'),
                'push_token' => $request->header('push_token'),
                'last_seen_at' => now(),
            ]
        );

        return $this->responseWithSuccess('Login successful', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
            ],
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Logout and revoke token",
     *     description="Revoke the current access token",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout successful")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->responseWithSuccess('Logout successful');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     summary="Get authenticated user profile",
     *     description="Retrieve the authenticated user's profile information",
     *     operationId="getMe",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="mobile", type="string", example="+1234567890"),
     *                     @OA\Property(property="user_type", type="string", example="merchant"),
     *                     @OA\Property(property="notification_prefs", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return $this->responseWithSuccess('Profile retrieved', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'user_type' => $user->user_type,
                'notification_prefs' => $user->notification_prefs,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/me",
     *     summary="Update authenticated user profile",
     *     description="Update the authenticated user's profile information",
     *     operationId="updateMe",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="mobile", type="string", example="+1234567890"),
     *             @OA\Property(property="notification_prefs", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function updateMe(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'mobile' => 'sometimes|string|max:20',
            'notification_prefs' => 'sometimes|array',
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'mobile', 'notification_prefs']));

        return $this->responseWithSuccess('Profile updated', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'notification_prefs' => $user->notification_prefs,
            ],
        ]);
    }
}
