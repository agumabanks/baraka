@extends('branch.layout')

@section('title', 'Tracking Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Real-Time Tracking Dashboard</h1>
            <p class="text-sm muted">Monitor active shipments and delivery progress</p>
        </div>
        <div class="flex items-center gap-2">
            <div id="liveIndicator" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-xs muted">Live updates</span>
            <button onclick="refreshTracking()" class="btn btn-ghost btn-sm ml-4">
                <svg class="w-4 h-4" id="refreshIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="stat-card border-l-4 border-sky-500">
            <div class="muted text-xs uppercase">In Transit</div>
            <div class="text-2xl font-bold text-sky-400">{{ $stats['in_transit'] ?? 0 }}</div>
        </div>
        <div class="stat-card border-l-4 border-purple-500">
            <div class="muted text-xs uppercase">Out for Delivery</div>
            <div class="text-2xl font-bold text-purple-400">{{ $stats['out_for_delivery'] ?? 0 }}</div>
        </div>
        <div class="stat-card border-l-4 border-red-500">
            <div class="muted text-xs uppercase">Delayed</div>
            <div class="text-2xl font-bold text-red-400">{{ $stats['delayed'] ?? 0 }}</div>
        </div>
        <div class="stat-card border-l-4 border-emerald-500">
            <div class="muted text-xs uppercase">On Time</div>
            <div class="text-2xl font-bold text-emerald-400">{{ $stats['on_time'] ?? 0 }}</div>
        </div>
        <div class="stat-card border-l-4 border-amber-500">
            <div class="muted text-xs uppercase">Avg Transit</div>
            <div class="text-2xl font-bold text-amber-400">{{ $stats['avg_transit_hours'] ?? 0 }}h</div>
        </div>
    </div>

    {{-- Quick Track --}}
    <div class="glass-panel p-4">
        <form id="quickTrackForm" class="flex gap-4">
            <div class="flex-1">
                <input type="text" 
                       id="trackingInput" 
                       class="input w-full" 
                       placeholder="Enter tracking number(s) - comma separated for multiple..."
                       autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Track</button>
        </form>
    </div>

    {{-- Quick Track Results --}}
    <div id="quickTrackResults" class="hidden glass-panel p-4 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold">Tracking Results</h3>
            <button onclick="clearResults()" class="text-xs muted hover:text-white">&times; Clear</button>
        </div>
        <div id="trackingResults"></div>
    </div>

    {{-- Active Shipments Grid --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Out for Delivery --}}
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-purple-400">Out for Delivery</h3>
                <span class="chip text-xs">{{ count($outForDelivery ?? []) }}</span>
            </div>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                @forelse($outForDelivery ?? [] as $shipment)
                    <div class="glass-panel p-3 border border-purple-500/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono font-semibold">#{{ $shipment->tracking_number }}</span>
                            <span class="text-xs muted">{{ $shipment->out_for_delivery_at?->diffForHumans() ?? 'Just now' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-xs muted">{{ $shipment->destBranch?->name ?? 'Unknown' }}</div>
                            <div class="text-xs">
                                @if($shipment->assignedWorker)
                                    <span class="text-emerald-400">{{ $shipment->assignedWorker->user?->name }}</span>
                                @else
                                    <span class="text-amber-400">Unassigned</span>
                                @endif
                            </div>
                        </div>
                        @if($shipment->expected_delivery_date)
                            <div class="mt-2 text-xs">
                                @php
                                    $isLate = $shipment->expected_delivery_date < now();
                                @endphp
                                <span class="{{ $isLate ? 'text-red-400' : 'text-emerald-400' }}">
                                    ETA: {{ $shipment->expected_delivery_date->format('H:i') }}
                                    @if($isLate) (Overdue) @endif
                                </span>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm muted text-center py-4">No shipments out for delivery</p>
                @endforelse
            </div>
        </div>

        {{-- In Transit --}}
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-sky-400">In Transit</h3>
                <span class="chip text-xs">{{ count($inTransit ?? []) }}</span>
            </div>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                @forelse($inTransit ?? [] as $shipment)
                    <div class="glass-panel p-3 border border-sky-500/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono font-semibold">#{{ $shipment->tracking_number }}</span>
                            <span class="badge {{ $shipment->current_status === 'LINEHAUL_DEPARTED' ? 'badge-info' : 'badge-success' }}">
                                {{ str_replace('_', ' ', $shipment->current_status) }}
                            </span>
                        </div>
                        <div class="text-xs muted">
                            {{ $shipment->originBranch?->code }} → {{ $shipment->destBranch?->code }}
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-white/10 rounded-full h-1.5">
                                @php
                                    $progress = match($shipment->current_status) {
                                        'BOOKED', 'CREATED' => 10,
                                        'PICKED_UP' => 25,
                                        'AT_ORIGIN_HUB' => 40,
                                        'LINEHAUL_DEPARTED' => 55,
                                        'LINEHAUL_ARRIVED' => 70,
                                        'AT_DESTINATION_HUB' => 80,
                                        'OUT_FOR_DELIVERY' => 90,
                                        'DELIVERED' => 100,
                                        default => 50,
                                    };
                                @endphp
                                <div class="bg-sky-500 h-1.5 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm muted text-center py-4">No shipments in transit</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Delayed / At Risk --}}
    @if(count($delayed ?? []) > 0)
    <div class="glass-panel p-5 border border-red-500/30">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-red-400">Delayed / At Risk</h3>
            <span class="badge badge-danger">{{ count($delayed) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left p-2 text-xs uppercase muted">Tracking</th>
                        <th class="text-left p-2 text-xs uppercase muted">Route</th>
                        <th class="text-left p-2 text-xs uppercase muted">Status</th>
                        <th class="text-left p-2 text-xs uppercase muted">Expected</th>
                        <th class="text-left p-2 text-xs uppercase muted">Delay</th>
                        <th class="text-right p-2 text-xs uppercase muted">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($delayed as $shipment)
                        <tr class="hover:bg-white/5">
                            <td class="p-2 font-mono">#{{ $shipment->tracking_number }}</td>
                            <td class="p-2 text-sm">{{ $shipment->originBranch?->code }} → {{ $shipment->destBranch?->code }}</td>
                            <td class="p-2">
                                <span class="chip text-xs">{{ str_replace('_', ' ', $shipment->current_status) }}</span>
                            </td>
                            <td class="p-2 text-sm">{{ $shipment->expected_delivery_date?->format('M d, H:i') }}</td>
                            <td class="p-2 text-sm text-red-400">
                                {{ $shipment->expected_delivery_date?->diffForHumans() }}
                            </td>
                            <td class="p-2 text-right">
                                <a href="{{ route('branch.shipments.show', $shipment) }}" class="chip text-xs">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Recent Deliveries --}}
    <div class="glass-panel p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-emerald-400">Recent Deliveries (Today)</h3>
            <span class="chip text-xs">{{ count($recentDeliveries ?? []) }}</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($recentDeliveries ?? [] as $shipment)
                <div class="glass-panel p-3 border border-emerald-500/30">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-mono text-sm">#{{ $shipment->tracking_number }}</span>
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="text-xs muted">{{ $shipment->delivered_at?->format('H:i') }}</div>
                </div>
            @empty
                <p class="text-sm muted col-span-full text-center py-4">No deliveries today yet</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('quickTrackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('trackingInput').value.trim();
    if (!input) return;
    
    const trackingNumbers = input.split(',').map(t => t.trim()).filter(t => t);
    trackShipments(trackingNumbers);
});

async function trackShipments(trackingNumbers) {
    const resultsContainer = document.getElementById('trackingResults');
    const resultsPanel = document.getElementById('quickTrackResults');
    
    resultsPanel.classList.remove('hidden');
    resultsContainer.innerHTML = '<div class="text-center py-4"><div class="animate-spin w-6 h-6 border-2 border-sky-500 border-t-transparent rounded-full mx-auto"></div></div>';
    
    try {
        const response = await fetch('/branch/tracking/quick', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ tracking_numbers: trackingNumbers }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            renderResults(data.shipments);
        } else {
            resultsContainer.innerHTML = '<p class="text-red-400">Failed to track shipments</p>';
        }
    } catch (error) {
        resultsContainer.innerHTML = '<p class="text-red-400">Error: ' + error.message + '</p>';
    }
}

function renderResults(shipments) {
    const container = document.getElementById('trackingResults');
    
    if (!shipments || shipments.length === 0) {
        container.innerHTML = '<p class="text-amber-400">No shipments found</p>';
        return;
    }
    
    let html = '<div class="space-y-4">';
    
    shipments.forEach(s => {
        const progress = getProgress(s.current_status);
        const statusColor = getStatusColor(s.current_status);
        
        html += `
            <div class="glass-panel p-4 border ${statusColor}">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-mono font-semibold">#${s.tracking_number}</span>
                    <span class="chip text-xs">${s.current_status?.replace(/_/g, ' ') || 'Unknown'}</span>
                </div>
                <div class="mb-3">
                    <div class="w-full bg-white/10 rounded-full h-2">
                        <div class="bg-sky-500 h-2 rounded-full transition-all" style="width: ${progress}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><span class="muted">Origin:</span> ${s.origin_branch || 'N/A'}</div>
                    <div><span class="muted">Destination:</span> ${s.dest_branch || 'N/A'}</div>
                    <div><span class="muted">Created:</span> ${s.created_at || 'N/A'}</div>
                    <div><span class="muted">ETA:</span> ${s.expected_delivery || 'N/A'}</div>
                </div>
                <div class="mt-3">
                    <a href="/branch/shipments/${s.id}" class="chip text-xs">View Details</a>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function getProgress(status) {
    const progressMap = {
        'BOOKED': 10, 'CREATED': 10,
        'PICKUP_SCHEDULED': 20,
        'PICKED_UP': 30,
        'AT_ORIGIN_HUB': 40,
        'BAGGED': 45,
        'LINEHAUL_DEPARTED': 55,
        'LINEHAUL_ARRIVED': 70,
        'AT_DESTINATION_HUB': 80,
        'OUT_FOR_DELIVERY': 90,
        'DELIVERED': 100,
    };
    return progressMap[status] || 50;
}

function getStatusColor(status) {
    if (status === 'DELIVERED') return 'border-emerald-500/30';
    if (status === 'OUT_FOR_DELIVERY') return 'border-purple-500/30';
    if (['LINEHAUL_DEPARTED', 'LINEHAUL_ARRIVED', 'IN_TRANSIT'].includes(status)) return 'border-sky-500/30';
    return 'border-white/10';
}

function clearResults() {
    document.getElementById('quickTrackResults').classList.add('hidden');
    document.getElementById('trackingInput').value = '';
}

function refreshTracking() {
    const icon = document.getElementById('refreshIcon');
    icon.classList.add('animate-spin');
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Auto-refresh every 60 seconds
setInterval(() => {
    window.location.reload();
}, 60000);
</script>
@endpush
@endsection
