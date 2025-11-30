<?php

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Models\BranchAlert;
use App\Models\Shipment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ShipmentSlaMonitor extends Command
{
    protected $signature = 'shipment:sla-monitor {--window=24 : Hours ahead to treat as approaching SLA}';
    protected $description = 'Raise alerts for shipments approaching or breaching SLA deadlines';

    public function handle(): int
    {
        $windowHours = (int) $this->option('window');
        $now = Carbon::now();
        $soon = $now->copy()->addHours($windowHours);

        $shipments = Shipment::query()
            ->whereNotIn('current_status', [ShipmentStatus::DELIVERED->value, ShipmentStatus::CANCELLED->value])
            ->whereNotNull('expected_delivery_date')
            ->where(function ($q) use ($soon) {
                $q->where('expected_delivery_date', '<=', $soon);
            })
            ->select(['id', 'origin_branch_id', 'dest_branch_id', 'tracking_number', 'expected_delivery_date'])
            ->get();

        foreach ($shipments as $shipment) {
            $expected = $shipment->expected_delivery_date;
            if (! $expected) {
                continue;
            }

            $overdue = $expected->isPast();
            $alertType = $overdue ? 'SHIPMENT_OVERDUE' : 'SHIPMENT_SLA';
            $severity = $overdue ? 'CRITICAL' : 'WARNING';
            $message = $overdue
                ? "Shipment {$shipment->tracking_number} is overdue by {$expected->diffForHumans($now, true)}"
                : "Shipment {$shipment->tracking_number} approaching SLA in {$now->diffForHumans($expected, true)}";

            foreach ([$shipment->origin_branch_id, $shipment->dest_branch_id] as $branchId) {
                if (! $branchId) {
                    continue;
                }

                $existing = BranchAlert::open()
                    ->where('branch_id', $branchId)
                    ->where('alert_type', $alertType)
                    ->where('context->shipment_id', $shipment->id)
                    ->first();

                if ($existing) {
                    continue;
                }

                BranchAlert::create([
                    'branch_id' => $branchId,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'status' => 'OPEN',
                    'title' => $overdue ? 'Shipment SLA breach risk' : 'Shipment approaching SLA',
                    'message' => $message,
                    'context' => [
                        'shipment_id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                        'expected_delivery_date' => $shipment->expected_delivery_date,
                        'branch_role' => $branchId === $shipment->origin_branch_id ? 'origin' : 'destination',
                    ],
                    'triggered_at' => $now,
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
