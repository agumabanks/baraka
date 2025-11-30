@extends('admin.layout')

@section('title', 'Branch Managers')
@section('header', 'Branch Managers')

@section('content')
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card border-emerald-500/30">
            <div class="muted text-xs uppercase">Total Managers</div>
            <div class="text-2xl font-bold text-emerald-400">{{ $managers->total() }}</div>
        </div>
        <div class="stat-card border-sky-500/30">
            <div class="muted text-xs uppercase">Total Branches</div>
            <div class="text-2xl font-bold text-sky-400">{{ $branches->count() }}</div>
        </div>
    </div>

    <div class="glass-panel p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-lg font-semibold">Branch Managers</h2>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.users.branch-managers') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" placeholder="Search name, email..." value="{{ $search }}" 
                   class="bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50 text-white">
            
            <select name="branch" class="bg-obsidian-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500/50 text-white">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($branchFilter == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition flex-1">Filter</button>
                <a href="{{ route('admin.users.branch-managers') }}" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white text-sm font-medium transition">Clear</a>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($managers as $manager)
                <div class="glass-panel p-4 flex flex-col gap-4 hover:border-white/20 transition">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg font-bold shrink-0">
                            {{ strtoupper(substr($manager->user->name ?? 'N/A', 0, 2)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold truncate">{{ $manager->user->name ?? 'N/A' }}</div>
                            <div class="text-xs muted truncate">{{ $manager->user->email ?? 'N/A' }}</div>
                            @if($manager->user->mobile)
                                <div class="text-xs muted truncate">{{ $manager->user->mobile }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="muted">Branch</span>
                            <span class="badge bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $manager->branch->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="muted">Last Login</span>
                            <span class="text-slate-300">{{ $manager->user->last_login_at ? $manager->user->last_login_at->diffForHumans() : 'Never' }}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-white/5 flex justify-end">
                        @if(auth()->id() !== $manager->user_id)
                            <button onclick="openImpersonateModal({{ $manager->user_id }}, '{{ addslashes($manager->user->name ?? '') }}')" 
                                    class="text-xs bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/30 px-3 py-1.5 rounded transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                Login As
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center muted">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <p>No branch managers found.</p>
                </div>
            @endforelse
        </div>

        @if($managers->hasPages())
            <div class="pt-4 border-t border-white/5">
                {{ $managers->links() }}
            </div>
        @endif
    </div>

    <!-- Reusing the modal from index or including a partial would be better, but for now duplicating the modal structure for simplicity as per instructions to keep it simple -->
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
                                    <h3 class="text-base font-semibold leading-6 text-white" id="modal-title">Login As Manager</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-slate-300">
                                            You are about to impersonate <strong id="targetUserName" class="text-white"></strong>. 
                                            All actions performed during this session will be logged.
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
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 sm:ml-3 sm:w-auto">Login As Manager</button>
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
