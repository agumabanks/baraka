@extends('admin.layout')

@section('title', 'User Management')
@section('header', 'User Management')

@section('content')
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <div class="muted text-xs uppercase">Total Users</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="stat-card border-emerald-500/30">
            <div class="muted text-xs uppercase">Active Users</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="stat-card border-sky-500/30">
            <div class="muted text-xs uppercase">Branch Managers</div>
            <div class="text-2xl font-bold text-sky-400">{{ number_format($stats['branch_managers']) }}</div>
        </div>
        <div class="stat-card border-amber-500/30">
            <div class="muted text-xs uppercase">Branch Workers</div>
            <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['branch_workers']) }}</div>
        </div>
    </div>

    <div class="glass-panel p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold">All Users</h2>
                <span class="pill-soft">{{ $users->total() }}</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap justify-end">
                <a href="{{ route('admin.users.branch-managers') }}" class="chip hover:bg-white/10 transition">Branch Managers</a>
                <a href="{{ route('admin.users.impersonation-logs') }}" class="chip hover:bg-white/10 transition">Logs</a>
                <a href="{{ route('admin.branches.create') }}" class="btn btn-sm btn-secondary">Add Branch</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">Add User</a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" placeholder="Search name, email..." value="{{ $search }}" 
                   class="bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50 text-white">
            
            <select name="branch" class="bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50 text-white">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected($branch == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>

            <select name="role" class="bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50 text-white">
                <option value="">All Roles</option>
                <option value="admin" @selected($role == 'admin')>Admin</option>
                <option value="branch_manager" @selected($role == 'branch_manager')>Branch Manager</option>
                <option value="user" @selected($role == 'user')>User</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition flex-1">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white text-sm font-medium transition">Clear</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="dhl-table text-left w-full">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Branch</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td data-label="User">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center text-xs font-bold">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-white">{{ $user->name }}</div>
                                        <div class="text-xs muted">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Branch">
                                @if($user->branchWorker)
                                    <span class="badge bg-sky-500/20 text-sky-300 border border-sky-500/30">{{ $user->branchWorker->branch->name ?? 'N/A' }}</span>
                                @elseif($user->branchManager)
                                    <span class="badge bg-purple-500/20 text-purple-300 border border-purple-500/30">{{ $user->branchManager->branch->name ?? 'N/A' }}</span>
                                @elseif($user->primary_branch_id)
                                    <span class="badge bg-slate-700 text-slate-300">Branch #{{ $user->primary_branch_id }}</span>
                                @else
                                    <span class="text-xs muted">System</span>
                                @endif
                            </td>
                            <td data-label="Role">
                                <div class="flex flex-wrap gap-1">
                                    @if($user->role)
                                        <span class="badge bg-white/10 text-slate-300">{{ $user->role->name }}</span>
                                    @else
                                        <span class="badge bg-white/5 text-slate-500">No Role</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Status">
                                @if($user->status == 1)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-xs muted" data-label="Last Login">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="text-right" data-label="Actions">
                                @if(auth()->id() !== $user->id)
                                    <button onclick="openImpersonateModal({{ $user->id }}, '{{ addslashes($user->name) }}')" 
                                            class="text-xs bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/30 px-2 py-1 rounded transition">
                                        Login As
                                    </button>
                                @else
                                    <span class="text-xs muted">You</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 muted">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
            <div class="pt-4 border-t border-white/5">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Impersonation Modal -->
    <div id="impersonationModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeImpersonateModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-obsidian-800 border border-white/10 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="impersonationForm" method="POST">
                        @csrf
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-base font-semibold leading-6 text-white" id="modal-title">Login As User</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-slate-300">
                                            You are about to impersonate <strong id="targetUserName" class="text-white"></strong>. 
                                            All actions performed during this session will be logged in the audit trail.
                                        </p>
                                        <div class="mt-4">
                                            <label for="reason" class="block text-sm font-medium text-slate-300">Reason (Optional)</label>
                                            <textarea name="reason" id="reason" rows="3" class="mt-1 block w-full rounded-md bg-obsidian-900 border-white/10 text-white shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" placeholder="Why are you logging in as this user?"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-obsidian-900/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 sm:ml-3 sm:w-auto">Login As User</button>
                            <button type="button" onclick="closeImpersonateModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white/5 px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-white/10 hover:bg-white/10 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openImpersonateModal(userId, userName) {
            document.getElementById('targetUserName').textContent = userName;
            document.getElementById('impersonationForm').action = `/admin/users/${userId}/impersonate`;
            document.getElementById('impersonationModal').classList.remove('hidden');
        }

        function closeImpersonateModal() {
            document.getElementById('impersonationModal').classList.add('hidden');
        }
    </script>
@endsection
