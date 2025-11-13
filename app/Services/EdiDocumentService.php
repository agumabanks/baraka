<?php

namespace App\Services;

use App\Models\EdiMapping;
use App\Models\EdiTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EdiDocumentService
{
    /**
     * Normalize inbound payload using mapping definitions and sensible defaults.
     */
    public function normalize(string $documentType, array $payload): array
    {
        $documentType = strtoupper($documentType);
        
        // Try to get mapping, but fall back to default if table doesn't exist
        try {
            $mapping = EdiMapping::where('document_type', $documentType)
                ->where('active', true)
                ->first();

            $data = $mapping
                ? $this->applyMapping($mapping->field_map, $payload)
                : $this->defaultNormalization($documentType, $payload);
        } catch (\Exception $e) {
            // Fall back to default normalization if mapping table doesn't exist
            $data = $this->defaultNormalization($documentType, $payload);
        }

        $data['document_type'] = $documentType;
        $data['document_number'] = $data['document_number'] ?? $data['shipment_number'] ?? $data['reference'] ?? null;

        return $data;
    }

    /**
     * Create an EDI 997 acknowledgement payload referencing the provided transaction.
     */
    public function generateFunctionalAck(EdiTransaction $transaction, string $status = 'AC'): array
    {
        $controlNumber = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'document_type' => '997',
            'acknowledgement_status' => $status,
            'original_document_type' => $transaction->document_type,
            'original_document_number' => $transaction->document_number,
            'control_number' => $controlNumber,
            'application_sender_code' => config('app.name'),
            'application_receiver_code' => $transaction->provider?->name ?? 'UNKNOWN',
            'timestamp' => now()->toIso8601String(),
            'errors' => [],
        ];
    }

    private function applyMapping(array $fieldMap, array $payload): array
    {
        $normalized = [];

        foreach ($fieldMap as $target => $source) {
            $normalized[$target] = Arr::get($payload, $source);
        }

        return $normalized;
    }

    private function defaultNormalization(string $documentType, array $payload): array
    {
        return match ($documentType) {
            '850' => [
                'document_number' => Arr::get($payload, 'purchase_order.number', Arr::get($payload, 'order.number')),
                'buyer' => Arr::get($payload, 'purchase_order.buyer', Arr::get($payload, 'buyer')),
                'ship_to' => Arr::get($payload, 'purchase_order.ship_to', Arr::get($payload, 'ship_to')),
                'line_items' => Arr::get($payload, 'purchase_order.items', Arr::get($payload, 'items', [])),
                'requested_ship_date' => Arr::get($payload, 'purchase_order.requested_ship_date'),
            ],
            '856' => [
                'document_number' => Arr::get($payload, 'shipment.notice_number', Arr::get($payload, 'shipment_number')),
                'shipment_status' => Arr::get($payload, 'shipment.status'),
                'carrier' => Arr::get($payload, 'shipment.carrier'),
                'packages' => Arr::get($payload, 'shipment.packages', []),
                'estimated_delivery' => Arr::get($payload, 'shipment.estimated_delivery'),
            ],
            '997' => [
                'acknowledgement_status' => Arr::get($payload, 'acknowledgement.status', Arr::get($payload, 'status', 'AC')),
                'original_document_number' => Arr::get($payload, 'acknowledgement.document_number', Arr::get($payload, 'document_number')),
                'errors' => Arr::get($payload, 'acknowledgement.errors', []),
            ],
            default => $payload,
        };
    }
}
