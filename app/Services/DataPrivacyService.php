<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataPrivacyService
{
    protected array $gdprRights = [
        "right_to_access",
        "right_to_rectification", 
        "right_to_erasure",
        "right_to_restriction",
        "right_to_data_portability",
        "right_to_object",
    ];

    public function processDataSubjectRequest(array $requestData): array
    {
        $requestType = $requestData["request_type"];
        $userId = $requestData["user_id"];

        if (!in_array($requestType, $this->gdprRights)) {
            return [
                "success" => false,
                "message" => "Invalid request type",
            ];
        }

        DB::table("data_subject_requests")->insert([
            "user_id" => $userId,
            "request_type" => $requestType,
            "request_data" => json_encode($requestData),
            "status" => "pending",
            "requested_at" => now(),
            "created_at" => now(),
        ]);

        return [
            "success" => true,
            "message" => "Data subject request submitted successfully",
            "request_id" => DB::getPdo()->lastInsertId(),
        ];
    }

    public function anonymizeUserData($userId): array
    {
        // Anonymize personal data while keeping operational data
        $user = DB::table("users")->where("id", $userId)->first();
        
        if (!$user) {
            return ["success" => false, "message" => "User not found"];
        }

        DB::table("users")->where("id", $userId)->update([
            "name" => "Anonymous User " . $userId,
            "email" => "anonymous{$userId}@deleted.local",
            "phone" => null,
            "address" => null,
            "anonymized_at" => now(),
        ]);

        Log::info("User data anonymized", ["user_id" => $userId]);

        return [
            "success" => true,
            "message" => "User data anonymized successfully",
        ];
    }

    public function getDataPrivacyReport(Carbon $startDate, Carbon $endDate): array
    {
        $requests = DB::table("data_subject_requests")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->get();

        return [
            "period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "statistics" => [
                "total_requests" => $requests->count(),
                "pending_requests" => $requests->where("status", "pending")->count(),
                "completed_requests" => $requests->where("status", "completed")->count(),
                "requests_by_type" => $requests->groupBy("request_type")->map->count(),
            ],
            "generated_at" => now(),
        ];
    }
}
