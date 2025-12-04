@extends('branch.layout')

@section('title', 'Maintenance Windows')

@push('styles')
<style>
    .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.03) 100%);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 16px 20px;
    }
    .filter-input {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 6px;
        padding: 8px 12px;
        color: white;
        font-size: 13px;
    }
    .filter-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
    }
    .status-scheduled { background: rgba(59,130,246,0.2); color: #60a5fa; }
    .status-in_progress { background: rgba(251,191,36,0.2); color: #fbbf24; }
    .status-completed { background: rgba(34,197,94,0.2); color: #22c55e; }
    .status-cancelled { background: rgba(239,68,68,0.2); color: #ef4444; }
    
    .entity-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .entity-branch { background: rgba(16,185,129,0.2); color: #10b981; }
    .entity-vehicle { background: rgba(59,130,246,0.2); color: #3b82f6; }
    .entity-warehouse_location { background: rgba(251,191,36,0.2); color: #fbbf24; }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Maintenance Windows</h1>
            <p class="text-sm text-zinc-400">Schedule and manage maintenance for vehicles, locations, and branch facilities</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('branch.operations') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white rounded-lg text-sm transition">
                ← Back to Operations
            </a>
            <button onclick="showCreateModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Schedule Maintenance
            </button>
        </div>
    </div>

    {{-- Active Maintenance Alert --}}
    @if($activeMaintenanceWindows->isNotEmpty())
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <div class="font-semibold text-amber-200">Active Maintenance in Progress</div>
                    <p class="text-sm text-amber-300/70">{{ $activeMaintenanceWindows->count() }} maintenance window(s) currently active. Capacity may be reduced.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="text-2xl font-bold text-white">{{ $maintenanceWindows->total() }}</div>
            <div class="text-xs text-zinc-500 uppercase">Total Windows</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-sky-400">{{ $maintenanceWindows->where('status', 'scheduled')->count() }}</div>
            <div class="text-xs text-zinc-500 uppercase">Scheduled</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-amber-400">{{ $activeMaintenanceWindows->count() }}</div>
            <div class="text-xs text-zinc-500 uppercase">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-emerald-400">{{ $maintenanceWindows->where('status', 'completed')->count() }}</div>
            <div class="text-xs text-zinc-500 uppercase">Completed</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-panel p-4 mb-6">
        <form method="GET" action="{{ route('branch.operations.maintenance') }}" class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <select name="entity_type" class="filter-input">
                <option value="">All Entity Types</option>
                <option value="branch" {{ request('entity_type') == 'branch' ? 'selected' : '' }}>Branch</option>
                <option value="vehicle" {{ request('entity_type') == 'vehicle' ? 'selected' : '' }}>Vehicle</option>
                <option value="warehouse_location" {{ request('entity_type') == 'warehouse_location' ? 'selected' : '' }}>Warehouse Location</option>
            </select>
            <select name="status" class="filter-input">
                <option value="">All Statuses</option>
                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="filter-input" placeholder="From">
            <input type="date" name="to" value="{{ request('to') }}" class="filter-input" placeholder="To">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-medium transition">
                Apply Filters
            </button>
        </form>
    </div>

    {{-- Maintenance Windows Table --}}
    <div class="glass-panel overflow-hidden">
        <div class="p-4 border-b border-white/10">
            <div class="font-semibold">Maintenance Windows</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Entity</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Scheduled Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Impact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($maintenanceWindows as $window)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="entity-icon entity-{{ $window->entity_type }}">
                                        @if($window->entity_type == 'vehicle')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M8 17l-4-4m4 4l4-4m-4 4H4"/></svg>
                                        @elseif($window->entity_type == 'warehouse_location')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-white">{{ ucfirst(str_replace('_', ' ', $window->entity_type)) }}</div>
                                        <div class="text-xs text-zinc-500">#{{ $window->entity_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 bg-white/10 rounded">{{ ucfirst($window->maintenance_type) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded status-{{ $window->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $window->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-white">{{ $window->scheduled_start_at->format('M d, Y H:i') }}</div>
                                <div class="text-xs text-zinc-500">to {{ $window->scheduled_end_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-2 bg-white/10 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: {{ $window->capacity_impact_percent }}%"></div>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $window->capacity_impact_percent }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-zinc-300 max-w-xs truncate">{{ $window->description }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($window->status == 'scheduled')
                                        <form method="POST" action="{{ route('branch.operations.maintenance.start', $window->id) }}">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs transition">
                                                Start
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('branch.operations.maintenance.cancel', $window->id) }}">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded text-xs transition">
                                                Cancel
                                            </button>
                                        </form>
                                    @elseif($window->status == 'in_progress')
                                        <button onclick="showCompleteModal({{ $window->id }})" class="px-2 py-1 bg-sky-600 hover:bg-sky-500 text-white rounded text-xs transition">
                                            Complete
                                        </button>
                                    @endif
                                    <button onclick="showViewModal({{ json_encode($window) }})" class="px-2 py-1 bg-white/10 hover:bg-white/20 text-zinc-400 hover:text-white rounded text-xs transition">
                                        View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="text-zinc-400 mb-2">No maintenance windows found</div>
                                <button onclick="showCreateModal()" class="text-emerald-400 hover:text-emerald-300">Schedule your first maintenance</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($maintenanceWindows->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $maintenanceWindows->links() }}
            </div>
        @endif
    </div>

    {{-- Create Maintenance Modal --}}
    <div id="createModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideCreateModal()">
        <div class="bg-zinc-900 rounded-xl w-full max-w-lg border border-white/10 max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Schedule Maintenance</h3>
                <button onclick="hideCreateModal()" class="text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('branch.operations.maintenance.store') }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Entity Type *</label>
                        <select name="entity_type" class="w-full filter-input" required onchange="loadEntities(this.value)">
                            <option value="">Select Type</option>
                            <option value="branch">Branch</option>
                            <option value="vehicle">Vehicle</option>
                            <option value="warehouse_location">Warehouse Location</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Entity *</label>
                        <select name="entity_id" id="entity_id" class="w-full filter-input" required>
                            <option value="">Select entity type first</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Maintenance Type *</label>
                        <select name="maintenance_type" class="w-full filter-input" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="emergency">Emergency</option>
                            <option value="repair">Repair</option>
                            <option value="inspection">Inspection</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Capacity Impact % *</label>
                        <input type="number" name="capacity_impact_percent" class="w-full filter-input" min="0" max="100" value="50" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Start Date & Time *</label>
                        <input type="datetime-local" name="scheduled_start_at" class="w-full filter-input" required>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">End Date & Time *</label>
                        <input type="datetime-local" name="scheduled_end_at" class="w-full filter-input" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-2">Description *</label>
                    <textarea name="description" class="w-full filter-input" rows="3" required placeholder="Describe the maintenance work..."></textarea>
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-2">Notes</label>
                    <textarea name="notes" class="w-full filter-input" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="hideCreateModal()" class="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">Schedule</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Complete Modal --}}
    <div id="completeModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideCompleteModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-md border border-white/10">
            <h3 class="text-lg font-semibold text-white mb-4">Complete Maintenance</h3>
            <form method="POST" id="completeForm" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm text-zinc-400 mb-2">Completion Notes</label>
                    <textarea name="notes" class="w-full filter-input" rows="4" placeholder="Enter any completion notes..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="hideCompleteModal()" class="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">Mark Complete</button>
                </div>
            </form>
        </div>
    </div>

    {{-- View Modal --}}
    <div id="viewModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideViewModal()">
        <div class="bg-zinc-900 rounded-xl w-full max-w-lg border border-white/10">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Maintenance Details</h3>
                <button onclick="hideViewModal()" class="text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="viewModalContent" class="p-6">
                <!-- Content will be populated by JS -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('createModal').classList.add('flex');
}
function hideCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createModal').classList.remove('flex');
}

function showCompleteModal(id) {
    document.getElementById('completeForm').action = `/branch/operations/maintenance/${id}/complete`;
    document.getElementById('completeModal').classList.remove('hidden');
    document.getElementById('completeModal').classList.add('flex');
}
function hideCompleteModal() {
    document.getElementById('completeModal').classList.add('hidden');
    document.getElementById('completeModal').classList.remove('flex');
}

function showViewModal(window) {
    const content = document.getElementById('viewModalContent');
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-zinc-500 uppercase">Entity Type</div>
                <div class="text-white">${window.entity_type?.replace('_', ' ') || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Entity ID</div>
                <div class="text-white">#${window.entity_id || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Maintenance Type</div>
                <div class="text-white">${window.maintenance_type || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Status</div>
                <div class="text-white">${window.status?.replace('_', ' ') || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Scheduled Start</div>
                <div class="text-white">${window.scheduled_start_at || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Scheduled End</div>
                <div class="text-white">${window.scheduled_end_at || 'N/A'}</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Capacity Impact</div>
                <div class="text-white">${window.capacity_impact_percent || 0}%</div>
            </div>
            <div>
                <div class="text-xs text-zinc-500 uppercase">Created By</div>
                <div class="text-white">${window.creator?.name || 'System'}</div>
            </div>
            <div class="col-span-2">
                <div class="text-xs text-zinc-500 uppercase">Description</div>
                <div class="text-white">${window.description || 'No description'}</div>
            </div>
            ${window.notes ? `
            <div class="col-span-2">
                <div class="text-xs text-zinc-500 uppercase">Notes</div>
                <div class="text-white">${window.notes}</div>
            </div>
            ` : ''}
        </div>
    `;
    document.getElementById('viewModal').classList.remove('hidden');
    document.getElementById('viewModal').classList.add('flex');
}
function hideViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    document.getElementById('viewModal').classList.remove('flex');
}

function loadEntities(entityType) {
    const entitySelect = document.getElementById('entity_id');
    entitySelect.innerHTML = '<option value="">Loading...</option>';
    
    if (!entityType) {
        entitySelect.innerHTML = '<option value="">Select entity type first</option>';
        return;
    }
    
    fetch(`{{ route('branch.operations.maintenance.entities') }}?type=${entityType}`)
        .then(response => response.json())
        .then(data => {
            entitySelect.innerHTML = '<option value="">Select ' + entityType.replace('_', ' ') + '</option>';
            data.forEach(entity => {
                const option = document.createElement('option');
                option.value = entity.id;
                option.textContent = entity.name;
                entitySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            entitySelect.innerHTML = '<option value="">Error loading</option>';
        });
}
</script>
@endpush
