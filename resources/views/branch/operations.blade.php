@extends('branch.layout')

@section('title', 'Operations Board')

@section('content')
    @if($activeMaintenance->isNotEmpty())
        <div class="glass-panel p-4 border border-amber-400/30 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Maintenance in progress</div>
                    <p class="muted text-xs">Capacity limited to {{ $maintenanceCapacity }}% until windows end.</p>
                </div>
                <span class="chip chip-warn text-2xs">{{ $activeMaintenance->count() }} window(s)</span>
            </div>
            <ul class="mt-2 space-y-1 text-xs">
                @foreach($activeMaintenance as $item)
                    <li>
                        <span class="font-semibold">{{ $item->title }}</span>
                        · {{ $item->context['starts_at'] ?? 'start' }} → {{ $item->context['ends_at'] ?? 'end' }}
                        @if(isset($item->context['capacity_factor']))
                            · Capacity {{ $item->context['capacity_factor'] }}%
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <div class="text-sm font-semibold">Live queues</div>
                    <p class="muted text-xs">Filter by direction, status, SLA risk.</p>
                </div>
                <form method="GET" action="{{ route('branch.operations') }}" class="flex flex-wrap gap-2 text-sm">
                    <select name="direction" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        @foreach(['outbound' => 'Outbound', 'inbound' => 'Inbound', 'all' => 'All'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['direction'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        <option value="">Any status</option>
                        @foreach(\App\Enums\ShipmentStatus::orderedLifecycle() as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-xs">
                        <input type="checkbox" name="sla_risk" value="1" @checked($filters['sla_risk'])>
                        SLA risk
                    </label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-xs">
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-xs">
                    <button type="submit" class="chip">Apply</button>
                </form>
            </div>

            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tracking</th>
                            <th>Status</th>
                            <th>Direction</th>
                            <th>Assigned</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                            @php
                                $isSlaRisk = false;
                                $hoursToDeadline = null;
                                if (isset($shipment->expected_delivery_date)) {
                                    $hoursToDeadline = now()->diffInHours($shipment->expected_delivery_date, false);
                                    if ($shipment->delivered_at && $shipment->delivered_at > $shipment->expected_delivery_date) {
                                        $isSlaRisk = true;
                                    } elseif (!$shipment->delivered_at && $shipment->expected_delivery_date <= now()->addHours(24)) {
                                        $isSlaRisk = true;
                                    }
                                }
                            @endphp
                            <tr class="{{ $isSlaRisk ? 'bg-rose-500/5' : '' }}">
                                <td class="font-semibold">
                                    #{{ $shipment->id }}
                                    @if($isSlaRisk)
                                        <span class="text-rose-400 ml-1" title="SLA Risk">⚠️</span>
                                    @endif
                                </td>
                                <td>{{ $shipment->tracking_number ?? '—' }}</td>
                                <td>
                                    <span class="chip text-2xs">{{ $shipment->current_status }}</span>
                                    @if($isSlaRisk && $hoursToDeadline !== null)
                                        <div class="text-rose-400 text-2xs mt-1">D-{{ $hoursToDeadline }}h</div>
                                    @endif
                                </td>
                                <td class="muted text-xs">
                                    @if($shipment->origin_branch_id === $branch->id)
                                        Outbound → {{ $shipment->destBranch?->code ?? $shipment->dest_branch_id }}
                                    @else
                                        Inbound ← {{ $shipment->originBranch?->code ?? $shipment->origin_branch_id }}
                                    @endif
                                </td>
                                <td class="muted text-xs">{{ $shipment->assignedWorker?->user?->name ?? 'Unassigned' }}</td>
                                <td class="muted text-xs">{{ optional($shipment->updated_at)->shortRelativeDiffForHumans() }}</td>
                                <td class="space-y-2">
                                    <form method="POST" action="{{ route('branch.operations.assign') }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                        <select name="worker_id" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 text-xs">
                                            @foreach($workers as $worker)
                                                <option value="{{ $worker->id }}">{{ $worker->user?->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="chip text-2xs" type="submit">Assign</button>
                                    </form>
                                    <form method="POST" action="{{ route('branch.operations.status') }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                        <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 text-xs">
                                            @foreach(\App\Enums\ShipmentStatus::orderedLifecycle() as $status)
                                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                        <button class="chip text-2xs" type="submit">Update</button>
                                    </form>
                                    @if($isSlaRisk)
                                        <form method="POST" action="{{ route('branch.operations.alerts.raise') }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                            <input type="hidden" name="severity" value="critical">
                                            <input type="hidden" name="message" value="SLA risk: approaching deadline">
                                            <button class="chip chip-warn text-2xs" type="submit">Escalate</button>
                                        </form>
                                    @endif
                                    @if(isset($branchOptions) && $branchOptions->count())
                                        <form method="POST" action="{{ route('branch.operations.reroute') }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                            <select name="dest_branch_id" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 text-xs">
                                                @foreach($branchOptions as $opt)
                                                    <option value="{{ $opt->id }}">{{ $opt->code ?? $opt->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="chip text-2xs" type="submit">Reroute</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 muted">No shipments match the filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $shipments->withQueryString()->links() }}
            </div>
        </div>

        <div class="space-y-4">
            <div class="glass-panel p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-semibold">Dispatch backlog</div>
                    <span class="badge {{ $backlog > 0 ? 'badge-warn' : 'badge-success' }}">{{ $backlog }}</span>
                </div>
                <p class="muted text-xs">Unassigned shipments need owners. Assign directly from the table.</p>
            </div>

            <div class="glass-panel p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold">Open alerts</div>
                    <span class="pill-soft">{{ count($alerts) }}</span>
                </div>
                @forelse($alerts as $alert)
                    <div class="border border-white/5 rounded-lg p-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm">{{ $alert->title }}</span>
                            <span class="chip text-2xs">{{ $alert->severity }}</span>
                        </div>
                        <p class="muted text-xs">{{ $alert->message }}</p>
                        @if(isset($alert->context['tracking_number']))
                            <p class="text-2xs text-amber-200">Tracking: {{ $alert->context['tracking_number'] }}</p>
                        @endif
                        <form method="POST" action="{{ route('branch.operations.alerts.resolve', $alert) }}" class="mt-2">
                            @csrf
                            <button class="chip text-2xs">Resolve</button>
                        </form>
                    </div>
                @empty
                    <p class="muted text-sm">No open alerts.</p>
                @endforelse
            </div>

            <div class="glass-panel p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold">Pending handoffs</div>
                    <form method="GET" action="{{ route('branch.operations.handoff.manifest.batch') }}" class="flex items-center gap-2 text-2xs">
                        <input type="hidden" name="direction" value="all">
                        <select name="status" class="bg-obsidian-800 border border-white/10 rounded px-2 py-1">
                            <option value="">Any</option>
                            @foreach(['PENDING','APPROVED','REJECTED'] as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                        <select name="format" class="bg-obsidian-800 border border-white/10 rounded px-2 py-1">
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                        <button class="chip text-2xs" type="submit">Export batch manifest</button>
                    </form>
                </div>
                @php
                    $handoffs = \App\Models\BranchHandoff::where(function($q) use ($branch) {
                            $q->where('dest_branch_id', $branch->id)->orWhere('origin_branch_id', $branch->id);
                        })
                        ->whereIn('status', ['PENDING','APPROVED'])
                        ->with(['shipment', 'originBranch', 'destBranch'])
                        ->latest()
                        ->limit(6)
                        ->get();
                @endphp
                @forelse($handoffs as $handoff)
                    <div class="border border-white/5 rounded-lg p-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Shipment {{ $handoff->shipment?->tracking_number }}</span>
                            <span class="chip text-2xs">{{ $handoff->status }}</span>
                        </div>
                        <p class="muted text-2xs">
                            {{ $handoff->originBranch?->code }} → {{ $handoff->destBranch?->code }}
                            @if($handoff->expected_hand_off_at)
                                · ETA {{ $handoff->expected_hand_off_at->shortRelativeDiffForHumans() }}
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            @if($handoff->status === 'PENDING' && $handoff->dest_branch_id === $branch->id)
                                <form method="POST" action="{{ route('branch.operations.handoff.approve', $handoff) }}" class="flex items-center gap-2">
                                    @csrf
                                    <button class="chip text-2xs">Approve</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('branch.operations.handoff.complete', $handoff) }}">
                                @csrf
                                <button class="chip text-2xs">Complete</button>
                            </form>
                            <a class="chip text-2xs" href="{{ route('branch.operations.handoff.manifest', [$handoff, 'format' => 'pdf']) }}">Manifest PDF</a>
                            <a class="chip text-2xs" href="{{ route('branch.operations.handoff.manifest', [$handoff, 'format' => 'csv']) }}">Manifest CSV</a>
                        </div>
                    </div>
                @empty
                    <p class="muted text-sm">No pending handoffs.</p>
                @endforelse
                <div class="border border-white/10 rounded-lg p-3 space-y-2">
                    <div class="text-sm font-semibold">Request handoff</div>
                    <form method="POST" action="{{ route('branch.operations.handoff.request') }}" class="space-y-2 text-2xs">
                        @csrf
                        <input type="number" name="shipment_id" placeholder="Shipment ID" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <select name="dest_branch_id" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                            @foreach($branchOptions as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->code ?? $opt->name }}</option>
                            @endforeach
                        </select>
                        <input type="datetime-local" name="expected_hand_off_at" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <input type="text" name="notes" placeholder="Notes" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <button class="chip w-full justify-center">Request</button>
                    </form>
                </div>
            </div>

            <div class="glass-panel p-4 space-y-3">
                <div class="text-sm font-semibold">Batch manifest / export</div>
                <form method="GET" action="{{ route('branch.operations.manifest.shipments') }}" class="space-y-2 text-xs">
                    <div class="flex gap-2">
                        <select name="direction" class="bg-obsidian-800 border border-white/10 rounded px-2 py-1 w-1/2">
                            <option value="all">All</option>
                            <option value="outbound">Outbound</option>
                            <option value="inbound">Inbound</option>
                        </select>
                        <select name="format" class="bg-obsidian-800 border border-white/10 rounded px-2 py-1 w-1/2">
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <input type="text" name="ids" placeholder="IDs (optional, comma separated)" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                    <select name="status" class="w-full bg-obsidian-800 border border-white/10 rounded px-2 py-1">
                        <option value="">Any status</option>
                        @foreach(\App\Enums\ShipmentStatus::orderedLifecycle() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <button class="chip w-full justify-center">Export manifest</button>
                </form>
            </div>

            <div class="glass-panel p-4 space-y-3">
                <div class="text-sm font-semibold">Route / bag manifest</div>
                <form method="GET" action="{{ route('branch.operations.manifest.route') }}" class="space-y-2 text-xs">
                    <select name="status" class="w-full bg-obsidian-800 border border-white/10 rounded px-2 py-1">
                        <option value="">Default (OFD/Linehaul)</option>
                        @foreach(\App\Enums\ShipmentStatus::orderedLifecycle() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <select name="format" class="w-full bg-obsidian-800 border border-white/10 rounded px-2 py-1">
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                    <button class="chip w-full justify-center">Export route manifest</button>
                </form>
            </div>

            <div class="glass-panel p-4 space-y-3">
                <div class="text-sm font-semibold">Scanner mode</div>
                <p class="muted text-xs">Validate tracking for this branch; misroutes and duplicates are blocked.</p>
                <form method="POST" action="{{ route('branch.operations.scan') }}" class="space-y-2 text-sm">
                    @csrf
                    <input type="text" name="tracking_number" placeholder="Scan or type tracking number" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <select name="mode" class="w-full bg-obsidian-700 border border-white/10 rounded px-2 py-2 text-xs">
                        <option value="route">Route / OFD</option>
                        <option value="delivery">Delivery</option>
                        <option value="bag">Bag</option>
                        <option value="load">Load</option>
                        <option value="unload">Unload</option>
                        <option value="returns">Returns</option>
                    </select>
                    <button type="submit" class="chip w-full justify-center">Validate</button>
                </form>
            </div>

            <div class="glass-panel p-4 space-y-3">
                    <div class="text-sm font-semibold">Maintenance windows</div>
                @forelse($maintenance as $item)
                    <div class="border border-white/5 rounded-lg p-3">
                        <div class="font-semibold text-sm">{{ $item->title }}</div>
                        <p class="muted text-xs">{{ $item->message }}</p>
                        @if(isset($item->context['starts_at']) || isset($item->context['ends_at']))
                            <p class="text-2xs text-amber-200">
                                {{ $item->context['starts_at'] ?? 'Start' }} → {{ $item->context['ends_at'] ?? 'End' }}
                            </p>
                        @endif
                        @if(isset($item->context['capacity_factor']))
                            <p class="text-2xs text-amber-200">Capacity {{ $item->context['capacity_factor'] }}%</p>
                        @endif
                    </div>
                @empty
                    <p class="muted text-sm">No scheduled maintenance.</p>
                @endforelse
                <form method="POST" action="{{ route('branch.operations.maintenance') }}" class="space-y-2 text-sm">
                    @csrf
                    <input type="text" name="title" placeholder="Title" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <textarea name="message" placeholder="Notes" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2"></textarea>
                    <div class="flex gap-2">
                        <input type="datetime-local" name="window_starts_at" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 w-1/2">
                        <input type="datetime-local" name="window_ends_at" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 w-1/2">
                    </div>
                    <input type="number" name="capacity_factor" placeholder="Capacity % (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <button type="submit" class="chip w-full justify-center">Schedule maintenance</button>
                </form>
            </div>
        </div>
    </div>
@endsection
