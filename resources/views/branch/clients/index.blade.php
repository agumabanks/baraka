@extends('branch.layout')

@section('title', 'Client Management')
@section('header', 'Client Management')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Client Management</h1>
            <p class="text-sm muted">Manage your branch clients, contracts, and credit limits</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('branch.clients.create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Client
            </a>
            <a href="{{ route('branch.clients.export', request()->query()) }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="glass-panel p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xs uppercase muted mb-1">Total Clients</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xs uppercase muted mb-1">Active</div>
                    <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xs uppercase muted mb-1">VIP Clients</div>
                    <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['vip']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="glass-panel p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xs uppercase muted mb-1">Credit Issues</div>
                    <div class="text-2xl font-bold text-rose-400">{{ number_format($stats['credit_issues']) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-rose-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-panel p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
            <div>
                <div class="text-lg font-semibold">Branch Clients</div>
                <p class="text-sm muted">View and manage clients for {{ $branch->name }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="relative">
                    <input type="text" id="clientSearch" value="{{ $search }}" placeholder="Search clients..." autocomplete="off"
                        class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-64">
                    <div id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
                <select id="statusFilter" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <option value="active" @selected($statusFilter === 'active')>Active</option>
                    <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
                    <option value="suspended" @selected($statusFilter === 'suspended')>Suspended</option>
                    <option value="blacklisted" @selected($statusFilter === 'blacklisted')>Blacklisted</option>
                </select>
                <select id="typeFilter" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="vip" @selected($typeFilter === 'vip')>VIP</option>
                    <option value="regular" @selected($typeFilter === 'regular')>Regular</option>
                    <option value="prospect" @selected($typeFilter === 'prospect')>Prospect</option>
                </select>
                @if($search || $statusFilter || $typeFilter)
                    <button type="button" onclick="clearFilters()" class="chip bg-slate-700">Clear</button>
                @endif
            </div>
        </div>

        <div id="bulkActionsBar" class="hidden bg-zinc-800/50 border border-white/10 rounded-lg p-3 mb-4">
            <form method="POST" action="{{ route('branch.clients.bulk-action') }}" class="flex flex-wrap items-center gap-3">
                @csrf
                <span class="text-sm"><span id="selectedCount">0</span> selected</span>
                <select name="action" required class="bg-obsidian-700 border border-white/10 rounded px-3 py-1.5 text-sm">
                    <option value="">Select Action</option>
                    <option value="activate">Activate</option>
                    <option value="suspend">Suspend</option>
                    <option value="deactivate">Deactivate</option>
                </select>
                <div id="selectedClientIds"></div>
                <button type="submit" class="chip bg-emerald-600 hover:bg-emerald-700">Apply</button>
                <button type="button" onclick="clearSelection()" class="chip bg-zinc-700">Cancel</button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-white/10">
            <table class="w-full">
                <thead class="bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase text-zinc-400">
                        <th class="w-12 px-4 py-3">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="rounded bg-zinc-700 border-zinc-600 text-emerald-500 focus:ring-emerald-500">
                        </th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Activity</th>
                        <th class="px-4 py-3 font-medium">Credit</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="clientsTableBody" class="divide-y divide-white/5">
                    @include('branch.clients._table', ['customers' => $customers])
                </tbody>
            </table>
        </div>

        <div id="paginationContainer" class="mt-4">
            @include('branch.clients._pagination', ['customers' => $customers, 'perPage' => $perPage ?? 10])
        </div>
    </div>
@endsection

@push('scripts')
<script>
let searchDebounce = null;
let currentPerPage = {{ $perPage ?? 10 }};

function performSearch() {
    const search = document.getElementById('clientSearch').value;
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (type) params.set('customer_type', type);
    params.set('per_page', currentPerPage);
    
    document.getElementById('searchSpinner').classList.remove('hidden');
    
    fetch(`{{ route('branch.clients.index') }}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('clientsTableBody').innerHTML = data.html;
        document.getElementById('paginationContainer').innerHTML = data.pagination;
        window.history.replaceState({}, '', `{{ route('branch.clients.index') }}?${params.toString()}`);
    })
    .catch(err => console.error('Search error:', err))
    .finally(() => {
        document.getElementById('searchSpinner').classList.add('hidden');
    });
}

document.getElementById('clientSearch').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(performSearch, 300);
});

['statusFilter', 'typeFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', performSearch);
});

function changePerPage(value) {
    currentPerPage = parseInt(value);
    performSearch();
}

function clearFilters() {
    document.getElementById('clientSearch').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value = '';
    performSearch();
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.client-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.client-checkbox:checked');
    const count = checkboxes.length;
    const bar = document.getElementById('bulkActionsBar');
    const countEl = document.getElementById('selectedCount');
    const idsContainer = document.getElementById('selectedClientIds');
    
    if (count > 0) {
        bar.classList.remove('hidden');
        countEl.textContent = count;
        idsContainer.innerHTML = Array.from(checkboxes).map(cb => 
            `<input type="hidden" name="client_ids[]" value="${cb.value}">`
        ).join('');
    } else {
        bar.classList.add('hidden');
    }
}

function clearSelection() {
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.client-checkbox').forEach(cb => cb.checked = false);
    updateSelection();
}

function toggleDropdown(button) {
    const container = button.closest('.dropdown-container');
    const menu = container.querySelector('.dropdown-menu');
    const isOpen = !menu.classList.contains('hidden');
    
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        m.classList.add('hidden');
    });
    
    if (!isOpen) {
        menu.classList.remove('hidden');
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-container')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});
</script>
@endpush
