@extends('admin.layout')

@section('title', 'Live Tracking Dashboard')
@section('header', 'Real-Time Shipment Tracking')

@push('styles')
<style>
    .tracking-map { height: 500px; width: 100%; border-radius: 12px; }
    .pulse-live { animation: pulse-live 2s infinite; }
    @keyframes pulse-live {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { opacity: 0.8; box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    }
    .shipment-card { transition: all 0.2s ease; }
    .shipment-card:hover { transform: translateX(4px); background: rgba(255,255,255,0.05); }
    .shipment-card.selected { background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; }
    .progress-track { height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; }
    .filter-chip { padding: 6px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s; }
    .filter-chip:hover { background: rgba(255,255,255,0.1); }
    .filter-chip.active { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3); }
    .metric-ring { width: 80px; height: 80px; border-radius: 50%; position: relative; }
    .metric-ring svg { transform: rotate(-90deg); }
    .metric-ring-value { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Header with Live Indicator --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-emerald-500 pulse-live"></div>
            <div>
                <h2 class="text-xl font-bold">Live Tracking</h2>
                <p class="text-sm text-zinc-400">Real-time shipment monitoring • Auto-refresh every 30s</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-sm text-zinc-500" id="lastUpdate">Updated just now</div>
            <button onclick="refreshDashboard()" class="btn btn-sm btn-secondary" id="refreshBtn">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-sm btn-primary">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                All Shipments
            </a>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid gap-4 grid-cols-2 lg:grid-cols-5">
        <div class="glass-panel p-4 border-l-4 border-sky-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">In Transit</div>
                    <div class="text-2xl font-bold text-sky-400 mt-1" id="statInTransit">{{ number_format($stats['in_transit']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-sky-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Out for Delivery</div>
                    <div class="text-2xl font-bold text-purple-400 mt-1" id="statOutForDelivery">{{ number_format($stats['out_for_delivery']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">On Time</div>
                    <div class="text-2xl font-bold text-emerald-400 mt-1" id="statOnTime">{{ number_format($stats['on_time']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Delayed</div>
                    <div class="text-2xl font-bold text-amber-400 mt-1" id="statDelayed">{{ number_format($stats['delayed']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-zinc-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Total Active</div>
                    <div class="text-2xl font-bold text-white mt-1" id="statTotal">{{ number_format($activeShipments->count()) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-zinc-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Map Section (2 cols) --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="glass-panel overflow-hidden">
                <div class="p-4 border-b border-white/10 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold">Live Map View</span>
                        <span class="px-2 py-0.5 text-xs bg-emerald-500/20 text-emerald-400 rounded-full">Real-time</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="fitAllMarkers()" class="p-2 hover:bg-white/10 rounded-lg transition" title="Fit All">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        </button>
                        <button onclick="toggleMapStyle()" class="p-2 hover:bg-white/10 rounded-lg transition" title="Toggle Style">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        </button>
                    </div>
                </div>
                <div id="trackingMap" class="tracking-map relative">
                    <div id="mapLoading" class="absolute inset-0 flex items-center justify-center bg-zinc-900 z-10">
                        <div class="text-center">
                            <div class="w-12 h-12 border-4 border-zinc-700 border-t-sky-500 rounded-full animate-spin mx-auto mb-4"></div>
                            <p class="text-zinc-400 text-sm">Loading map...</p>
                        </div>
                    </div>
                </div>
                {{-- Legend --}}
                <div class="p-3 border-t border-white/10 flex flex-wrap items-center gap-4 text-xs">
                    <div class="flex items-center gap-2"><span class="status-dot bg-sky-500"></span><span class="text-zinc-400">In Transit</span></div>
                    <div class="flex items-center gap-2"><span class="status-dot bg-purple-500"></span><span class="text-zinc-400">Out for Delivery</span></div>
                    <div class="flex items-center gap-2"><span class="status-dot bg-emerald-500"></span><span class="text-zinc-400">Delivered</span></div>
                    <div class="flex items-center gap-2"><span class="status-dot bg-amber-500"></span><span class="text-zinc-400">Delayed</span></div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 bg-zinc-600"></span><span class="text-zinc-400">Hub/Branch</span></div>
                </div>
            </div>

            {{-- Performance Metrics --}}
            <div class="glass-panel p-5">
                <h3 class="text-sm font-semibold mb-4">Delivery Performance</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="metric-ring mx-auto mb-2">
                            <svg viewBox="0 0 36 36" class="w-full h-full">
                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#10b981" stroke-width="3" stroke-dasharray="{{ $stats['on_time'] > 0 && ($stats['on_time'] + $stats['delayed']) > 0 ? round($stats['on_time'] / ($stats['on_time'] + $stats['delayed']) * 100) : 0 }}, 100"/>
                            </svg>
                            <div class="metric-ring-value text-emerald-400">{{ $stats['on_time'] > 0 && ($stats['on_time'] + $stats['delayed']) > 0 ? round($stats['on_time'] / ($stats['on_time'] + $stats['delayed']) * 100) : 0 }}%</div>
                        </div>
                        <div class="text-xs text-zinc-400">On-Time Rate</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ $stats['in_transit'] + $stats['out_for_delivery'] }}</div>
                        <div class="text-xs text-zinc-400 mt-1">Active Now</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-sky-400">{{ $stats['avg_transit_hours'] > 0 ? $stats['avg_transit_hours'] . 'h' : 'N/A' }}</div>
                        <div class="text-xs text-zinc-400 mt-1">Avg Transit</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400">{{ $stats['first_attempt_rate'] }}%</div>
                        <div class="text-xs text-zinc-400 mt-1">First Attempt</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Shipments List (1 col) --}}
        <div class="glass-panel flex flex-col" style="max-height: 750px;">
            <div class="p-4 border-b border-white/10">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold">Active Shipments</span>
                    <span class="px-2 py-0.5 text-xs bg-zinc-700 rounded-full" id="shipmentCount">{{ $activeShipments->count() }}</span>
                </div>
                {{-- Search --}}
                <div class="relative mb-3">
                    <input type="text" id="shipmentSearch" placeholder="Search tracking #..." 
                        class="w-full bg-white/5 border border-white/10 rounded-lg pl-9 pr-4 py-2 text-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                    <svg class="w-4 h-4 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                {{-- Status Filters --}}
                <div class="flex flex-wrap gap-2">
                    <button class="filter-chip border border-white/10 active" data-status="all">All</button>
                    <button class="filter-chip border border-white/10" data-status="in_transit">In Transit</button>
                    <button class="filter-chip border border-white/10" data-status="out_for_delivery">OFD</button>
                    <button class="filter-chip border border-white/10" data-status="delayed">Delayed</button>
                </div>
            </div>
            
            <div class="overflow-y-auto flex-1" id="shipmentsList">
                @forelse($activeShipments as $shipment)
                    @php
                        $statusColors = [
                            'in_transit' => ['bg' => 'bg-sky-500', 'text' => 'text-sky-400', 'badge' => 'bg-sky-500/20 text-sky-400'],
                            'out_for_delivery' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-400', 'badge' => 'bg-purple-500/20 text-purple-400'],
                            'delayed' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-400', 'badge' => 'bg-amber-500/20 text-amber-400'],
                            'delivered' => ['bg' => 'bg-emerald-500', 'text' => 'text-emerald-400', 'badge' => 'bg-emerald-500/20 text-emerald-400'],
                        ];
                        $colors = $statusColors[$shipment->status] ?? ['bg' => 'bg-zinc-500', 'text' => 'text-zinc-400', 'badge' => 'bg-zinc-500/20 text-zinc-400'];
                        $progress = match($shipment->status) {
                            'created' => 10, 'picked_up' => 25, 'processing' => 40,
                            'in_transit' => 60, 'out_for_delivery' => 85, 'delivered' => 100, default => 0
                        };
                        $isDelayed = $shipment->expected_delivery_date && $shipment->expected_delivery_date->isPast() && $shipment->status !== 'delivered';
                    @endphp
                    <div class="shipment-card p-4 border-b border-white/5 cursor-pointer" 
                         data-id="{{ $shipment->id }}" 
                         data-status="{{ $shipment->status }}"
                         data-tracking="{{ strtolower($shipment->tracking_number ?? '') }}"
                         onclick="selectShipment({{ $shipment->id }})">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <div class="font-mono text-sm font-semibold {{ $colors['text'] }}">
                                    {{ $shipment->tracking_number ?? 'TRK-' . str_pad($shipment->id, 6, '0', STR_PAD_LEFT) }}
                                </div>
                                <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-1">
                                    {{ Str::limit($shipment->originBranch->name ?? 'Origin', 10) }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    {{ Str::limit($shipment->destBranch->name ?? 'Dest', 10) }}
                                </div>
                            </div>
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $colors['badge'] }}">
                                @if($isDelayed)
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500 mr-1 animate-pulse"></span>
                                @endif
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </div>
                        
                        {{-- Progress --}}
                        <div class="progress-track mt-2">
                            <div class="progress-fill {{ $colors['bg'] }}" style="width: {{ $progress }}%"></div>
                        </div>
                        
                        {{-- ETA & Customer --}}
                        <div class="flex items-center justify-between mt-2 text-xs text-zinc-500">
                            <span>{{ $shipment->customer->contact_person ?? $shipment->customer->company_name ?? 'Customer' }}</span>
                            @if($shipment->expected_delivery_date)
                                <span class="{{ $isDelayed ? 'text-amber-400' : '' }}">
                                    ETA: {{ $shipment->expected_delivery_date->format('M d, H:i') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="text-zinc-500">No active shipments</p>
                        <a href="{{ route('admin.pos.index') }}" class="text-sky-400 text-sm hover:underline mt-2 inline-block">Create Shipment</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid gap-4 md:grid-cols-4">
        <a href="{{ route('admin.pos.index') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <div class="font-medium">New Shipment</div>
                    <div class="text-xs text-zinc-500">Create via POS</div>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.shipments.index', ['status' => 'delayed']) }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="font-medium">Exceptions</div>
                    <div class="text-xs text-zinc-500">{{ $stats['delayed'] }} need attention</div>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.shipments.index') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-sky-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <div class="font-medium">All Shipments</div>
                    <div class="text-xs text-zinc-500">Full management</div>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.analytics.dashboard') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <div class="font-medium">Analytics</div>
                    <div class="text-xs text-zinc-500">Performance insights</div>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
const REFRESH_INTERVAL = 30000;
let map, markers = {}, selectedId = null, refreshTimer;

// Shipment data
@php
$shipmentData = $activeShipments->map(function($s) {
    return [
        'id' => $s->id,
        'tracking' => $s->tracking_number,
        'status' => $s->status,
        'lat' => $s->originBranch?->latitude ?? 0.3476,
        'lng' => $s->originBranch?->longitude ?? 32.5825,
        'origin' => $s->originBranch?->name,
        'dest' => $s->destBranch?->name,
    ];
});
@endphp
const shipments = @json($shipmentData);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initFilters();
    initSearch();
    initMap();
    startAutoRefresh();
});

function initFilters() {
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filterShipments(chip.dataset.status);
        });
    });
}

function initSearch() {
    const search = document.getElementById('shipmentSearch');
    search.addEventListener('input', () => {
        const query = search.value.toLowerCase();
        document.querySelectorAll('.shipment-card').forEach(card => {
            const tracking = card.dataset.tracking || '';
            card.style.display = tracking.includes(query) ? '' : 'none';
        });
        updateCount();
    });
}

function filterShipments(status) {
    document.querySelectorAll('.shipment-card').forEach(card => {
        if (status === 'all') {
            card.style.display = '';
        } else {
            card.style.display = card.dataset.status === status ? '' : 'none';
        }
    });
    updateCount();
}

function updateCount() {
    const visible = document.querySelectorAll('.shipment-card:not([style*="display: none"])').length;
    document.getElementById('shipmentCount').textContent = visible;
}

function selectShipment(id) {
    document.querySelectorAll('.shipment-card').forEach(c => c.classList.remove('selected'));
    const card = document.querySelector(`.shipment-card[data-id="${id}"]`);
    if (card) card.classList.add('selected');
    selectedId = id;
    focusMarker(id);
}

function initMap() {
    @if(config('services.google_maps.api_key'))
    // Will be initialized by Google Maps callback
    @else
    // Leaflet fallback
    if (typeof L !== 'undefined') {
        map = L.map('trackingMap').setView([0.3476, 32.5825], 10);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        document.getElementById('mapLoading').style.display = 'none';
        addMarkers();
    } else {
        document.getElementById('mapLoading').innerHTML = `
            <div class="text-center p-8">
                <svg class="w-16 h-16 mx-auto mb-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                <p class="text-zinc-400 mb-2">Map requires configuration</p>
                <p class="text-xs text-zinc-500">Add maps API key to .env</p>
            </div>
        `;
    }
    @endif
}

function addMarkers() {
    if (!map) return;
    const bounds = [];
    
    shipments.forEach(s => {
        if (!s.lat || !s.lng) return;
        const color = getStatusColor(s.status);
        
        if (typeof L !== 'undefined') {
            const marker = L.circleMarker([s.lat, s.lng], {
                radius: 8, fillColor: color, color: '#fff', weight: 2, fillOpacity: 0.9
            }).addTo(map);
            marker.bindPopup(`<b>${s.tracking}</b><br>${s.origin} → ${s.dest}<br><small>${s.status}</small>`);
            markers[s.id] = marker;
            bounds.push([s.lat, s.lng]);
        }
    });
    
    if (bounds.length > 0 && typeof L !== 'undefined') {
        map.fitBounds(bounds, { padding: [30, 30] });
    }
}

function getStatusColor(status) {
    return { in_transit: '#3b82f6', out_for_delivery: '#a855f7', delivered: '#10b981', delayed: '#f59e0b' }[status] || '#64748b';
}

function focusMarker(id) {
    const marker = markers[id];
    if (marker && map) {
        map.setView(marker.getLatLng(), 14);
        marker.openPopup();
    }
}

function fitAllMarkers() {
    if (!map || Object.keys(markers).length === 0) return;
    const bounds = Object.values(markers).map(m => m.getLatLng());
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [30, 30] });
}

function toggleMapStyle() {
    // Toggle between dark/light - implementation depends on map provider
}

function refreshDashboard() {
    const btn = document.getElementById('refreshBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Refreshing...';
    
    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(() => {
            document.getElementById('lastUpdate').textContent = 'Updated just now';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Refresh';
        });
}

function startAutoRefresh() {
    refreshTimer = setInterval(() => {
        const mins = Math.floor((Date.now() - window.loadTime) / 60000);
        document.getElementById('lastUpdate').textContent = mins > 0 ? `Updated ${mins}m ago` : 'Updated just now';
    }, 60000);
}
window.loadTime = Date.now();

// Cleanup
window.addEventListener('beforeunload', () => clearInterval(refreshTimer));
</script>

{{-- Leaflet CSS/JS fallback --}}
@if(!config('services.google_maps.api_key'))
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@else
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initGoogleMap" async defer></script>
<script>
function initGoogleMap() {
    map = new google.maps.Map(document.getElementById('trackingMap'), {
        center: { lat: 0.3476, lng: 32.5825 },
        zoom: 10,
        styles: [
            { elementType: 'geometry', stylers: [{ color: '#1d2c4d' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1626' }] },
        ],
        mapTypeControl: false,
        streetViewControl: false,
    });
    document.getElementById('mapLoading').style.display = 'none';
    
    const bounds = new google.maps.LatLngBounds();
    shipments.forEach(s => {
        if (!s.lat || !s.lng) return;
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(s.lat), lng: parseFloat(s.lng) },
            map: map,
            icon: { path: google.maps.SymbolPath.CIRCLE, fillColor: getStatusColor(s.status), fillOpacity: 0.9, strokeColor: '#fff', strokeWeight: 2, scale: 10 },
            title: s.tracking
        });
        markers[s.id] = marker;
        bounds.extend(marker.getPosition());
        
        const info = new google.maps.InfoWindow({ content: `<b>${s.tracking}</b><br>${s.origin} → ${s.dest}` });
        marker.addListener('click', () => info.open(map, marker));
    });
    if (Object.keys(markers).length > 0) map.fitBounds(bounds);
}

function focusMarker(id) {
    const marker = markers[id];
    if (marker && map) {
        map.panTo(marker.getPosition());
        map.setZoom(14);
        google.maps.event.trigger(marker, 'click');
    }
}

function fitAllMarkers() {
    if (!map || Object.keys(markers).length === 0) return;
    const bounds = new google.maps.LatLngBounds();
    Object.values(markers).forEach(m => bounds.extend(m.getPosition()));
    map.fitBounds(bounds);
}
</script>
@endif
@endpush
