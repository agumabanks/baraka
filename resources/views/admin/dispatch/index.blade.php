@extends('admin.layout')

@section('title', 'Dispatch & Route Optimization')
@section('header', 'Dispatch Center')

@section('content')
    {{-- Stats Cards --}}
    <div class="grid gap-3 md:grid-cols-4 mb-6">
        <div class="stat-card bg-gradient-to-r from-amber-500/10 to-amber-500/5 border border-amber-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">Unassigned</div>
                    <div class="text-3xl font-bold text-amber-400">{{ number_format($stats['unassigned_shipments']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-r from-sky-500/10 to-sky-500/5 border border-sky-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">Active Drivers</div>
                    <div class="text-3xl font-bold text-sky-400">{{ number_format($stats['active_drivers']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-sky-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-r from-emerald-500/10 to-emerald-500/5 border border-emerald-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">Today's Assignments</div>
                    <div class="text-3xl font-bold text-emerald-400">{{ number_format($stats['todays_assignments']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-r from-purple-500/10 to-purple-500/5 border border-purple-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">Pending Routes</div>
                    <div class="text-3xl font-bold text-purple-400">{{ number_format($stats['pending_routes']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Route Optimization Panel --}}
        <div class="glass-panel">
            <div class="p-4 border-b border-white/10">
                <h3 class="font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Route Optimization
                </h3>
            </div>
            <div class="p-6">
                <form id="optimize-route-form" class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Select Shipments</label>
                        <select id="shipment-select" multiple class="w-full p-3 bg-white/5 border border-white/20 rounded-lg text-white">
                            <option value="">Loading shipments...</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Select multiple shipments to optimize their route</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-300 mb-2">Strategy</label>
                            <select id="strategy-select" class="w-full p-3 bg-white/5 border border-white/20 rounded-lg text-white">
                                <option value="auto">Auto (Recommended)</option>
                                <option value="2opt">2-Opt (Small Routes)</option>
                                <option value="3opt">3-Opt (Medium Routes)</option>
                                <option value="genetic_2opt">Genetic + 2-Opt</option>
                                <option value="simulated_annealing">Simulated Annealing (Large)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-2">Use Traffic Data</label>
                            <select id="traffic-select" class="w-full p-3 bg-white/5 border border-white/20 rounded-lg text-white">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Optimize Route
                    </button>
                </form>
                <div id="optimization-result" class="mt-4 hidden">
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                        <h4 class="font-semibold text-emerald-400 mb-2">Optimization Result</h4>
                        <div id="result-content"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auto Assignment Panel --}}
        <div class="glass-panel">
            <div class="p-4 border-b border-white/10">
                <h3 class="font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    AI-Powered Assignment
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <p class="text-slate-400 text-sm">
                        Automatically assign shipments to drivers based on workload, proximity, performance, and skills.
                    </p>
                    <div class="flex gap-3">
                        <button id="bulk-assign-btn" class="flex-1 btn btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Auto-Assign All
                        </button>
                        <button id="rebalance-btn" class="flex-1 btn btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Rebalance
                        </button>
                    </div>
                </div>
                
                {{-- Workload Distribution --}}
                <div class="mt-6">
                    <h4 class="text-sm font-semibold text-slate-300 mb-3">Driver Workload</h4>
                    <div id="workload-container" class="space-y-2">
                        <div class="text-center py-4 text-slate-500">
                            <span>Select a branch to view workload</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-6 flex gap-3 flex-wrap">
        <a href="{{ route('admin.dispatch.hub-routes') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            Manage Hub Routes
        </a>
        <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            View Shipments
        </a>
        <a href="{{ route('admin.tracking.dashboard') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            </svg>
            Live Tracking
        </a>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load unassigned shipments for selection
    loadUnassignedShipments();
    
    // Optimize route form
    document.getElementById('optimize-route-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const select = document.getElementById('shipment-select');
        const selectedIds = Array.from(select.selectedOptions).map(opt => parseInt(opt.value));
        
        if (selectedIds.length < 2) {
            alert('Please select at least 2 shipments to optimize');
            return;
        }
        
        const response = await fetch('{{ route("admin.dispatch.optimize-route") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                shipment_ids: selectedIds,
                strategy: document.getElementById('strategy-select').value,
                use_traffic: document.getElementById('traffic-select').value === '1',
            }),
        });
        
        const result = await response.json();
        displayOptimizationResult(result);
    });
    
    // Bulk assign button
    document.getElementById('bulk-assign-btn').addEventListener('click', async function() {
        // TODO: Implement bulk assignment
        alert('Bulk assignment will be triggered for all unassigned shipments');
    });
    
    // Rebalance button
    document.getElementById('rebalance-btn').addEventListener('click', async function() {
        // TODO: Implement workload rebalancing
        alert('Workload rebalancing will be triggered');
    });
});

async function loadUnassignedShipments() {
    // Placeholder - would load from API
    const select = document.getElementById('shipment-select');
    select.innerHTML = '<option value="" disabled>Select shipments to optimize...</option>';
}

function displayOptimizationResult(result) {
    const container = document.getElementById('optimization-result');
    const content = document.getElementById('result-content');
    
    if (result.success) {
        content.innerHTML = `
            <p class="text-sm"><strong>Strategy:</strong> ${result.strategy_used}</p>
            <p class="text-sm"><strong>Total Distance:</strong> ${result.metrics?.total_distance || 0} km</p>
            <p class="text-sm"><strong>Total Stops:</strong> ${result.metrics?.total_stops || 0}</p>
            <p class="text-sm"><strong>Improvement:</strong> ${result.improvement?.improvement_percentage || 0}%</p>
            <p class="text-sm"><strong>Est. Fuel Cost:</strong> $${result.metrics?.estimated_fuel_cost || 0}</p>
        `;
    } else {
        content.innerHTML = `<p class="text-red-400">${result.message || 'Optimization failed'}</p>`;
    }
    
    container.classList.remove('hidden');
}
</script>
@endpush
