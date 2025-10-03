<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class EDIProviderService
{
    public function configureEDIProvider(array $configData): array
    {
        DB::table("edi_providers")->insert([
            "name" => $configData["name"],
            "provider_type" => $configData["provider_type"],
            "connection_details" => json_encode($configData["connection_details"]),
            "status" => "active",
            "created_by" => $configData["created_by"],
            "created_at" => now(),
        ]);

        return [
            "success" => true,
            "provider_id" => DB::getPdo()->lastInsertId(),
            "message" => "EDI provider configured successfully",
        ];
    }

    public function sendEDIMessage(int $providerId, string $messageType, array $data): array
    {
        $provider = DB::table("edi_providers")->where("id", $providerId)->first();
        
        if (!$provider) {
            return ["success" => false, "message" => "EDI provider not found"];
        }

        DB::table("edi_messages")->insert([
            "edi_provider_id" => $providerId,
            "message_type" => $messageType,
            "direction" => "outbound",
            "message_content" => json_encode($data),
            "status" => "sent",
            "sent_at" => now(),
            "created_at" => now(),
        ]);

        return [
            "success" => true,
            "message_id" => DB::getPdo()->lastInsertId(),
            "message" => "EDI message sent successfully",
        ];
    }
}
