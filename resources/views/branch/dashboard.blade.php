@extends('branch.layout')

@section('title', 'Branch Dashboard')
@section('sla_rate', $onTimeRate . '%')

@section('content')
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Inbound Queue', 'value' => $stats['inbound_queue'], 'hint' => 'En route to branch'],
                ['label' => 'Outbound Queue', 'value' => $stats['outbound_queue'], 'hint' => 'Awaiting dispatch'],
                ['label' => 'Throughput (24h)', 'value' => $stats['throughput_24h'], 'hint' => 'Origin last 24h'],
                ['label' => 'Capacity Utilization', 'value' => $stats['capacity_utilization'].'%', 'hint' => 'Planned vs. live'],
                ['label' => 'Active Workforce', 'value' => $stats['active_workers'], 'hint' => 'On shift'],
                ['label' => 'Active Clients', 'value' => $stats['active_clients'], 'hint' => 'Primary accounts'],
                ['label' => 'Exceptions', 'value' => $stats['exceptions'], 'hint' => 'Flagged shipments'],
                ['label' => 'On-time %', 'value' => $onTimeRate.'%', 'hint' => 'SLA breach: '.$slaBreaches],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="stat-card">
                <div class="muted text-xs uppercase">{{ $card['label'] }}</div>
                <div class="text-2xl font-bold">{{ $card['value'] }}</div>
                <div class="muted text-2xs">{{ $card['hint'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 space-y-3 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold">Operational posture</div>
                    <div class="muted text-xs">Queues, SLA outlook, and action shortcuts</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('branch.operations') }}" class="chip">Open Ops Board</a>
                    <a href="{{ route('branch.workforce') }}" class="chip">Dispatch workforce</a>
                    <a href="{{ route('branch.finance') }}" class="chip">Reconcile finance</a>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div class="glass-panel p-4 border border-emerald-500/30">
                    <div class="flex items-center justify-between mb-2">
                        <span class="muted text-xs">On-time delivery</span>
                        <span class="badge {{ $onTimeRate >= 95 ? 'badge-success' : 'badge-warn' }}">{{ $onTimeRate }}%</span>
                    </div>
                    <p class="text-sm">SLA breaches: {{ $slaBreaches }} | Finance today: {{ number_format($financeToday, 2) }} UGX</p>
                </div>
                <div class="glass-panel p-4 border border-amber-500/30">
                    <div class="flex items-center justify-between mb-2">
                        <span class="muted text-xs">Risk watch</span>
                        <span class="badge badge-warn">{{ $stats['exceptions'] }} open</span>
                    </div>
                    <p class="text-sm">Monitor flagged shipments and assign owners before SLA drifts.</p>
                </div>
            </div>
            <div class="grid lg:grid-cols-3 gap-3">
                <a href="{{ route('branch.operations') }}" class="glass-panel p-4 hover:border-white/30 transition">
                    <div class="text-sm font-semibold mb-1">Queues & Dispatch</div>
                    <p class="muted text-xs">Assign shipments, reprioritize, escalate.</p>
                </a>
                <a href="{{ route('branch.warehouse') }}" class="glass-panel p-4 hover:border-white/30 transition">
                    <div class="text-sm font-semibold mb-1">Warehouse</div>
                    <p class="muted text-xs">Stock, locations, and movement alerts.</p>
                </a>
                <a href="{{ route('branch.fleet') }}" class="glass-panel p-4 hover:border-white/30 transition">
                    <div class="text-sm font-semibold mb-1">Fleet board</div>
                    <p class="muted text-xs">Vehicle health, rosters, downtime.</p>
                </a>
            </div>
        </div>
        <div class="glass-panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Branch manager</div>
                    <div class="muted text-xs">Escalation contact</div>
                </div>
                <a href="{{ route('branch.workforce') }}" class="chip text-2xs">Manage</a>
            </div>
            @if($branch->branchManager && $branch->branchManager->user)
                <div class="space-y-1">
                    <div class="font-semibold">{{ $branch->branchManager->user->name }}</div>
                    <div class="muted text-sm">{{ $branch->branchManager->user->email }}</div>
                    <div class="muted text-sm">Phone: {{ $branch->branchManager->user->mobile ?? $branch->branchManager->user->phone_e164 }}</div>
                </div>
            @else
                <p class="muted text-sm">No manager assigned</p>
            @endif
            <div class="glass-panel px-3 py-2 border border-amber-500/30 text-amber-100 text-xs">
                Keep manager and dispatcher on-call details fresh for SLA escalations.
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass-panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Recent outbound</div>
                <span class="muted text-2xs">Latest 5</span>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($outboundList as $item)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">#{{ $item->id }} • {{ $item->current_status }}</div>
                            <div class="muted text-xs">Dest branch: {{ $item->dest_branch_id }}</div>
                        </div>
                        <div class="muted text-xs">{{ optional($item->created_at)->shortRelativeDiffForHumans() }}</div>
                    </div>
                @empty
                    <p class="muted text-sm">No outbound shipments.</p>
                @endforelse
            </div>
        </div>
        <div class="glass-panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Recent inbound</div>
                <span class="muted text-2xs">Latest 5</span>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($inboundList as $item)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">#{{ $item->id }} • {{ $item->current_status }}</div>
                            <div class="muted text-xs">Origin branch: {{ $item->origin_branch_id }}</div>
                        </div>
                        <div class="muted text-xs">{{ optional($item->created_at)->shortRelativeDiffForHumans() }}</div>
                    </div>
                @empty
                    <p class="muted text-sm">No inbound shipments.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Recent exceptions</div>
                <span class="muted text-2xs">Latest 5</span>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($exceptionsList as $item)
                    <div class="py-3 flex items-center justify-between text-amber-100">
                        <div>
                            <div class="font-semibold text-sm">#{{ $item->id }} • {{ $item->current_status }}</div>
                            <div class="muted text-xs">Dest branch: {{ $item->dest_branch_id }}</div>
                        </div>
                        <div class="muted text-xs">{{ optional($item->updated_at)->shortRelativeDiffForHumans() }}</div>
                    </div>
                @empty
                    <p class="muted text-sm">No active exceptions.</p>
                @endforelse
            </div>
        </div>
        <div class="glass-panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Workforce on shift</div>
                <a href="{{ route('branch.workforce') }}" class="chip text-2xs">Open</a>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($workforce as $worker)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $worker->user?->name }}</div>
                            <div class="muted text-2xs">{{ $worker->role?->label() ?? $worker->role?->value }}</div>
                        </div>
                        <div class="chip text-2xs">{{ $worker->employment_status?->label() ?? $worker->employment_status }}</div>
                    </div>
                @empty
                    <p class="muted text-sm">No active workers.</p>
                @endforelse
            </div>
        </div>
        <div class="glass-panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Clients</div>
                <a href="{{ route('branch.clients') }}" class="chip text-2xs">Manage</a>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($clients as $client)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $client->business_name }}</div>
                            <div class="muted text-2xs">Status: {{ $client->status }}</div>
                        </div>
                        <div class="muted text-2xs">{{ optional($client->created_at)->shortRelativeDiffForHumans() }}</div>
                    </div>
                @empty
                    <p class="muted text-sm">No clients yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
            <div class="glass-panel p-5">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-semibold">Finance snapshot</div>
                    <span class="pill-soft">Today</span>
                </div>
            <div class="text-2xl font-bold mb-1">{{ number_format($financeToday, 2) }} {{ $defaultCurrency ?? 'UGX' }}</div>
            <p class="muted text-xs">Shipments created today. Reconcile in Finance for full invoice status.</p>
        </div>
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-semibold">Warehouse</div>
                <a href="{{ route('branch.warehouse') }}" class="text-2xs muted hover:text-white">Open</a>
            </div>
            @forelse($warehouseAlerts as $w)
                <div class="flex justify-between muted text-sm border-b border-white/5 pb-2 mb-2">
                    <span>{{ $w->name }}</span>
                    <span>{{ $w->status ?? 'n/a' }}</span>
                </div>
            @empty
                <div class="muted text-sm">No warehouse records.</div>
            @endforelse
        </div>
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-semibold">Fleet</div>
                <a href="{{ route('branch.fleet') }}" class="text-2xs muted hover:text-white">Open</a>
            </div>
            @forelse($fleetItems as $v)
                <div class="flex justify-between muted text-sm border-b border-white/5 pb-2 mb-2">
                    <span>{{ $v->registration ?? ('Vehicle '.$v->id) }}</span>
                    <span>{{ $v->status ?? 'n/a' }}</span>
                </div>
            @empty
                <div class="muted text-sm">No fleet records.</div>
            @endforelse
        </div>
    </div>

    {{-- DHL-Grade Performance Metrics --}}
    <div class="grid gap-4 lg:grid-cols-4">
        <div class="stat-card border-l-2 border-emerald-500">
            <div class="muted text-xs uppercase">Avg Delivery Time</div>
            <div class="text-2xl font-bold text-emerald-400">{{ $avgDeliveryTime ?? 0 }}h</div>
            <div class="muted text-2xs">Hours from pickup</div>
        </div>
        <div class="stat-card border-l-2 border-purple-500">
            <div class="muted text-xs uppercase">First Attempt Rate</div>
            <div class="text-2xl font-bold text-purple-400">{{ $firstAttemptRate ?? 0 }}%</div>
            <div class="muted text-2xs">Delivered on 1st try</div>
        </div>
        <div class="stat-card border-l-2 border-sky-500">
            <div class="muted text-xs uppercase">Scans Today</div>
            <div class="text-2xl font-bold text-sky-400">{{ $scanActivity['total'] ?? 0 }}</div>
            <div class="muted text-2xs">Total scan events</div>
        </div>
        <div class="stat-card border-l-2 border-amber-500">
            <div class="muted text-xs uppercase">Avg per Worker</div>
            <div class="text-2xl font-bold text-amber-400">{{ $driverPerformance['avg_per_worker'] ?? 0 }}</div>
            <div class="muted text-2xs">Deliveries (7 days)</div>
        </div>
    </div>

    {{-- COD & Additional Metrics --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-semibold">COD Collections</div>
                <span class="pill-soft">Today</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="muted text-xs uppercase">Collected Today</div>
                    <div class="text-xl font-bold text-emerald-400">{{ number_format($codMetrics['collected_today'] ?? 0) }} {{ $defaultCurrency ?? 'UGX' }}</div>
                </div>
                <div>
                    <div class="muted text-xs uppercase">Pending COD</div>
                    <div class="text-xl font-bold text-amber-400">{{ number_format($codMetrics['pending'] ?? 0) }} {{ $defaultCurrency ?? 'UGX' }}</div>
                </div>
            </div>
        </div>
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-semibold">Shipment Trends</div>
                <span class="muted text-2xs">Last 7 days</span>
            </div>
            <div class="h-32">
                <canvas id="branchTrendsChart"></canvas>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('branchTrendsChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyTrends['labels'] ?? []) !!},
                datasets: [{
                    label: 'Created',
                    data: {!! json_encode($dailyTrends['created'] ?? []) !!},
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    tension: 0.4,
                    fill: true,
                }, {
                    label: 'Delivered',
                    data: {!! json_encode($dailyTrends['delivered'] ?? []) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 10 } } }
                }
            }
        });
    }
});
</script>
@endpush
