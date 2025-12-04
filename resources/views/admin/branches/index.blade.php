@extends('admin.layout')

@section('title', 'Branches')
@section('header', 'Branch Management')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div class="text-sm muted">Manage branch and hub records across the network.</div>
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Add Branch</a>
    </div>

    {{-- Stats --}}
    <div class="grid gap-3 md:grid-cols-3 mb-6">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Branches</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Active</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="stat-card">
            <div class="muted text-xs uppercase">Hubs</div>
            <div class="text-2xl font-bold text-purple-400">{{ number_format($stats['hubs']) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-panel p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" id="branchSearch" placeholder="Search branches..." autocomplete="off"
                    class="w-full bg-white/5 border border-white/10 rounded px-3 py-2 text-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                <div id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            <select id="typeFilter" class="bg-white/5 border border-white/10 rounded px-3 py-2 text-sm min-w-[140px]">
                <option value="">All Types</option>
                <option value="hub">Hub</option>
                <option value="branch">Branch</option>
                <option value="regional">Regional</option>
            </select>
            <select id="statusFilter" class="bg-white/5 border border-white/10 rounded px-3 py-2 text-sm min-w-[140px]">
                <option value="">All Statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button type="button" onclick="clearFilters()" class="btn btn-sm btn-secondary">Clear</button>
        </div>
    </div>

    {{-- Branch List --}}
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="text-sm font-semibold">All Branches</div>
            <a href="{{ route('admin.branches.create') }}" class="btn btn-sm btn-primary">Add Branch</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="branchesTableBody" class="divide-y divide-white/5">
                    @include('admin.branches._table', ['branches' => $branches])
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="paginationContainer">
            @include('admin.branches._pagination', ['branches' => $branches, 'perPage' => $perPage ?? 10])
        </div>
    </div>
@endsection

@push('scripts')
<script>
let searchDebounce = null;
let currentPerPage = {{ $perPage ?? 10 }};

// AJAX Search and Filter
function performSearch() {
    const search = document.getElementById('branchSearch').value;
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (type) params.set('type', type);
    if (status !== '') params.set('status', status);
    params.set('per_page', currentPerPage);
    
    // Show spinner
    document.getElementById('searchSpinner').classList.remove('hidden');
    
    fetch(`{{ route('admin.branches.index') }}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('branchesTableBody').innerHTML = data.html;
        document.getElementById('paginationContainer').innerHTML = data.pagination;
        // Update URL without reload
        window.history.replaceState({}, '', `{{ route('admin.branches.index') }}?${params.toString()}`);
    })
    .catch(err => console.error('Search error:', err))
    .finally(() => {
        document.getElementById('searchSpinner').classList.add('hidden');
    });
}

// Debounced search on input
document.getElementById('branchSearch').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(performSearch, 300);
});

// Instant filter on select change
['typeFilter', 'statusFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', performSearch);
});

// Change per page
function changePerPage(value) {
    currentPerPage = parseInt(value);
    performSearch();
}

// Clear all filters
function clearFilters() {
    document.getElementById('branchSearch').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    performSearch();
}
</script>
@endpush
