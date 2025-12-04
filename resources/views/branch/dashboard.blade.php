@extends('branch.layout')

@section('title', 'Branch Dashboard')
@section('sla_rate', $onTimeRate . '%')

@section('content')
    {{-- Auto-refresh indicator --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div id="refreshIndicator" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Live updates active"></div>
            <span class="text-xs muted">Auto-refresh: <span id="refreshCountdown">60</span>s</span>
        </div>
        <button onclick="refreshDashboard()" class="chip text-xs flex items-center gap-1">
            <svg class="w-3 h-3" id="refreshIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh Now
        </button>
    </div>

    {{-- Priority Alerts Banner --}}
    @if(count($priorityAlerts) > 0)
    <div class="mb-4 space-y-2" id="priorityAlertsContainer">
        @foreach($priorityAlerts as $alert)
        <div class="glass-panel p-3 flex items-center justify-between gap-4 border-l-4 
            @if($alert['severity'] === 'critical') border-red-500 bg-red-500/10
            @elseif($alert['severity'] === 'high') border-orange-500 bg-orange-500/10
            @elseif($alert['severity'] === 'medium') border-amber-500 bg-amber-500/10
            @else border-sky-500 bg-sky-500/10 @endif">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    @if($alert['severity'] === 'critical')
                    <svg class="w-5 h-5 text-red-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    @elseif($alert['severity'] === 'high')
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                    </svg>
                    @elseif($alert['severity'] === 'medium')
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @else
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @endif
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase
                            @if($alert['severity'] === 'critical') text-red-400
                            @elseif($alert['severity'] === 'high') text-orange-400
                            @elseif($alert['severity'] === 'medium') text-amber-400
                            @else text-sky-400 @endif">{{ $alert['severity'] }}</span>
                        <span class="font-semibold text-sm">{{ $alert['title'] }}</span>
                    </div>
                    <p class="text-xs muted">{{ $alert['message'] }}</p>
                </div>
            </div>
            <a href="{{ route($alert['action_route']) }}" class="chip text-xs flex-shrink-0">{{ $alert['action_label'] }}</a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- SLA At-Risk Countdown Widget --}}
    @if(count($slaAtRisk) > 0)
    <div class="glass-panel p-4 mb-4 border border-red-500/30">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold text-sm text-red-400">SLA At Risk - {{ count($slaAtRisk) }} Shipment(s)</span>
            </div>
            <a href="{{ route('branch.operations') }}" class="chip text-xs">View All</a>
        </div>
        <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-3" id="slaCountdownContainer">
            @foreach(array_slice($slaAtRisk, 0, 6) as $shipment)
            <div class="glass-panel p-3 border
                @if($shipment['severity'] === 'critical') border-red-500/50 bg-red-500/5
                @elseif($shipment['severity'] === 'high') border-orange-500/50 bg-orange-500/5
                @elseif($shipment['severity'] === 'medium') border-amber-500/50 bg-amber-500/5
                @else border-white/10 @endif"
                data-deadline="{{ $shipment['deadline'] }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-mono text-sm font-semibold">#{{ $shipment['tracking_number'] ?? $shipment['id'] }}</span>
                    @if($shipment['is_breached'])
                    <span class="badge badge-danger text-xs animate-pulse">BREACHED</span>
                    @else
                    <span class="countdown-timer font-mono text-sm font-bold
                        @if($shipment['severity'] === 'critical') text-red-400
                        @elseif($shipment['severity'] === 'high') text-orange-400
                        @elseif($shipment['severity'] === 'medium') text-amber-400
                        @else text-emerald-400 @endif"
                        data-deadline="{{ $shipment['deadline'] }}">
                        {{ $shipment['hours_remaining'] }}h {{ $shipment['minutes_remaining'] % 60 }}m
                    </span>
                    @endif
                </div>
                <div class="text-xs muted">
                    <div>{{ $shipment['status'] }}</div>
                    <div>To: {{ $shipment['destination'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

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
                    <a href="{{ route('branch.finance.index') }}" class="chip">Reconcile finance</a>
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
                <a href="{{ route('branch.warehouse.index') }}" class="glass-panel p-4 hover:border-white/30 transition">
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
                <a href="{{ route('branch.clients.index') }}" class="chip text-2xs">Manage</a>
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
                <a href="{{ route('branch.warehouse.index') }}" class="text-2xs muted hover:text-white">Open</a>
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
    // Initialize Chart
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
    
    // Initialize SLA Countdown Timers
    initCountdownTimers();
    
    // Start auto-refresh countdown
    startRefreshCountdown();
});

// SLA Countdown Timer functionality
function initCountdownTimers() {
    const timers = document.querySelectorAll('.countdown-timer[data-deadline]');
    
    timers.forEach(timer => {
        updateCountdown(timer);
    });
    
    // Update every second
    setInterval(() => {
        timers.forEach(timer => updateCountdown(timer));
    }, 1000);
}

function updateCountdown(element) {
    const deadline = new Date(element.dataset.deadline);
    const now = new Date();
    const diff = deadline - now;
    
    if (diff <= 0) {
        element.textContent = 'BREACHED';
        element.classList.remove('text-amber-400', 'text-orange-400', 'text-emerald-400');
        element.classList.add('text-red-400', 'animate-pulse');
        return;
    }
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    if (hours > 0) {
        element.textContent = `${hours}h ${minutes}m`;
    } else if (minutes > 0) {
        element.textContent = `${minutes}m ${seconds}s`;
    } else {
        element.textContent = `${seconds}s`;
    }
    
    // Update color based on urgency
    element.classList.remove('text-emerald-400', 'text-amber-400', 'text-orange-400', 'text-red-400');
    if (hours < 2) {
        element.classList.add('text-red-400');
    } else if (hours < 6) {
        element.classList.add('text-orange-400');
    } else if (hours < 12) {
        element.classList.add('text-amber-400');
    } else {
        element.classList.add('text-emerald-400');
    }
}

// Auto-refresh functionality
let refreshInterval = 60;
let refreshCountdown = refreshInterval;
let refreshTimer;

function startRefreshCountdown() {
    const countdownEl = document.getElementById('refreshCountdown');
    
    refreshTimer = setInterval(() => {
        refreshCountdown--;
        if (countdownEl) countdownEl.textContent = refreshCountdown;
        
        if (refreshCountdown <= 0) {
            refreshDashboard();
        }
    }, 1000);
}

function refreshDashboard() {
    const icon = document.getElementById('refreshIcon');
    const indicator = document.getElementById('refreshIndicator');
    
    // Add spinning animation
    if (icon) icon.classList.add('animate-spin');
    if (indicator) {
        indicator.classList.remove('bg-emerald-500');
        indicator.classList.add('bg-amber-500');
    }
    
    // Reload the page
    window.location.reload();
}

// Skeleton loader for AJAX refresh (future enhancement)
function showLoadingState() {
    const containers = document.querySelectorAll('.stat-card, .glass-panel');
    containers.forEach(container => {
        container.classList.add('animate-pulse', 'opacity-70');
    });
}

function hideLoadingState() {
    const containers = document.querySelectorAll('.stat-card, .glass-panel');
    containers.forEach(container => {
        container.classList.remove('animate-pulse', 'opacity-70');
    });
}

// Audio alert for critical SLA breaches (optional - user can enable)
function playAlertSound() {
    // Create a simple beep using Web Audio API
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.1;
        
        oscillator.start();
        setTimeout(() => oscillator.stop(), 200);
    } catch (e) {
        // Audio not available
    }
}
</script>
@endpush
