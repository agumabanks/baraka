<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiReturnFormatTrait;

/**
 * @OA\Tag(
 *     name="Profile",
 *     description="API Endpoints for user profile management"
 * )
 */
class MeController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     summary="Get user profile",
     *     description="Retrieve the authenticated user's profile information",
     *     operationId="getProfile",
     *     tags={"Profile"},
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
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function __invoke()
    {
        return $this->responseWithSuccess('Profile retrieved', [
            'user' => new UserResource(auth()->user()),
        ], 200);
    }
}
