<?php

namespace App\Services\Logistics;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Models\ShipmentTransition;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShipmentLifecycleService
{
    /**
     * Allowed lifecycle transitions in canonical flow order.
     */
    private const TRANSITIONS = [
        'BOOKED' => ['PICKUP_SCHEDULED', 'CANCELLED'],
        'PICKUP_SCHEDULED' => ['PICKED_UP', 'CANCELLED'],
        'PICKED_UP' => ['AT_ORIGIN_HUB', 'RETURN_INITIATED', 'EXCEPTION'],
        'AT_ORIGIN_HUB' => ['BAGGED', 'RETURN_INITIATED', 'EXCEPTION'],
        'BAGGED' => ['LINEHAUL_DEPARTED', 'EXCEPTION'],
        'LINEHAUL_DEPARTED' => ['LINEHAUL_ARRIVED', 'EXCEPTION'],
        'LINEHAUL_ARRIVED' => ['AT_DESTINATION_HUB', 'EXCEPTION'],
        'AT_DESTINATION_HUB' => ['CUSTOMS_HOLD', 'CUSTOMS_CLEARED', 'RETURN_INITIATED'],
        'CUSTOMS_HOLD' => ['CUSTOMS_CLEARED', 'RETURN_INITIATED', 'EXCEPTION'],
        'CUSTOMS_CLEARED' => ['OUT_FOR_DELIVERY', 'RETURN_INITIATED'],
        'OUT_FOR_DELIVERY' => ['DELIVERED', 'RETURN_INITIATED', 'EXCEPTION'],
        'RETURN_INITIATED' => ['RETURN_IN_TRANSIT', 'RETURNED'],
        'RETURN_IN_TRANSIT' => ['RETURNED'],
    ];

    /**
     * Transition a shipment to a new lifecycle status.
     *
     * @throws Throwable
     */
    public function transition(Shipment $shipment, ShipmentStatus $target, array $context = []): ShipmentTransition
    {
        $force = (bool) ($context['force'] ?? false);
        $performedById = $context['performed_by']
            ?? ($context['performed_by_user'] instanceof Authenticatable ? $context['performed_by_user']->getAuthIdentifier() : null)
            ?? Auth::id();

        /** @var ShipmentStatus|null $current */
        $current = $shipment->current_status instanceof ShipmentStatus
            ? $shipment->current_status
            : ShipmentStatus::fromString((string) $shipment->current_status);

        if (! $force && ! $this->isTransitionAllowed($current, $target)) {
            throw new \InvalidArgumentException(sprintf(
                'Transition from %s to %s is not permitted.',
                $current?->value ?? 'UNKNOWN',
                $target->value
            ));
        }

        return DB::transaction(function () use ($shipment, $target, $current, $context, $performedById) {
            $timestamp = $context['timestamp'] ?? $context['at'] ?? now();

            $updates = [
                'current_status' => $target->value,
                'status' => strtolower($target->value),
            ];

            if ($column = $target->associatedTimestampColumn()) {
                $shouldOverwrite = (bool) ($context['overwrite_timestamp'] ?? false);
                if ($shouldOverwrite || empty($shipment->{$column})) {
                    $updates[$column] = $timestamp;
                }
            }

            if (isset($context['location_type'])) {
                $updates['current_location_type'] = $context['location_type'];
            }

            if (isset($context['location_id'])) {
                $updates['current_location_id'] = $context['location_id'];
            }

            if (($scanEvent = $context['scan_event'] ?? null) instanceof ScanEvent) {
                $updates['last_scan_event_id'] = $scanEvent->id;
            }

            // Exception handling metadata
            if ($target === ShipmentStatus::EXCEPTION) {
                $updates['has_exception'] = true;
                $updates['exception_type'] = $context['exception_type'] ?? $shipment->exception_type;
                $updates['exception_severity'] = $context['exception_severity'] ?? $shipment->exception_severity;
                $updates['exception_notes'] = $context['exception_notes'] ?? $shipment->exception_notes;
                $updates['exception_occurred_at'] = $timestamp;
            }

            if ($target->isTerminal() && $target !== ShipmentStatus::EXCEPTION) {
                $updates['has_exception'] = $target === ShipmentStatus::EXCEPTION;
            }

            if ($target === ShipmentStatus::RETURN_INITIATED) {
                $updates['return_reason'] = $context['return_reason'] ?? $shipment->return_reason;
                $updates['return_notes'] = $context['return_notes'] ?? $shipment->return_notes;
            }

            if ($target === ShipmentStatus::CANCELLED) {
                $updates['cancelled_at'] = $timestamp;
            }

            // Persist shipment updates
            $shipment->fill($updates);
            $shipment->save();

            $transition = ShipmentTransition::create([
                'shipment_id' => $shipment->id,
                'from_status' => $current?->value,
                'to_status' => $target->value,
                'trigger' => $context['trigger'] ?? ($context['scan_event'] ? 'scan_event' : 'manual'),
                'source_type' => $this->resolveSourceType($context['source'] ?? $context['scan_event'] ?? null),
                'source_id' => $this->resolveSourceId($context['source'] ?? $context['scan_event'] ?? null),
                'performed_by' => $performedById,
                'context' => $this->sanitizeContext($context),
            ]);

            try {
                event(new ShipmentStatusChanged($shipment, $context['scan_event'] ?? null));
            } catch (\Throwable $eventException) {
                Log::warning('Failed to dispatch ShipmentStatusChanged event', [
                    'shipment_id' => $shipment->id,
                    'error' => $eventException->getMessage(),
                ]);
            }

            return $transition;
        });
    }

    public function allowedNextStatuses(?ShipmentStatus $current): array
    {
        $currentKey = $current?->value ?? null;

        if ($currentKey === null) {
            return [ShipmentStatus::BOOKED];
        }

        $targets = self::TRANSITIONS[$currentKey] ?? [];

        return array_values(array_filter(array_map(
            fn (string $status) => ShipmentStatus::fromString($status),
            $targets
        )));
    }

    private function isTransitionAllowed(?ShipmentStatus $from, ShipmentStatus $to): bool
    {
        if ($from === null) {
            return $to === ShipmentStatus::BOOKED;
        }

        if ($from === $to) {
            return true;
        }

        if ($from->isTerminal()) {
            return false;
        }

        $allowed = self::TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    private function resolveSourceType($source): ?string
    {
        if ($source instanceof Model) {
            return $source->getMorphClass();
        }

        if (is_string($source)) {
            return $source;
        }

        return null;
    }

    private function resolveSourceId($source): ?int
    {
        if ($source instanceof Model) {
            return $source->getKey();
        }

        if (is_numeric($source)) {
            return (int) $source;
        }

        return null;
    }

    private function sanitizeContext(array $context): array
    {
        $keysToStrip = ['force', 'scan_event', 'source', 'performed_by', 'performed_by_user'];

        $sanitized = Arr::except($context, $keysToStrip);

        array_walk_recursive($sanitized, function (&$value) {
            if ($value instanceof Model) {
                $value = [
                    'type' => $value->getMorphClass(),
                    'id' => $value->getKey(),
                ];
            }
        });

        return $sanitized;
    }
}
