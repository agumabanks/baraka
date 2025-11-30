<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\Api\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * API Key Management (Admin only)
 */
class ApiKeyController extends Controller
{
    protected ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * List API keys
     */
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['api_keys' => $keys->map(fn($k) => $this->formatKey($k))],
        ]);
    }

    /**
     * Create API key
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Validation failed', 'details' => $validator->errors()],
            ], 422);
        }

        $result = $this->apiKeyService->createKey($validator->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'api_key' => $this->formatKey($result['api_key']),
                'credentials' => [
                    'key' => $result['key'],
                    'secret' => $result['secret'],
                ],
                'warning' => 'Save these credentials securely. The secret will not be shown again.',
            ],
        ], 201);
    }

    /**
     * Get API key details
     */
    public function show(ApiKey $apiKey): JsonResponse
    {
        $stats = $this->apiKeyService->getUsageStats($apiKey);

        return response()->json([
            'success' => true,
            'data' => [
                'api_key' => $this->formatKey($apiKey),
                'usage' => $stats,
            ],
        ]);
    }

    /**
     * Update API key
     */
    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'permissions' => 'nullable|array',
            'allowed_ips' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:1000',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Validation failed', 'details' => $validator->errors()],
            ], 422);
        }

        $apiKey->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => ['api_key' => $this->formatKey($apiKey->fresh())],
        ]);
    }

    /**
     * Revoke API key
     */
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $this->apiKeyService->revokeKey($apiKey);

        return response()->json([
            'success' => true,
            'data' => ['message' => 'API key revoked'],
        ]);
    }

    /**
     * Regenerate secret
     */
    public function regenerateSecret(ApiKey $apiKey): JsonResponse
    {
        $newSecret = $this->apiKeyService->regenerateSecret($apiKey);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $apiKey->key,
                'secret' => $newSecret,
                'warning' => 'Save these credentials securely. The secret will not be shown again.',
            ],
        ]);
    }

    /**
     * Format API key for response
     */
    protected function formatKey(ApiKey $apiKey): array
    {
        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $apiKey->key,
            'permissions' => $apiKey->permissions,
            'allowed_ips' => $apiKey->allowed_ips,
            'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
            'is_active' => $apiKey->is_active,
            'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
            'expires_at' => $apiKey->expires_at?->toIso8601String(),
            'created_at' => $apiKey->created_at->toIso8601String(),
        ];
    }
}
