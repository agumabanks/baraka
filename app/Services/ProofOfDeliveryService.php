<?php

namespace App\Services;

use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Models\User;
use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProofOfDeliveryService
{
    protected GeofencingService $geofencingService;

    public function __construct(GeofencingService $geofencingService)
    {
        $this->geofencingService = $geofencingService;
    }

    /**
     * Record a delivery with full proof of delivery
     */
    public function recordDelivery(
        Shipment $shipment,
        User $deliveryPerson,
        array $data
    ): ScanEvent {
        DB::beginTransaction();
        
        try {
            // Validate GPS location
            $locationValidation = $this->validateDeliveryLocation($shipment, $data);
            
            // Upload POD files
            $photoPath = null;
            $signaturePath = null;
            
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $photoPath = $this->uploadPhoto($shipment, $data['photo']);
            }
            
            if (isset($data['signature']) && $data['signature'] instanceof UploadedFile) {
                $signaturePath = $this->uploadSignature($shipment, $data['signature']);
            } elseif (isset($data['signature_base64'])) {
                $signaturePath = $this->saveSignatureFromBase64($shipment, $data['signature_base64']);
            }

            // Create scan event
            $scanEvent = ScanEvent::create([
                'shipment_id' => $shipment->id,
                'sscc' => $shipment->tracking_number,
                'type' => ScanType::DELIVERED,
                'status_after' => ShipmentStatus::DELIVERED,
                'branch_id' => $shipment->dest_branch_id,
                'user_id' => $deliveryPerson->id,
                'occurred_at' => now(),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'gps_accuracy' => $data['gps_accuracy'] ?? null,
                'photo_path' => $photoPath,
                'signature_path' => $signaturePath,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_id_type' => $data['recipient_id_type'] ?? null,
                'recipient_id_number' => $data['recipient_id_number'] ?? null,
                'is_validated' => $locationValidation['is_valid'],
                'validation_errors' => $locationValidation['validation_errors'] ?: null,
                'distance_from_expected' => $locationValidation['distance_from_center'],
                'geofence_id' => $locationValidation['geofence_id'],
                'is_within_geofence' => $locationValidation['within_geofence'],
                'device_id' => $data['device_id'] ?? null,
                'device_info' => $data['device_info'] ?? null,
                'note' => $data['notes'] ?? null,
                'geojson' => $this->buildGeoJson($data['latitude'] ?? null, $data['longitude'] ?? null),
            ]);

            // Update shipment status
            $shipment->update([
                'status' => ShipmentStatus::DELIVERED->value,
                'current_status' => ShipmentStatus::DELIVERED,
                'delivered_at' => now(),
                'delivered_by' => $deliveryPerson->id,
                'last_scan_event_id' => $scanEvent->id,
            ]);

            // Trigger events for invoice generation, notifications, etc.
            event(new \App\Events\ShipmentStatusChanged($shipment, ShipmentStatus::DELIVERED));

            DB::commit();

            Log::info('Delivery recorded successfully', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'delivered_by' => $deliveryPerson->id,
                'location_valid' => $locationValidation['is_valid'],
            ]);

            return $scanEvent;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record delivery', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Record a scan event with GPS validation
     */
    public function recordScan(
        Shipment $shipment,
        User $user,
        ScanType $scanType,
        array $data
    ): ScanEvent {
        // Validate GPS location
        $locationValidation = $this->geofencingService->validateScanLocation(
            $data['latitude'] ?? 0,
            $data['longitude'] ?? 0,
            $data['branch_id'] ?? $shipment->origin_branch_id,
            $data['hub_id'] ?? null
        );

        // Determine new status based on scan type
        $newStatus = $this->getStatusFromScanType($scanType);

        // Upload photo if provided
        $photoPath = null;
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $photoPath = $this->uploadPhoto($shipment, $data['photo']);
        }

        $scanEvent = ScanEvent::create([
            'shipment_id' => $shipment->id,
            'sscc' => $shipment->tracking_number,
            'type' => $scanType,
            'status_after' => $newStatus,
            'branch_id' => $data['branch_id'] ?? $shipment->origin_branch_id,
            'user_id' => $user->id,
            'occurred_at' => now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'gps_accuracy' => $data['gps_accuracy'] ?? null,
            'photo_path' => $photoPath,
            'is_validated' => $locationValidation['is_valid'],
            'validation_errors' => $locationValidation['validation_errors'] ?: null,
            'distance_from_expected' => $locationValidation['distance_from_center'],
            'geofence_id' => $locationValidation['geofence_id'],
            'is_within_geofence' => $locationValidation['within_geofence'],
            'device_id' => $data['device_id'] ?? null,
            'device_info' => $data['device_info'] ?? null,
            'note' => $data['notes'] ?? null,
            'geojson' => $this->buildGeoJson($data['latitude'] ?? null, $data['longitude'] ?? null),
        ]);

        // Update shipment status if automated transitions enabled
        if ($newStatus && ($data['auto_update_status'] ?? true)) {
            $this->updateShipmentStatus($shipment, $scanType, $newStatus, $scanEvent);
        }

        return $scanEvent;
    }

    /**
     * Validate delivery location against expected destination
     */
    protected function validateDeliveryLocation(Shipment $shipment, array $data): array
    {
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return [
                'is_valid' => false,
                'within_geofence' => false,
                'geofence_id' => null,
                'distance_from_center' => null,
                'validation_errors' => ['GPS coordinates not provided'],
            ];
        }

        return $this->geofencingService->validateScanLocation(
            $data['latitude'],
            $data['longitude'],
            $shipment->dest_branch_id,
            null,
            $data['max_distance_tolerance'] ?? 1000 // 1km tolerance for deliveries
        );
    }

    /**
     * Upload delivery photo
     */
    protected function uploadPhoto(Shipment $shipment, UploadedFile $photo): string
    {
        $filename = sprintf(
            'pod_%s_%s_photo.%s',
            $shipment->tracking_number,
            now()->format('YmdHis'),
            $photo->getClientOriginalExtension()
        );

        $path = $photo->storeAs(
            "pod/{$shipment->id}",
            $filename,
            'public'
        );

        return $path;
    }

    /**
     * Upload signature image
     */
    protected function uploadSignature(Shipment $shipment, UploadedFile $signature): string
    {
        $filename = sprintf(
            'pod_%s_%s_signature.%s',
            $shipment->tracking_number,
            now()->format('YmdHis'),
            $signature->getClientOriginalExtension()
        );

        $path = $signature->storeAs(
            "pod/{$shipment->id}",
            $filename,
            'public'
        );

        return $path;
    }

    /**
     * Save signature from base64 encoded string (from canvas)
     */
    protected function saveSignatureFromBase64(Shipment $shipment, string $base64): string
    {
        // Remove data URL prefix if present
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = base64_decode($base64);

        $filename = sprintf(
            'pod_%s_%s_signature.png',
            $shipment->tracking_number,
            now()->format('YmdHis')
        );

        $path = "pod/{$shipment->id}/{$filename}";
        Storage::disk('public')->put($path, $imageData);

        return $path;
    }

    /**
     * Get status from scan type
     */
    protected function getStatusFromScanType(ScanType $scanType): ?ShipmentStatus
    {
        return match ($scanType) {
            ScanType::PICKUP => ShipmentStatus::PICKED_UP,
            ScanType::INBOUND => ShipmentStatus::IN_TRANSIT,
            ScanType::OUTBOUND => ShipmentStatus::IN_TRANSIT,
            ScanType::OUT_FOR_DELIVERY => ShipmentStatus::OUT_FOR_DELIVERY,
            ScanType::DELIVERED => ShipmentStatus::DELIVERED,
            ScanType::RETURNED => ShipmentStatus::RETURNED,
            ScanType::EXCEPTION => null, // Don't auto-update for exceptions
            default => null,
        };
    }

    /**
     * Update shipment status based on scan
     */
    protected function updateShipmentStatus(
        Shipment $shipment,
        ScanType $scanType,
        ShipmentStatus $newStatus,
        ScanEvent $scanEvent
    ): void {
        $updates = [
            'status' => strtolower($newStatus->value),
            'current_status' => $newStatus,
            'last_scan_event_id' => $scanEvent->id,
        ];

        // Set timestamp based on scan type
        $timestampField = match ($scanType) {
            ScanType::PICKUP => 'picked_up_at',
            ScanType::OUT_FOR_DELIVERY => 'out_for_delivery_at',
            ScanType::DELIVERED => 'delivered_at',
            ScanType::RETURNED => 'returned_at',
            default => null,
        };

        if ($timestampField) {
            $updates[$timestampField] = now();
        }

        $shipment->update($updates);

        // Trigger status changed event
        event(new \App\Events\ShipmentStatusChanged($shipment, $newStatus));
    }

    /**
     * Build GeoJSON point from coordinates
     */
    protected function buildGeoJson(?float $latitude, ?float $longitude): ?array
    {
        if (!$latitude || !$longitude) {
            return null;
        }

        return [
            'type' => 'Point',
            'coordinates' => [$longitude, $latitude],
        ];
    }

    /**
     * Get POD details for a shipment
     */
    public function getPodDetails(Shipment $shipment): ?array
    {
        $deliveryScan = ScanEvent::where('shipment_id', $shipment->id)
            ->where('type', ScanType::DELIVERED)
            ->latest('occurred_at')
            ->first();

        if (!$deliveryScan) {
            return null;
        }

        return [
            'delivered_at' => $deliveryScan->occurred_at,
            'delivered_by' => $deliveryScan->user,
            'recipient_name' => $deliveryScan->recipient_name,
            'recipient_id_type' => $deliveryScan->recipient_id_type,
            'recipient_id_number' => $deliveryScan->recipient_id_number,
            'photo_url' => $deliveryScan->photo_path ? Storage::url($deliveryScan->photo_path) : null,
            'signature_url' => $deliveryScan->signature_path ? Storage::url($deliveryScan->signature_path) : null,
            'location' => [
                'latitude' => $deliveryScan->latitude,
                'longitude' => $deliveryScan->longitude,
                'accuracy' => $deliveryScan->gps_accuracy,
            ],
            'validation' => [
                'is_validated' => $deliveryScan->is_validated,
                'within_geofence' => $deliveryScan->is_within_geofence,
                'distance_from_expected' => $deliveryScan->distance_from_expected,
                'errors' => $deliveryScan->validation_errors,
            ],
            'notes' => $deliveryScan->note,
        ];
    }

    /**
     * Validate POD completeness
     */
    public function validatePodCompleteness(Shipment $shipment): array
    {
        $pod = $this->getPodDetails($shipment);
        
        if (!$pod) {
            return [
                'is_complete' => false,
                'missing' => ['No POD record found'],
            ];
        }

        $missing = [];

        if (!$pod['signature_url']) {
            $missing[] = 'Signature';
        }

        if (!$pod['recipient_name']) {
            $missing[] = 'Recipient name';
        }

        if (!$pod['location']['latitude'] || !$pod['location']['longitude']) {
            $missing[] = 'GPS location';
        }

        return [
            'is_complete' => empty($missing),
            'missing' => $missing,
            'pod' => $pod,
        ];
    }
}
