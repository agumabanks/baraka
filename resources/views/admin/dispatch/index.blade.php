@extends('admin.layout')

@section('title', 'Dispatch & Route Optimization')
@section('header', 'Dispatch Center')

@push('styles')
<style>
    .driver-card { transition: all 0.2s ease; }
    .driver-card:hover { transform: translateY(-2px); }
    .workload-bar { height: 8px; border-radius: 4px; background: rgba(255,255,255,0.1); overflow: hidden; }
    .workload-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
    .shipment-checkbox:checked + label { background: rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.5); }
    .optimization-step { opacity: 0.5; }
    .optimization-step.active { opacity: 1; }
    .optimization-step.completed { opacity: 1; }
    .optimization-step.completed .step-icon { background: #10b981; }
    .pulse-assigning { animation: pulse-assign 1.5s infinite; }
    @keyframes pulse-assign { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">Dispatch Center</h2>
            <p class="text-sm text-zinc-400">Route optimization & driver assignment</p>
        </div>
        <div class="flex items-center gap-3">
            <select id="branchFilter" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="">All Branches</option>
                @foreach(\App\Models\Backend\Branch::active()->get() as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            <button onclick="refreshDashboard()" class="btn btn-sm btn-secondary">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
        <div class="glass-panel p-4 border-l-4 border-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Unassigned</div>
                    <div class="text-2xl font-bold text-amber-400 mt-1" id="statUnassigned">{{ number_format($stats['unassigned_shipments']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-sky-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Active Drivers</div>
                    <div class="text-2xl font-bold text-sky-400 mt-1" id="statDrivers">{{ number_format($stats['active_drivers']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-sky-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Today's Assignments</div>
                    <div class="text-2xl font-bold text-emerald-400 mt-1" id="statAssignments">{{ number_format($stats['todays_assignments']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-zinc-400 uppercase tracking-wider">Pending Routes</div>
                    <div class="text-2xl font-bold text-purple-400 mt-1" id="statPending">{{ number_format($stats['pending_routes']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Unassigned Shipments (2 cols) --}}
        <div class="lg:col-span-2 glass-panel">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="font-semibold">Unassigned Shipments</h3>
                    <span class="px-2 py-0.5 text-xs bg-amber-500/20 text-amber-400 rounded-full" id="shipmentCount">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="selectAllShipments()" class="btn btn-xs btn-secondary">Select All</button>
                    <button onclick="optimizeSelected()" class="btn btn-xs btn-primary" id="optimizeBtn" disabled>
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Optimize Route
                    </button>
                </div>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto" id="shipmentsList">
                <div class="text-center py-8 text-zinc-500">
                    <div class="w-8 h-8 border-2 border-zinc-600 border-t-sky-500 rounded-full animate-spin mx-auto mb-3"></div>
                    Loading shipments...
                </div>
            </div>
            {{-- Bulk Actions --}}
            <div class="p-4 border-t border-white/10 flex items-center justify-between bg-white/5" id="bulkActions" style="display: none;">
                <span class="text-sm text-zinc-400"><span id="selectedCount">0</span> shipments selected</span>
                <div class="flex items-center gap-2">
                    <select id="assignDriver" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Select Driver</option>
                    </select>
                    <button onclick="assignToDriver()" class="btn btn-sm btn-secondary" id="manualAssignBtn" disabled>Assign</button>
                    <button onclick="autoAssignSelected()" class="btn btn-sm btn-primary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Auto-Assign
                    </button>
                </div>
            </div>
        </div>

        {{-- Driver Workload (1 col) --}}
        <div class="glass-panel">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <h3 class="font-semibold">Driver Workload</h3>
                <button onclick="rebalanceWorkload()" class="btn btn-xs btn-secondary" title="Rebalance">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto" id="driverWorkload">
                <div class="text-center py-8 text-zinc-500">Loading drivers...</div>
            </div>
        </div>
    </div>

    {{-- Optimization Result Modal --}}
    <div id="optimizationModal" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center" style="display: none;">
        <div class="glass-panel w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <h3 class="font-semibold">Route Optimization Result</h3>
                <button onclick="closeOptimizationModal()" class="text-zinc-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6" id="optimizationContent">
                {{-- Content populated by JS --}}
            </div>
            <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                <button onclick="closeOptimizationModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="applyOptimization()" class="btn btn-primary" id="applyOptBtn">Apply & Assign</button>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid gap-4 md:grid-cols-4">
        <a href="{{ route('admin.dispatch.hub-routes') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-sky-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                </div>
                <div>
                    <div class="font-medium">Hub Routes</div>
                    <div class="text-xs text-zinc-500">Manage inter-hub routing</div>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.tracking.dashboard') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </div>
                <div>
                    <div class="font-medium">Live Tracking</div>
                    <div class="text-xs text-zinc-500">Real-time shipment map</div>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.shipments.index') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <div class="font-medium">All Shipments</div>
                    <div class="text-xs text-zinc-500">Full shipment list</div>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.analytics.dashboard') }}" class="glass-panel p-4 hover:bg-white/5 transition group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <div class="font-medium">Analytics</div>
                    <div class="text-xs text-zinc-500">Performance metrics</div>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
let selectedShipments = new Set();
let driversData = [];
let optimizationResult = null;

document.addEventListener('DOMContentLoaded', () => {
    loadUnassignedShipments();
    loadDriverWorkload();
    
    document.getElementById('branchFilter').addEventListener('change', () => {
        loadUnassignedShipments();
        loadDriverWorkload();
    });
    
    document.getElementById('assignDriver').addEventListener('change', () => {
        document.getElementById('manualAssignBtn').disabled = !document.getElementById('assignDriver').value;
    });
});

async function loadUnassignedShipments() {
    const branchId = document.getElementById('branchFilter').value;
    const container = document.getElementById('shipmentsList');
    
    try {
        const params = new URLSearchParams({ status: 'unassigned' });
        if (branchId) params.append('branch_id', branchId);
        
        const response = await fetch(`/admin/shipments?${params}&per_page=100`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        const shipments = data.data || [];
        document.getElementById('shipmentCount').textContent = shipments.length;
        document.getElementById('statUnassigned').textContent = shipments.length;
        
        if (shipments.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto mb-3 text-emerald-500/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-zinc-400">All shipments assigned!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = shipments.map(s => `
            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10 transition">
                <input type="checkbox" class="shipment-checkbox w-4 h-4 rounded bg-white/10 border-white/20 text-sky-500 focus:ring-sky-500" 
                       value="${s.id}" onchange="toggleShipment(${s.id})">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm text-sky-400">${s.tracking_number || 'TRK-' + String(s.id).padStart(6, '0')}</span>
                        <span class="px-1.5 py-0.5 text-xs rounded ${getStatusBadge(s.status)}">${s.status}</span>
                    </div>
                    <div class="text-xs text-zinc-500 mt-0.5 truncate">
                        ${s.origin_branch?.name || 'Origin'} → ${s.dest_branch?.name || 'Destination'}
                    </div>
                </div>
                <div class="text-right text-xs text-zinc-500">
                    <div>${s.chargeable_weight_kg || 0} kg</div>
                    <div>${s.customer?.company_name || s.customer?.contact_person || 'Customer'}</div>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Failed to load shipments:', error);
        container.innerHTML = '<div class="text-center py-8 text-red-400">Failed to load shipments</div>';
    }
}

async function loadDriverWorkload() {
    const branchId = document.getElementById('branchFilter').value;
    const container = document.getElementById('driverWorkload');
    
    if (!branchId) {
        container.innerHTML = '<div class="text-center py-8 text-zinc-500">Select a branch to view drivers</div>';
        return;
    }
    
    try {
        const response = await fetch(`/admin/dispatch/workload-distribution?branch_id=${branchId}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        
        driversData = data.drivers || [];
        updateDriverSelect();
        
        if (driversData.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-zinc-500">No active drivers in this branch</div>';
            return;
        }
        
        const maxLoad = Math.max(...driversData.map(d => d.current_load || 0), 10);
        
        container.innerHTML = driversData.map(d => {
            const loadPct = Math.min(100, ((d.current_load || 0) / maxLoad) * 100);
            const capacityPct = d.capacity ? Math.min(100, ((d.current_load || 0) / d.capacity) * 100) : 50;
            const loadColor = capacityPct > 80 ? 'bg-red-500' : capacityPct > 60 ? 'bg-amber-500' : 'bg-emerald-500';
            
            return `
                <div class="driver-card p-3 rounded-lg bg-white/5 mb-2">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-500 to-purple-500 flex items-center justify-center text-xs font-bold">
                                ${(d.name || 'D').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="text-sm font-medium">${d.name || 'Driver'}</div>
                                <div class="text-xs text-zinc-500">${d.role || 'Courier'}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold ${capacityPct > 80 ? 'text-red-400' : 'text-white'}">${d.current_load || 0}</div>
                            <div class="text-xs text-zinc-500">shipments</div>
                        </div>
                    </div>
                    <div class="workload-bar">
                        <div class="workload-fill ${loadColor}" style="width: ${loadPct}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-zinc-500 mt-1">
                        <span>${d.completed_today || 0} completed</span>
                        <span>${capacityPct.toFixed(0)}% capacity</span>
                    </div>
                </div>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Failed to load workload:', error);
        container.innerHTML = '<div class="text-center py-8 text-red-400">Failed to load drivers</div>';
    }
}

function updateDriverSelect() {
    const select = document.getElementById('assignDriver');
    select.innerHTML = '<option value="">Select Driver</option>' + 
        driversData.map(d => `<option value="${d.id}">${d.name} (${d.current_load || 0} shipments)</option>`).join('');
}

function toggleShipment(id) {
    if (selectedShipments.has(id)) {
        selectedShipments.delete(id);
    } else {
        selectedShipments.add(id);
    }
    updateBulkActions();
}

function selectAllShipments() {
    const checkboxes = document.querySelectorAll('.shipment-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
        const id = parseInt(cb.value);
        if (!allChecked) {
            selectedShipments.add(id);
        } else {
            selectedShipments.delete(id);
        }
    });
    updateBulkActions();
}

function updateBulkActions() {
    const count = selectedShipments.size;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkActions').style.display = count > 0 ? 'flex' : 'none';
    document.getElementById('optimizeBtn').disabled = count < 2;
}

async function optimizeSelected() {
    if (selectedShipments.size < 2) {
        showToast('Select at least 2 shipments to optimize', 'warning');
        return;
    }
    
    const modal = document.getElementById('optimizationModal');
    const content = document.getElementById('optimizationContent');
    
    content.innerHTML = `
        <div class="text-center py-8">
            <div class="w-12 h-12 border-4 border-zinc-700 border-t-sky-500 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-zinc-400">Optimizing route for ${selectedShipments.size} shipments...</p>
            <p class="text-xs text-zinc-500 mt-2">Using AI-powered route optimization</p>
        </div>
    `;
    modal.style.display = 'flex';
    
    try {
        const response = await fetch('/admin/dispatch/optimize-route', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                shipment_ids: Array.from(selectedShipments),
                strategy: 'auto',
                use_traffic: true
            })
        });
        
        optimizationResult = await response.json();
        displayOptimizationResult(optimizationResult);
        
    } catch (error) {
        console.error('Optimization failed:', error);
        content.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto mb-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-400">Optimization failed</p>
                <p class="text-xs text-zinc-500 mt-2">${error.message || 'Please try again'}</p>
            </div>
        `;
    }
}

function displayOptimizationResult(result) {
    const content = document.getElementById('optimizationContent');
    
    if (!result.success) {
        content.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto mb-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-amber-400">${result.message || 'Could not optimize route'}</p>
            </div>
        `;
        return;
    }
    
    const metrics = result.metrics || {};
    const improvement = result.improvement || {};
    
    content.innerHTML = `
        <div class="space-y-6">
            {{-- Success Header --}}
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h4 class="text-lg font-bold text-emerald-400">Route Optimized!</h4>
                <p class="text-sm text-zinc-400">Strategy: ${result.strategy_used || 'auto'}</p>
            </div>
            
            {{-- Metrics Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-sky-400">${metrics.total_stops || selectedShipments.size}</div>
                    <div class="text-xs text-zinc-500">Total Stops</div>
                </div>
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-purple-400">${(metrics.total_distance || 0).toFixed(1)} km</div>
                    <div class="text-xs text-zinc-500">Total Distance</div>
                </div>
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-amber-400">${metrics.estimated_time || '~2h'}</div>
                    <div class="text-xs text-zinc-500">Est. Duration</div>
                </div>
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-emerald-400">${(improvement.improvement_percentage || 0).toFixed(1)}%</div>
                    <div class="text-xs text-zinc-500">Improvement</div>
                </div>
            </div>
            
            {{-- Route Order --}}
            <div>
                <h5 class="text-sm font-semibold mb-3">Optimized Stop Order</h5>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    ${(result.optimized_order || Array.from(selectedShipments)).map((id, i) => `
                        <div class="flex items-center gap-3 p-2 bg-white/5 rounded-lg">
                            <div class="w-6 h-6 rounded-full bg-sky-500/20 flex items-center justify-center text-xs font-bold text-sky-400">${i + 1}</div>
                            <span class="font-mono text-sm">Shipment #${id}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            {{-- Savings --}}
            ${improvement.distance_saved ? `
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <div class="text-sm font-medium text-emerald-400">Estimated Savings</div>
                            <div class="text-xs text-zinc-400">${improvement.distance_saved?.toFixed(1) || 0} km less • ~$${metrics.estimated_fuel_cost?.toFixed(2) || '0'} fuel</div>
                        </div>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

function closeOptimizationModal() {
    document.getElementById('optimizationModal').style.display = 'none';
    optimizationResult = null;
}

async function applyOptimization() {
    if (!optimizationResult || !optimizationResult.success) return;
    
    const driverId = document.getElementById('assignDriver').value;
    if (!driverId) {
        showToast('Please select a driver first', 'warning');
        return;
    }
    
    // Assign shipments to driver in optimized order
    showToast('Assigning shipments...', 'info');
    
    try {
        for (const shipmentId of (optimizationResult.optimized_order || Array.from(selectedShipments))) {
            await fetch('/admin/dispatch/manual-assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ shipment_id: shipmentId, driver_id: parseInt(driverId) })
            });
        }
        
        showToast('All shipments assigned successfully!', 'success');
        closeOptimizationModal();
        selectedShipments.clear();
        updateBulkActions();
        loadUnassignedShipments();
        loadDriverWorkload();
        
    } catch (error) {
        showToast('Assignment failed: ' + error.message, 'error');
    }
}

async function autoAssignSelected() {
    if (selectedShipments.size === 0) return;
    
    showToast(`Auto-assigning ${selectedShipments.size} shipments...`, 'info');
    
    try {
        const response = await fetch('/admin/dispatch/bulk-auto-assign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ shipment_ids: Array.from(selectedShipments) })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Successfully assigned ${result.assigned || selectedShipments.size} shipments`, 'success');
            selectedShipments.clear();
            updateBulkActions();
            loadUnassignedShipments();
            loadDriverWorkload();
        } else {
            showToast(result.message || 'Auto-assignment failed', 'error');
        }
        
    } catch (error) {
        showToast('Auto-assignment failed', 'error');
    }
}

async function assignToDriver() {
    const driverId = document.getElementById('assignDriver').value;
    if (!driverId || selectedShipments.size === 0) return;
    
    showToast(`Assigning ${selectedShipments.size} shipments...`, 'info');
    
    try {
        for (const shipmentId of selectedShipments) {
            await fetch('/admin/dispatch/manual-assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ shipment_id: shipmentId, driver_id: parseInt(driverId) })
            });
        }
        
        showToast('Shipments assigned successfully!', 'success');
        selectedShipments.clear();
        updateBulkActions();
        loadUnassignedShipments();
        loadDriverWorkload();
        
    } catch (error) {
        showToast('Assignment failed', 'error');
    }
}

async function rebalanceWorkload() {
    const branchId = document.getElementById('branchFilter').value;
    if (!branchId) {
        showToast('Select a branch first', 'warning');
        return;
    }
    
    showToast('Rebalancing workload...', 'info');
    
    try {
        const response = await fetch('/admin/dispatch/rebalance-workload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ branch_id: parseInt(branchId) })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Rebalanced: ${result.moves || 0} shipments reassigned`, 'success');
            loadDriverWorkload();
        } else {
            showToast(result.message || 'Rebalancing failed', 'error');
        }
        
    } catch (error) {
        showToast('Rebalancing failed', 'error');
    }
}

function refreshDashboard() {
    loadUnassignedShipments();
    loadDriverWorkload();
    showToast('Dashboard refreshed', 'success');
}

function getStatusBadge(status) {
    const badges = {
        'created': 'bg-zinc-500/20 text-zinc-400',
        'pending': 'bg-amber-500/20 text-amber-400',
        'processing': 'bg-sky-500/20 text-sky-400',
        'in_transit': 'bg-purple-500/20 text-purple-400',
    };
    return badges[status] || 'bg-zinc-500/20 text-zinc-400';
}

function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-emerald-500',
        error: 'bg-red-500',
        warning: 'bg-amber-500',
        info: 'bg-sky-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endpush
