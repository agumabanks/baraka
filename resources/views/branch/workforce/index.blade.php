@extends('branch.layout')

@section('title', 'Workforce Management')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Workforce Management</h1>
        <p class="text-sm muted">Manage team members, roles, and scheduling for {{ $branch->name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('branch.workforce.schedule') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Schedule View
        </a>
        <a href="{{ route('branch.workforce.export', request()->query()) }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export
        </a>
        <button type="button" onclick="document.getElementById('onboardModal').classList.remove('hidden')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Onboard Staff
        </button>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-4 mb-6">
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Total Staff</div>
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
                <div class="text-2xs uppercase muted mb-1">On Duty Today</div>
                <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['on_duty']) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Drivers/Couriers</div>
                <div class="text-2xl font-bold text-purple-400">{{ number_format($stats['drivers']) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="glass-panel p-5">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
        <div>
            <div class="text-lg font-semibold">Team Roster</div>
            <p class="text-sm muted">Manage roles, status, and view performance</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <div class="relative">
                <input type="text" id="workerSearch" value="{{ $search }}" placeholder="Search workers..." autocomplete="off"
                    class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-64">
                <div id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            <select id="roleFilter" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                <option value="">All Roles</option>
                @foreach($roleOptions as $role)
                    <option value="{{ $role['value'] }}" @selected($roleFilter === $role['value'])>{{ $role['label'] }}</option>
                @endforeach
            </select>
            <select id="statusFilter" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="active" @selected($statusFilter === 'active')>Active</option>
                <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
            </select>
            @if($search || $roleFilter || $statusFilter)
                <button type="button" onclick="clearFilters()" class="chip bg-slate-700">Clear</button>
            @endif
        </div>
    </div>

    <div id="bulkActionsBar" class="hidden bg-zinc-800/50 border border-white/10 rounded-lg p-3 mb-4">
        <form method="POST" action="{{ route('branch.workforce.bulk-action') }}" class="flex flex-wrap items-center gap-3">
            @csrf
            <span class="text-sm"><span id="selectedCount">0</span> selected</span>
            <select name="action" id="bulkAction" required class="bg-obsidian-700 border border-white/10 rounded px-3 py-1.5 text-sm">
                <option value="">Select Action</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="change_role">Change Role</option>
            </select>
            <select name="role" id="bulkRoleSelect" class="bg-obsidian-700 border border-white/10 rounded px-3 py-1.5 text-sm hidden">
                @foreach($roleOptions as $role)
                    <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                @endforeach
            </select>
            <div id="selectedWorkerIds"></div>
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
                    <th class="px-4 py-3 font-medium">Worker</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Workload</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="workersTableBody" class="divide-y divide-white/5">
                @include('branch.workforce._table', ['workers' => $workers])
            </tbody>
        </table>
    </div>

    <div id="paginationContainer" class="mt-4">
        @include('branch.workforce._pagination', ['workers' => $workers, 'perPage' => $perPage ?? 12])
    </div>
</div>

{{-- Onboard Modal --}}
<div id="onboardModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('onboardModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="glass-panel w-full max-w-lg">
            <form method="POST" action="{{ route('branch.workforce.store') }}">
                @csrf
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Onboard New Staff</h3>
                            <p class="text-sm muted">Add a new team member to {{ $branch->name }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium mb-1">Full Name <span class="text-rose-400">*</span></label>
                                <input type="text" name="name" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="John Doe">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Email <span class="text-rose-400">*</span></label>
                                <input type="email" name="email" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="john@example.com">
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium mb-1">Mobile</label>
                                <input type="text" name="mobile" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="+1234567890">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">ID Number</label>
                                <input type="text" name="id_number" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="National ID">
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium mb-1">Role <span class="text-rose-400">*</span></label>
                                <select name="role" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                                    @foreach($roleOptions as $role)
                                        <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Employment Status</label>
                                <select name="employment_status" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                                    @foreach($statusOptions as $status)
                                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Hourly Rate</label>
                            <input type="number" name="hourly_rate" step="0.01" min="0" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="Any additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('onboardModal').classList.add('hidden')" class="chip bg-zinc-700">Cancel</button>
                    <button type="submit" class="chip bg-emerald-600 hover:bg-emerald-700">Onboard Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show success toast
});
</script>
@endif
@endsection

@push('scripts')
<script>
let searchDebounce = null;
let currentPerPage = {{ $perPage ?? 12 }};

function performSearch() {
    const search = document.getElementById('workerSearch').value;
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (role) params.set('role', role);
    if (status) params.set('status', status);
    params.set('per_page', currentPerPage);
    
    document.getElementById('searchSpinner').classList.remove('hidden');
    
    fetch(`{{ route('branch.workforce') }}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('workersTableBody').innerHTML = data.html;
        document.getElementById('paginationContainer').innerHTML = data.pagination;
        window.history.replaceState({}, '', `{{ route('branch.workforce') }}?${params.toString()}`);
    })
    .catch(err => console.error('Search error:', err))
    .finally(() => {
        document.getElementById('searchSpinner').classList.add('hidden');
    });
}

document.getElementById('workerSearch').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(performSearch, 300);
});

['roleFilter', 'statusFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', performSearch);
});

document.getElementById('bulkAction').addEventListener('change', function() {
    document.getElementById('bulkRoleSelect').classList.toggle('hidden', this.value !== 'change_role');
});

function changePerPage(value) {
    currentPerPage = parseInt(value);
    performSearch();
}

function clearFilters() {
    document.getElementById('workerSearch').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    performSearch();
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = selectAll.checked);
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.worker-checkbox:checked');
    const count = checkboxes.length;
    const bar = document.getElementById('bulkActionsBar');
    
    if (count > 0) {
        bar.classList.remove('hidden');
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('selectedWorkerIds').innerHTML = Array.from(checkboxes)
            .map(cb => `<input type="hidden" name="worker_ids[]" value="${cb.value}">`)
            .join('');
    } else {
        bar.classList.add('hidden');
    }
}

function clearSelection() {
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = false);
    updateSelection();
}
</script>
@endpush
