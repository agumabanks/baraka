@extends('admin.layout')

@section('title', 'Shipments')
@section('header', 'Shipment Management')

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
        display: none; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
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
    
    .shipment-checkbox { width: 18px; height: 18px; accent-color: #3b82f6; cursor: pointer; }
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
        <div class="grid gap-3 md:grid-cols-6">
            <div class="relative">
                <input type="text" id="shipmentSearch" value="{{ request('q') }}" placeholder="Search tracking#, customer..." autocomplete="off"
                    class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                <div id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            <div>
                <select id="statusFilter" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    @foreach($statuses ?? [] as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select id="branchFilter" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
                    <option value="">All Branches</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" id="dateFrom" value="{{ request('date_from') }}" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <input type="date" id="dateTo" value="{{ request('date_to') }}" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2">
                @if(request()->hasAny(['q', 'status', 'branch', 'date_from', 'date_to']))
                    <button type="button" onclick="clearFilters()" class="btn btn-sm btn-secondary flex-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
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
        <button type="button" class="bulk-btn" onclick="bulkExport()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
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
                {{-- Export Buttons --}}
                <a href="{{ route('admin.shipments.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="export-btn bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    CSV
                </a>
                <a href="{{ route('admin.shipments.index', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="export-btn bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <a href="{{ route('admin.pos.index') }}" class="btn btn-sm btn-primary">
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
                    @include('admin.shipments._table', ['shipments' => $shipments])
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="paginationContainer">
            @include('admin.shipments._pagination', ['shipments' => $shipments, 'perPage' => $perPage ?? 10])
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
                <button onclick="submitStatusUpdate()" class="flex-1 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition">Update Status</button>
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
let currentPerPage = {{ $perPage ?? 10 }};

// AJAX Search and Filter
function performSearch() {
    const search = document.getElementById('shipmentSearch').value;
    const status = document.getElementById('statusFilter').value;
    const branch = document.getElementById('branchFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const params = new URLSearchParams();
    if (search) params.set('q', search);
    if (status) params.set('status', status);
    if (branch) params.set('branch', branch);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    params.set('per_page', currentPerPage);
    
    // Show spinner
    document.getElementById('searchSpinner').classList.remove('hidden');
    
    fetch(`{{ route('admin.shipments.index') }}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('shipmentsTableBody').innerHTML = data.html;
        document.getElementById('paginationContainer').innerHTML = data.pagination;
        // Update URL without reload
        window.history.replaceState({}, '', `{{ route('admin.shipments.index') }}?${params.toString()}`);
        // Reset bulk selection
        clearSelection();
    })
    .catch(err => console.error('Search error:', err))
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
['statusFilter', 'branchFilter', 'dateFrom', 'dateTo'].forEach(id => {
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
    document.getElementById('branchFilter').value = '';
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
    window.open(`/admin/shipments/bulk-labels?ids=${ids.join(',')}`, '_blank');
}

function bulkDownloadWaybills() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    window.location.href = `/admin/shipments/bulk-waybills?ids=${ids.join(',')}`;
}

function bulkUpdateStatus() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    document.getElementById('statusShipmentId').value = ids.join(',');
    showStatusModal();
}

function bulkExport() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    window.location.href = `/admin/shipments/export?ids=${ids.join(',')}&format=csv`;
}

// Single Actions
function downloadWaybill(id) {
    window.location.href = `/admin/pos/${id}/label?format=pdf`;
}

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
    
    fetch('/admin/shipments/bulk-update-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ ids: ids.split(','), status, notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update status');
        }
    })
    .catch(() => alert('An error occurred'));
}

function showTrackingModal(id, tracking) {
    document.getElementById('trackingModal').classList.remove('hidden');
    document.getElementById('trackingModal').classList.add('flex');
    document.getElementById('trackingContent').innerHTML = `
        <div class="text-center py-8">
            <div class="font-mono text-xl text-white mb-4">${tracking}</div>
            <a href="/admin/tracking/${id}" target="_blank" class="text-sky-400 hover:text-sky-300">View Full Tracking History →</a>
        </div>
    `;
}

function hideTrackingModal() {
    document.getElementById('trackingModal').classList.add('hidden');
    document.getElementById('trackingModal').classList.remove('flex');
}

function duplicateShipment(id) {
    if (confirm('Create a copy of this shipment?')) {
        window.location.href = `/admin/pos?duplicate=${id}`;
    }
}

function cancelShipment(id) {
    if (confirm('Are you sure you want to cancel this shipment? This action cannot be undone.')) {
        fetch(`/admin/shipments/${id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel shipment');
            }
        })
        .catch(() => alert('An error occurred'));
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
    
    fetch(`/admin/shipments/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.html) {
            content.innerHTML = data.html;
        } else {
            content.innerHTML = `<div class="text-center py-8 text-red-400">Failed to load shipment details</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `<div class="text-center py-8 text-red-400">Failed to load shipment details</div>`;
    });
}

function hideShipmentModal() {
    const modal = document.getElementById('shipmentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush
