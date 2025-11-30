<?php

namespace App\Console\Commands;

use App\Models\BranchAlert;
use App\Models\BranchHandoff;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class HandoffSlaMonitor extends Command
{
    protected $signature = 'handoff:sla-monitor';
    protected $description = 'Generate alerts for handoffs approaching SLA breach';

    public function handle(): int
    {
        $now = Carbon::now();
        $soon = $now->copy()->addHours(2);

        $handoffs = BranchHandoff::query()
            ->where('status', 'APPROVED')
            ->whereNull('handoff_completed_at')
            ->whereNotNull('expected_hand_off_at')
            ->with(['shipment'])
            ->get();

        foreach ($handoffs as $handoff) {
            $expected = $handoff->expected_hand_off_at;
            if (! $expected) {
                continue;
            }

            $overdue = $expected->isPast();
            $approaching = $expected->lessThanOrEqualTo($soon) && ! $overdue;
            if (! $overdue && ! $approaching) {
                continue;
            }

            $alertType = $overdue ? 'HANDOFF_OVERDUE' : 'HANDOFF_SLA';
            $severity = $overdue ? 'CRITICAL' : 'WARNING';
            $message = $overdue
                ? 'Handoff '.$handoff->id.' is overdue by '.$expected->diffForHumans($now, true)
                : 'Handoff '.$handoff->id.' is nearing SLA in '.$now->diffForHumans($expected, true);

            foreach ([$handoff->origin_branch_id, $handoff->dest_branch_id] as $branchId) {
                $existing = BranchAlert::open()
                    ->where('alert_type', $alertType)
                    ->where('branch_id', $branchId)
                    ->where('context->handoff_id', $handoff->id)
                    ->first();

                if ($existing) {
                    continue;
                }

                BranchAlert::create([
                    'branch_id' => $branchId,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'status' => 'OPEN',
                    'title' => $overdue ? 'Handoff SLA breach risk' : 'Handoff approaching SLA',
                    'message' => $message,
                    'context' => [
                        'handoff_id' => $handoff->id,
                        'shipment_id' => $handoff->shipment_id,
                        'expected_hand_off_at' => $handoff->expected_hand_off_at,
                        'branch_role' => $branchId === $handoff->origin_branch_id ? 'origin' : 'destination',
                    ],
                    'triggered_at' => $now,
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
