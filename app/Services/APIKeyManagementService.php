<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class APIKeyManagementService
{
    public function generateAPIKey(array $keyData): array
    {
        $apiKey = Str::random(32);
        $secretKey = Str::random(64);

        DB::table("api_keys")->insert([
            "name" => $keyData["name"],
            "description" => $keyData["description"] ?? null,
            "api_key" => hash("sha256", $apiKey),
            "secret_key" => Hash::make($secretKey),
            "permissions" => json_encode($keyData["permissions"] ?? []),
            "rate_limit" => $keyData["rate_limit"] ?? 1000,
            "expires_at" => isset($keyData["expires_at"]) ? Carbon::parse($keyData["expires_at"]) : null,
            "created_by" => $keyData["created_by"],
            "status" => "active",
            "created_at" => now(),
        ]);

        return [
            "success" => true,
            "api_key" => $apiKey,
            "secret_key" => $secretKey,
            "key_id" => DB::getPdo()->lastInsertId(),
            "message" => "API key generated successfully",
        ];
    }

    public function validateAPIKey(string $apiKey): array
    {
        $hashedKey = hash("sha256", $apiKey);
        $keyRecord = DB::table("api_keys")
            ->where("api_key", $hashedKey)
            ->where("status", "active")
            ->first();

        if (!$keyRecord) {
            return ["valid" => false, "message" => "Invalid API key"];
        }

        if ($keyRecord->expires_at && Carbon::parse($keyRecord->expires_at)->isPast()) {
            return ["valid" => false, "message" => "API key has expired"];
        }

        return [
            "valid" => true,
            "key_id" => $keyRecord->id,
            "permissions" => json_decode($keyRecord->permissions, true),
            "rate_limit" => $keyRecord->rate_limit,
        ];
    }

    public function revokeAPIKey(int $keyId): array
    {
        $updated = DB::table("api_keys")
            ->where("id", $keyId)
            ->update([
                "status" => "revoked",
                "revoked_at" => now(),
            ]);

        return [
            "success" => $updated > 0,
            "message" => $updated > 0 ? "API key revoked successfully" : "API key not found",
        ];
    }

    public function updateAPIKeyPermissions(int $keyId, array $permissions): array
    {
        $updated = DB::table("api_keys")
            ->where("id", $keyId)
            ->update([
                "permissions" => json_encode($permissions),
                "updated_at" => now(),
            ]);

        return [
            "success" => $updated > 0,
            "message" => $updated > 0 ? "API key permissions updated" : "API key not found",
        ];
    }

    public function getAPIKeyUsage(int $keyId, Carbon $startDate, Carbon $endDate): array
    {
        $usage = DB::table("api_logs")
            ->where("api_key_id", $keyId)
            ->whereBetween("created_at", [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, COUNT(*) as requests, SUM(response_time) as total_time")
            ->groupBy("date")
            ->orderBy("date")
            ->get();

        return [
            "key_id" => $keyId,
            "period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "usage" => $usage,
            "total_requests" => $usage->sum("requests"),
            "average_response_time" => $usage->avg("total_time"),
        ];
    }

    public function checkRateLimit(int $keyId): array
    {
        $keyRecord = DB::table("api_keys")->where("id", $keyId)->first();
        
        if (!$keyRecord) {
            return ["allowed" => false, "message" => "API key not found"];
        }

        $currentHour = now()->format("Y-m-d H");
        $requestsThisHour = DB::table("api_logs")
            ->where("api_key_id", $keyId)
            ->whereRaw("DATE_FORMAT(created_at, \"%Y-%m-%d %H\") = ?", [$currentHour])
            ->count();

        $allowed = $requestsThisHour < $keyRecord->rate_limit;

        return [
            "allowed" => $allowed,
            "current_usage" => $requestsThisHour,
            "limit" => $keyRecord->rate_limit,
            "remaining" => max(0, $keyRecord->rate_limit - $requestsThisHour),
        ];
    }

    public function logAPIRequest(int $keyId, string $endpoint, int $responseTime, int $statusCode): void
    {
        DB::table("api_logs")->insert([
            "api_key_id" => $keyId,
            "endpoint" => $endpoint,
            "response_time" => $responseTime,
            "status_code" => $statusCode,
            "ip_address" => request()->ip(),
            "user_agent" => request()->userAgent(),
            "created_at" => now(),
        ]);
    }
}
