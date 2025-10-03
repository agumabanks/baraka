<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookManagementService
{
    public function createWebhook(array $webhookData): array
    {
        DB::table("webhook_endpoints")->insert([
            "name" => $webhookData["name"],
            "url" => $webhookData["url"],
            "events" => json_encode($webhookData["events"]),
            "headers" => json_encode($webhookData["headers"] ?? []),
            "secret" => $webhookData["secret"] ?? Str::random(32),
            "status" => "active",
            "retry_attempts" => $webhookData["retry_attempts"] ?? 3,
            "timeout" => $webhookData["timeout"] ?? 30,
            "created_by" => $webhookData["created_by"],
            "created_at" => now(),
        ]);

        return [
            "success" => true,
            "webhook_id" => DB::getPdo()->lastInsertId(),
            "message" => "Webhook created successfully",
        ];
    }

    public function triggerWebhook(string $event, array $payload): array
    {
        $endpoints = DB::table("webhook_endpoints")
            ->where("status", "active")
            ->whereJsonContains("events", $event)
            ->get();

        $results = [];

        foreach ($endpoints as $endpoint) {
            $result = $this->deliverWebhook($endpoint, $event, $payload);
            $results[] = [
                "endpoint_id" => $endpoint->id,
                "url" => $endpoint->url,
                "success" => $result["success"],
                "status_code" => $result["status_code"] ?? null,
                "error" => $result["error"] ?? null,
            ];
        }

        return [
            "event" => $event,
            "total_endpoints" => count($endpoints),
            "successful_deliveries" => collect($results)->where("success", true)->count(),
            "failed_deliveries" => collect($results)->where("success", false)->count(),
            "results" => $results,
        ];
    }

    private function deliverWebhook($endpoint, string $event, array $payload): array
    {
        $webhookPayload = [
            "event" => $event,
            "timestamp" => now()->toISOString(),
            "data" => $payload,
        ];

        $headers = json_decode($endpoint->headers, true);
        $headers["Content-Type"] = "application/json";
        $headers["User-Agent"] = "Baraka-Webhook/1.0";

        try {
            $response = Http::timeout($endpoint->timeout)
                ->withHeaders($headers)
                ->post($endpoint->url, $webhookPayload);

            $success = $response->successful();
            $statusCode = $response->status();

            DB::table("webhook_deliveries")->insert([
                "webhook_endpoint_id" => $endpoint->id,
                "event" => $event,
                "payload" => json_encode($webhookPayload),
                "response_status" => $statusCode,
                "response_body" => $response->body(),
                "delivered_at" => now(),
                "created_at" => now(),
            ]);

            return [
                "success" => $success,
                "status_code" => $statusCode,
                "response" => $response->body(),
            ];

        } catch (\Exception $e) {
            DB::table("webhook_deliveries")->insert([
                "webhook_endpoint_id" => $endpoint->id,
                "event" => $event,
                "payload" => json_encode($webhookPayload),
                "response_status" => 0,
                "response_body" => $e->getMessage(),
                "delivered_at" => now(),
                "created_at" => now(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }
}
