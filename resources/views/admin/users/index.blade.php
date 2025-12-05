@extends('admin.layout')

@section('title', 'User Management')
@section('header', 'User Management')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
        <div class="glass-panel p-4 border-l-4 border-zinc-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Total Users</div>
            <div class="text-2xl font-bold text-white mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-emerald-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Active Users</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-sky-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Branch Managers</div>
            <div class="text-2xl font-bold text-sky-400 mt-1">{{ number_format($stats['branch_managers']) }}</div>
        </div>
        <div class="glass-panel p-4 border-l-4 border-amber-500">
            <div class="text-xs text-zinc-400 uppercase tracking-wider">Branch Workers</div>
            <div class="text-2xl font-bold text-amber-400 mt-1">{{ number_format($stats['branch_workers']) }}</div>
        </div>
    </div>

    {{-- Main Panel --}}
    <div class="glass-panel">
        {{-- Header --}}
        <div class="p-4 border-b border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold">All Users</h2>
                <span class="px-2 py-0.5 text-xs bg-zinc-700 rounded-full" id="totalCount">{{ $users->total() }}</span>
                <div id="loadingSpinner" class="hidden">
                    <div class="w-4 h-4 border-2 border-zinc-600 border-t-sky-500 rounded-full animate-spin"></div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.users.branch-managers') }}" class="btn btn-xs btn-secondary">Branch Managers</a>
                <a href="{{ route('admin.users.impersonation-logs') }}" class="btn btn-xs btn-secondary">Logs</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add User
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="p-4 border-b border-white/10 bg-white/5">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2 relative">
                    <input type="text" id="searchInput" placeholder="Search name, email, phone..." value="{{ $search }}"
                           class="w-full bg-white/5 border border-white/10 rounded-lg pl-9 pr-4 py-2 text-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                    <svg class="w-4 h-4 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="branchFilter" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-sky-500">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select id="roleFilter" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-sky-500">
                    <option value="">All Roles</option>
                    <option value="admin" {{ $role == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="branch_manager" {{ $role == 'branch_manager' ? 'selected' : '' }}>Branch Manager</option>
                    <option value="user" {{ $role == 'user' ? 'selected' : '' }}>User</option>
                </select>
                <select id="statusFilter" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-sky-500">
                    <option value="">All Status</option>
                    <option value="1" {{ $status === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ $status === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto overflow-y-visible">
            <table class="w-full">
                <thead class="bg-white/5 text-xs uppercase text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Branch</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Last Login</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    @include('admin.users._table')
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-white/10" id="paginationContainer">
            @include('admin.users._pagination', ['perPage' => $perPage ?? 10])
        </div>
    </div>
</div>

{{-- Impersonation Modal --}}
<div id="impersonationModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeImpersonateModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="glass-panel w-full max-w-lg">
            <form id="impersonationForm" method="POST">
                @csrf
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold">Login As User</h3>
                            <p class="text-sm text-zinc-400 mt-1">
                                You are about to impersonate <strong id="targetUserName" class="text-white"></strong>. 
                                All actions will be logged.
                            </p>
                            <div class="mt-4">
                                <label class="block text-sm text-zinc-400 mb-1">Reason (Optional)</label>
                                <textarea name="reason" rows="2" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm" placeholder="Why are you logging in as this user?"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                    <button type="button" onclick="closeImpersonateModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn bg-amber-600 hover:bg-amber-500 text-white">Login As User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let perPage = {{ $perPage ?? 10 }};
let searchTimeout;

document.addEventListener('DOMContentLoaded', function() {
    // Live search with debounce
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchUsers();
        }, 300);
    });

    // Filter changes
    ['branchFilter', 'roleFilter', 'statusFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            currentPage = 1;
            fetchUsers();
        });
    });
});

function fetchUsers() {
    const params = new URLSearchParams({
        q: document.getElementById('searchInput').value,
        branch: document.getElementById('branchFilter').value,
        role: document.getElementById('roleFilter').value,
        status: document.getElementById('statusFilter').value,
        per_page: perPage,
        page: currentPage
    });

    // Show loading
    document.getElementById('loadingSpinner').classList.remove('hidden');

    fetch(`{{ route('admin.users.index') }}?${params}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('usersTableBody').innerHTML = data.html;
        document.getElementById('paginationContainer').innerHTML = data.pagination;
        document.getElementById('totalCount').textContent = data.total;
        
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('q', document.getElementById('searchInput').value);
        url.searchParams.set('branch', document.getElementById('branchFilter').value);
        url.searchParams.set('role', document.getElementById('roleFilter').value);
        url.searchParams.set('status', document.getElementById('statusFilter').value);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('page', currentPage);
        window.history.replaceState({}, '', url);
    })
    .catch(error => console.error('Error:', error))
    .finally(() => {
        document.getElementById('loadingSpinner').classList.add('hidden');
    });
}

function goToPage(page) {
    currentPage = page;
    fetchUsers();
}

function changePerPage(value) {
    perPage = parseInt(value);
    currentPage = 1;
    fetchUsers();
}

function openImpersonateModal(userId, userName) {
    document.getElementById('targetUserName').textContent = userName;
    document.getElementById('impersonationForm').action = `/admin/users/${userId}/impersonate`;
    document.getElementById('impersonationModal').classList.remove('hidden');
}

function closeImpersonateModal() {
    document.getElementById('impersonationModal').classList.add('hidden');
}
</script>
@endpush
