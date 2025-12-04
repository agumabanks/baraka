@extends('admin.layout')

@section('title', 'Hub Routes Management')
@section('header', 'Hub Routes')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">Hub Routes</h2>
            <p class="text-sm text-zinc-400">Configure inter-hub routes, transit times, and costs</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openAddModal()" class="btn btn-primary">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Route
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
        <div class="glass-panel p-4 border-l-4 border-sky-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Total Routes</div>
            <div class="text-2xl font-bold text-sky-400 mt-1">{{ $routes->total() }}</div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-emerald-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Active Hubs</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ $hubs->count() }}</div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-purple-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Express Routes</div>
            <div class="text-2xl font-bold text-purple-400 mt-1">{{ $routes->where('service_level', 'express')->count() }}</div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-amber-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Transport Modes</div>
            <div class="text-2xl font-bold text-amber-400 mt-1">{{ $routes->pluck('transport_mode')->unique()->count() }}</div>
        </div>
    </div>

    {{-- Routes Table --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <h3 class="font-semibold">Route Configuration</h3>
            <div class="flex items-center gap-2">
                <select id="filterServiceLevel" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm" onchange="filterRoutes()">
                    <option value="">All Service Levels</option>
                    <option value="express">Express</option>
                    <option value="standard">Standard</option>
                    <option value="economy">Economy</option>
                </select>
                <select id="filterMode" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm" onchange="filterRoutes()">
                    <option value="">All Modes</option>
                    <option value="road">Road</option>
                    <option value="air">Air</option>
                    <option value="rail">Rail</option>
                    <option value="sea">Sea</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5 text-xs uppercase text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Origin</th>
                        <th class="px-4 py-3 text-left">Destination</th>
                        <th class="px-4 py-3 text-center">Mode</th>
                        <th class="px-4 py-3 text-center">Service Level</th>
                        <th class="px-4 py-3 text-right">Distance</th>
                        <th class="px-4 py-3 text-right">Transit Time</th>
                        <th class="px-4 py-3 text-right">Base Cost</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="routesTableBody">
                    @forelse($routes as $route)
                        <tr class="border-b border-white/5 hover:bg-white/5 route-row" 
                            data-service="{{ $route->service_level }}" 
                            data-mode="{{ $route->transport_mode }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $route->originHub->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-zinc-500">{{ $route->originHub->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $route->destinationHub->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-zinc-500">{{ $route->destinationHub->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $modeIcons = [
                                        'road' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
                                        'air' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>',
                                        'rail' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                                        'sea' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>',
                                    ];
                                    $modeColors = ['road' => 'text-sky-400', 'air' => 'text-purple-400', 'rail' => 'text-amber-400', 'sea' => 'text-emerald-400'];
                                @endphp
                                <span class="{{ $modeColors[$route->transport_mode] ?? 'text-zinc-400' }}" title="{{ ucfirst($route->transport_mode) }}">
                                    {!! $modeIcons[$route->transport_mode] ?? $route->transport_mode !!}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $levelBadges = [
                                        'express' => 'bg-purple-500/20 text-purple-400',
                                        'standard' => 'bg-sky-500/20 text-sky-400',
                                        'economy' => 'bg-zinc-500/20 text-zinc-400',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $levelBadges[$route->service_level] ?? 'bg-zinc-500/20' }}">
                                    {{ ucfirst($route->service_level) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($route->distance_km, 1) }} km</td>
                            <td class="px-4 py-3 text-right font-mono">{{ $route->transit_time_hours }}h</td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($route->base_cost, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($route->is_active ?? true)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-400">Active</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-500/20 text-zinc-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="editRoute({{ $route->id }})" class="p-1.5 hover:bg-white/10 rounded" title="Edit">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button onclick="deleteRoute({{ $route->id }})" class="p-1.5 hover:bg-red-500/20 rounded" title="Delete">
                                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-zinc-500">
                                No hub routes configured. Click "Add Route" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($routes->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $routes->links() }}
            </div>
        @endif
    </div>

    {{-- Back Link --}}
    <div class="flex gap-3">
        <a href="{{ route('admin.dispatch.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Dispatch
        </a>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div id="routeModal" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center" style="display: none;">
    <div class="glass-panel w-full max-w-lg mx-4">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <h3 class="font-semibold" id="modalTitle">Add Hub Route</h3>
            <button onclick="closeModal()" class="text-zinc-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="routeForm" class="p-6 space-y-4">
            <input type="hidden" id="routeId" value="">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Origin Hub</label>
                    <select id="originHub" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select Hub</option>
                        @foreach($hubs as $hub)
                            <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Destination Hub</label>
                    <select id="destinationHub" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select Hub</option>
                        @foreach($hubs as $hub)
                            <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Transport Mode</label>
                    <select id="transportMode" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <option value="road">Road</option>
                        <option value="air">Air</option>
                        <option value="rail">Rail</option>
                        <option value="sea">Sea</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Service Level</label>
                    <select id="serviceLevel" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <option value="express">Express</option>
                        <option value="standard">Standard</option>
                        <option value="economy">Economy</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Distance (km)</label>
                    <input type="number" id="distanceKm" step="0.1" min="0" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Transit Time (hours)</label>
                    <input type="number" id="transitTime" min="1" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Base Cost ($)</label>
                    <input type="number" id="baseCost" step="0.01" min="0" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Cost/kg ($)</label>
                    <input type="number" id="costPerKg" step="0.01" min="0" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-1">Cost/cbm ($)</label>
                    <input type="number" id="costPerCbm" step="0.01" min="0" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
        </form>
        <div class="p-4 border-t border-white/10 flex justify-end gap-3">
            <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            <button onclick="saveRoute()" class="btn btn-primary">Save Route</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const routesData = @json($routes->items());

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Hub Route';
    document.getElementById('routeId').value = '';
    document.getElementById('routeForm').reset();
    document.getElementById('routeModal').style.display = 'flex';
}

function editRoute(id) {
    const route = routesData.find(r => r.id === id);
    if (!route) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Hub Route';
    document.getElementById('routeId').value = route.id;
    document.getElementById('originHub').value = route.origin_hub_id;
    document.getElementById('destinationHub').value = route.destination_hub_id;
    document.getElementById('transportMode').value = route.transport_mode;
    document.getElementById('serviceLevel').value = route.service_level;
    document.getElementById('distanceKm').value = route.distance_km;
    document.getElementById('transitTime').value = route.transit_time_hours;
    document.getElementById('baseCost').value = route.base_cost;
    document.getElementById('costPerKg').value = route.cost_per_kg || '';
    document.getElementById('costPerCbm').value = route.cost_per_cbm || '';
    
    document.getElementById('routeModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('routeModal').style.display = 'none';
}

async function saveRoute() {
    const routeId = document.getElementById('routeId').value;
    const data = {
        origin_hub_id: parseInt(document.getElementById('originHub').value),
        destination_hub_id: parseInt(document.getElementById('destinationHub').value),
        transport_mode: document.getElementById('transportMode').value,
        service_level: document.getElementById('serviceLevel').value,
        distance_km: parseFloat(document.getElementById('distanceKm').value),
        transit_time_hours: parseInt(document.getElementById('transitTime').value),
        base_cost: parseFloat(document.getElementById('baseCost').value),
        cost_per_kg: parseFloat(document.getElementById('costPerKg').value) || null,
        cost_per_cbm: parseFloat(document.getElementById('costPerCbm').value) || null,
    };
    
    if (data.origin_hub_id === data.destination_hub_id) {
        alert('Origin and destination hubs must be different');
        return;
    }
    
    const url = routeId ? `/admin/dispatch/hub-routes/${routeId}` : '/admin/dispatch/hub-routes';
    const method = routeId ? 'PUT' : 'POST';
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Failed to save route');
        }
    } catch (error) {
        alert('Error saving route');
    }
}

async function deleteRoute(id) {
    if (!confirm('Are you sure you want to delete this route?')) return;
    
    try {
        const response = await fetch(`/admin/dispatch/hub-routes/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Failed to delete route');
        }
    } catch (error) {
        alert('Error deleting route');
    }
}

function filterRoutes() {
    const serviceLevel = document.getElementById('filterServiceLevel').value;
    const mode = document.getElementById('filterMode').value;
    
    document.querySelectorAll('.route-row').forEach(row => {
        const matchService = !serviceLevel || row.dataset.service === serviceLevel;
        const matchMode = !mode || row.dataset.mode === mode;
        row.style.display = (matchService && matchMode) ? '' : 'none';
    });
}
</script>
@endpush
