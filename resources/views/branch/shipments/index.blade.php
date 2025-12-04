@extends('branch.layout')

@section('title', 'Shipments')

@push('styles')
<style>
    .action-dropdown { position: relative; display: inline-block; }
    .action-dropdown-content { 
        display: none; position: absolute; right: 0; top: 100%; min-width: 200px; 
        background: #1f1f23; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.5); z-index: 50; overflow: hidden;
    }
    .action-dropdown:hover .action-dropdown-content,
    .action-dropdown:focus-within .action-dropdown-content { display: block; }
    .action-dropdown-content a, .action-dropdown-content button { 
        display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 14px; 
        font-size: 13px; color: #a1a1aa; text-align: left; border: none; background: none;
        transition: all 0.15s;
    }
    .action-dropdown-content a:hover, .action-dropdown-content button:hover { 
        background: rgba(255,255,255,0.08); color: white; 
    }
    .action-dropdown-content .divider { border-top: 1px solid rgba(255,255,255,0.1); margin: 4px 0; }
    .action-dropdown-content .danger:hover { background: rgba(239,68,68,0.2); color: #ef4444; }
    
    .bulk-toolbar { 
        display: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
        border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; 
        align-items: center; gap: 16px; 
    }
    .bulk-toolbar.show { display: flex; }
    .bulk-toolbar .count { font-weight: 600; color: white; }
    .bulk-toolbar .bulk-btn { 
        padding: 6px 12px; background: rgba(255,255,255,0.2); border: none; border-radius: 6px; 
        color: white; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;
        transition: background 0.15s;
    }
    .bulk-toolbar .bulk-btn:hover { background: rgba(255,255,255,0.3); }
    
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .status-booked { background: rgba(59,130,246,0.2); color: #60a5fa; }
    .status-picked_up { background: rgba(168,85,247,0.2); color: #a78bfa; }
    .status-in_transit, .status-linehaul_departed { background: rgba(251,191,36,0.2); color: #fbbf24; }
    .status-at_origin_hub, .status-at_destination_hub { background: rgba(20,184,166,0.2); color: #2dd4bf; }
    .status-out_for_delivery { background: rgba(249,115,22,0.2); color: #fb923c; }
    .status-delivered { background: rgba(34,197,94,0.2); color: #22c55e; }
    .status-cancelled, .status-returned { background: rgba(239,68,68,0.2); color: #ef4444; }
    .status-created, .status-processing { background: rgba(100,116,139,0.2); color: #94a3b8; }
    
    .shipment-checkbox { width: 18px; height: 18px; accent-color: #10b981; cursor: pointer; }
    .export-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
</style>
@endpush

@section('content')
    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-5 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Shipments</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Delivered</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['delivered'] ?? 0) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">In Transit</div>
            <div class="text-2xl font-bold text-sky-400">{{ number_format($stats['in_transit'] ?? 0) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Out for Delivery</div>
            <div class="text-2xl font-bold text-orange-400">{{ number_format($stats['out_for_delivery'] ?? 0) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Pending</div>
            <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['pending'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-panel p-4 mb-6">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="relative md:col-span-2">
                <input type="text" id="shipmentSearch" value="{{ request('search') }}" placeholder="Search tracking#, customer..." autocomplete="off"
                    class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                <div id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            <div>
                <select id="statusFilter" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>Created</option>
                    <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>Booked</option>
                    <option value="picked_up" {{ request('status') === 'picked_up' ? 'selected' : '' }}>Picked Up</option>
                    <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                    <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
                </select>
            </div>
            <div>
                <input type="date" id="dateFrom" value="{{ request('date_from') }}" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2">
                <input type="date" id="dateTo" value="{{ request('date_to') }}" class="flex-1 bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <button type="button" onclick="clearFilters()" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Bulk Actions Toolbar --}}
    <div class="bulk-toolbar" id="bulkToolbar">
        <span class="count"><span id="selectedCount">0</span> selected</span>
        <button type="button" class="bulk-btn" onclick="bulkPrintLabels()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Labels
        </button>
        <button type="button" class="bulk-btn" onclick="bulkDownloadWaybills()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download Waybills
        </button>
        <button type="button" class="bulk-btn" onclick="bulkUpdateStatus()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Update Status
        </button>
        <button type="button" class="bulk-btn ml-auto" onclick="clearSelection()" style="background: rgba(239,68,68,0.3);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Clear
        </button>
    </div>

    {{-- Shipments List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="text-sm font-semibold">All Shipments</div>
                <span class="text-xs text-zinc-500">({{ $shipments->total() ?? $shipments->count() }} total)</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('branch.shipments.labels', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="export-btn bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Batch Labels
                </a>
                <a href="{{ route('branch.pos.index') }}" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Shipment POS
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="shipment-checkbox" onchange="toggleSelectAll()">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Tracking #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Route</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="shipmentsTableBody" class="divide-y divide-white/5">
                    @include('branch.shipments._table', ['shipments' => $shipments])
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="paginationContainer">
            @include('branch.shipments._pagination', ['shipments' => $shipments, 'perPage' => $perPage ?? 15])
        </div>
    </div>

    {{-- Status Update Modal --}}
    <div id="statusModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideStatusModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-md border border-white/10">
            <h3 class="text-lg font-semibold text-white mb-4">Update Shipment Status</h3>
            <input type="hidden" id="statusShipmentId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-zinc-400 mb-2">New Status</label>
                    <select id="newStatus" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white">
                        <option value="BOOKED">Booked</option>
                        <option value="PICKED_UP">Picked Up</option>
                        <option value="AT_ORIGIN_HUB">At Origin Hub</option>
                        <option value="IN_TRANSIT">In Transit</option>
                        <option value="LINEHAUL_DEPARTED">Linehaul Departed</option>
                        <option value="AT_DESTINATION_HUB">At Destination Hub</option>
                        <option value="OUT_FOR_DELIVERY">Out for Delivery</option>
                        <option value="DELIVERED">Delivered</option>
                        <option value="RETURNED">Returned</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-zinc-400 mb-2">Notes (Optional)</label>
                    <textarea id="statusNotes" rows="3" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white" placeholder="Add any notes..."></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button onclick="hideStatusModal()" class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                <button onclick="submitStatusUpdate()" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">Update Status</button>
            </div>
        </div>
    </div>

    {{-- Tracking Modal --}}
    <div id="trackingModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideTrackingModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-lg border border-white/10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Shipment Tracking</h3>
                <button onclick="hideTrackingModal()" class="text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="trackingContent" class="text-zinc-400">Loading...</div>
        </div>
    </div>

    {{-- Shipment Details Modal --}}
    <div id="shipmentModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideShipmentModal()">
        <div class="bg-zinc-900 rounded-xl w-full max-w-2xl border border-white/10 max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-white/10">
                <h3 class="text-lg font-semibold text-white">Shipment Details</h3>
                <button onclick="hideShipmentModal()" class="text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="shipmentModalContent" class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                <div class="text-center py-8 text-zinc-400">
                    <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Loading...
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let searchDebounce = null;
let currentPerPage = {{ $perPage ?? 15 }};

// AJAX Search and Filter
function performSearch() {
    const search = document.getElementById('shipmentSearch').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    params.set('per_page', currentPerPage);
    
    document.getElementById('searchSpinner').classList.remove('hidden');
    
    fetch(`{{ route('branch.shipments.index') }}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('shipmentsTableBody').innerHTML = data.html;
        document.getElementById('paginationContainer').innerHTML = data.pagination;
        window.history.replaceState({}, '', `{{ route('branch.shipments.index') }}?${params.toString()}`);
        clearSelection();
    })
    .catch(err => {
        console.error('Search error:', err);
        // Fallback to page reload
        window.location.href = `{{ route('branch.shipments.index') }}?${params.toString()}`;
    })
    .finally(() => {
        document.getElementById('searchSpinner').classList.add('hidden');
    });
}

// Debounced search on input
document.getElementById('shipmentSearch').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(performSearch, 300);
});

// Instant filter on select/date change
['statusFilter', 'dateFrom', 'dateTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', performSearch);
});

// Change per page
function changePerPage(value) {
    currentPerPage = parseInt(value);
    performSearch();
}

// Clear all filters
function clearFilters() {
    document.getElementById('shipmentSearch').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    performSearch();
}

// Bulk Selection
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    document.querySelectorAll('.shipment-select').forEach(cb => cb.checked = selectAll.checked);
    updateBulkToolbar();
}

function updateBulkToolbar() {
    const selected = document.querySelectorAll('.shipment-select:checked').length;
    const toolbar = document.getElementById('bulkToolbar');
    document.getElementById('selectedCount').textContent = selected;
    toolbar.classList.toggle('show', selected > 0);
}

function clearSelection() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.checked = false;
    document.querySelectorAll('.shipment-select').forEach(cb => cb.checked = false);
    updateBulkToolbar();
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.shipment-select:checked')).map(cb => cb.value);
}

// Bulk Actions
function bulkPrintLabels() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    window.open(`{{ route('branch.shipments.labels') }}?ids=${ids.join(',')}`, '_blank');
}

function bulkDownloadWaybills() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    window.location.href = `{{ route('branch.shipments.labels') }}?ids=${ids.join(',')}&format=pdf`;
}

function bulkUpdateStatus() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    document.getElementById('statusShipmentId').value = ids.join(',');
    showStatusModal();
}

// Single Actions
function showStatusModal(id = null) {
    if (id) document.getElementById('statusShipmentId').value = id;
    document.getElementById('statusModal').classList.remove('hidden');
    document.getElementById('statusModal').classList.add('flex');
}

function hideStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    document.getElementById('statusModal').classList.remove('flex');
}

function submitStatusUpdate() {
    const ids = document.getElementById('statusShipmentId').value;
    const status = document.getElementById('newStatus').value;
    const notes = document.getElementById('statusNotes').value;
    
    // For branch, we do individual updates since bulk endpoint may not exist
    const idArray = ids.split(',').filter(Boolean);
    if (idArray.length === 0) return;
    
    // Simple alert for now - in production you'd implement the endpoint
    alert(`Status update to ${status} for ${idArray.length} shipment(s) - Please use the Operations module for status updates.`);
    hideStatusModal();
}

function showTrackingModal(id, tracking) {
    document.getElementById('trackingModal').classList.remove('hidden');
    document.getElementById('trackingModal').classList.add('flex');
    document.getElementById('trackingContent').innerHTML = `
        <div class="text-center py-8">
            <div class="font-mono text-xl text-white mb-4">${tracking}</div>
            <a href="/branch/shipments/${id}" class="text-emerald-400 hover:text-emerald-300">View Full Details →</a>
        </div>
    `;
}

function hideTrackingModal() {
    document.getElementById('trackingModal').classList.add('hidden');
    document.getElementById('trackingModal').classList.remove('flex');
}

function cancelShipment(id) {
    if (confirm('Are you sure you want to cancel this shipment? This action cannot be undone.')) {
        // Redirect to show page with cancel action
        alert('Please use the Operations module to cancel shipments.');
    }
}

// Shipment Details Modal
function showShipmentModal(id) {
    const modal = document.getElementById('shipmentModal');
    const content = document.getElementById('shipmentModalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    content.innerHTML = `
        <div class="text-center py-8 text-zinc-400">
            <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Loading...
        </div>
    `;
    
    fetch(`/branch/shipments/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.html) {
            content.innerHTML = data.html;
        } else if (data.shipment) {
            const s = data.shipment;
            content.innerHTML = `
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold font-mono text-emerald-400">${s.tracking_number || 'N/A'}</div>
                            <div class="text-sm text-zinc-500">${s.waybill_number || ''}</div>
                        </div>
                        <span class="status-badge status-${(s.current_status || s.status || 'created').toLowerCase().replace(' ', '_')}">
                            ${(s.current_status || s.status || 'Created').replace('_', ' ')}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-lg p-4">
                            <div class="text-xs text-zinc-500 uppercase mb-1">Customer</div>
                            <div class="font-semibold">${s.customer?.name || s.customer_profile?.contact_person || 'Walk-in'}</div>
                        </div>
                        <div class="bg-white/5 rounded-lg p-4">
                            <div class="text-xs text-zinc-500 uppercase mb-1">Service Level</div>
                            <div class="font-semibold">${(s.service_level || 'Standard').charAt(0).toUpperCase() + (s.service_level || 'Standard').slice(1)}</div>
                        </div>
                        <div class="bg-white/5 rounded-lg p-4">
                            <div class="text-xs text-zinc-500 uppercase mb-1">Origin</div>
                            <div class="font-semibold">${s.origin_branch?.name || 'N/A'}</div>
                        </div>
                        <div class="bg-white/5 rounded-lg p-4">
                            <div class="text-xs text-zinc-500 uppercase mb-1">Destination</div>
                            <div class="font-semibold">${s.dest_branch?.name || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="/branch/shipments/${id}" class="flex-1 py-2 text-center bg-white/10 hover:bg-white/20 rounded-lg transition">View Full Details</a>
                        <a href="/branch/pos/${id}/label" target="_blank" class="flex-1 py-2 text-center bg-emerald-600 hover:bg-emerald-500 rounded-lg transition">Print Label</a>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `<div class="text-center py-8 text-red-400">Failed to load shipment details</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="text-center py-8">
                <div class="text-zinc-400 mb-4">Could not load details via AJAX</div>
                <a href="/branch/shipments/${id}" class="text-emerald-400 hover:text-emerald-300">Open Full Page →</a>
            </div>
        `;
    });
}

function hideShipmentModal() {
    const modal = document.getElementById('shipmentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush
