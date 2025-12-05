@forelse($users as $user)
    <tr class="border-b border-white/5 hover:bg-white/5 transition">
        <td class="px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center text-xs font-bold">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <div class="font-medium text-white">{{ $user->name }}</div>
                    <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                </div>
            </div>
        </td>
        <td class="px-4 py-3">
            @if($user->branchWorker)
                <span class="px-2 py-0.5 text-xs rounded-full bg-sky-500/20 text-sky-400">{{ $user->branchWorker->branch->name ?? 'N/A' }}</span>
            @elseif($user->branchManager)
                <span class="px-2 py-0.5 text-xs rounded-full bg-purple-500/20 text-purple-400">{{ $user->branchManager->branch->name ?? 'N/A' }}</span>
            @elseif($user->primary_branch_id)
                <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-500/20 text-zinc-400">Branch #{{ $user->primary_branch_id }}</span>
            @else
                <span class="text-xs text-zinc-500">System</span>
            @endif
        </td>
        <td class="px-4 py-3">
            @if($user->role)
                <span class="px-2 py-0.5 text-xs rounded-full bg-white/10 text-zinc-300">{{ $user->role->name }}</span>
            @else
                <span class="px-2 py-0.5 text-xs rounded-full bg-white/5 text-zinc-500">No Role</span>
            @endif
        </td>
        <td class="px-4 py-3">
            @if($user->status == 1)
                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-400">Active</span>
            @else
                <span class="px-2 py-0.5 text-xs rounded-full bg-red-500/20 text-red-400">Inactive</span>
            @endif
        </td>
        <td class="px-4 py-3 text-xs text-zinc-500">
            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
        </td>
        <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs bg-white/5 hover:bg-white/10 border border-white/10 px-2 py-1 rounded transition" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                @if(auth()->id() !== $user->id)
                    <button onclick="openImpersonateModal({{ $user->id }}, '{{ addslashes($user->name) }}')" class="text-xs bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/30 px-2 py-1 rounded transition" title="Login As">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </button>
                @endif
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="text-xs bg-white/5 hover:bg-white/10 border border-white/10 px-2 py-1 rounded transition" title="More Actions">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="fixed z-[9999] w-48 bg-obsidian-800 border border-white/10 rounded-lg shadow-2xl"
                         x-init="$watch('open', value => { if(value) { const btn = $el.previousElementSibling; const rect = btn.getBoundingClientRect(); $el.style.top = (rect.bottom + 4) + 'px'; $el.style.left = (rect.right - 192) + 'px'; } })">
                        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" onsubmit="return confirm('Reset password for {{ addslashes($user->name) }}? A new random password will be generated.')">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-sky-400 hover:bg-white/5 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Reset Password
                            </button>
                        </form>
                        @if(auth()->id() !== $user->id)
                            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm {{ $user->status ? 'text-orange-400' : 'text-emerald-400' }} hover:bg-white/5 transition flex items-center gap-2">
                                    @if($user->status)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Deactivate User
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Activate User
                                    @endif
                                </button>
                            </form>
                            <div class="border-t border-white/10"></div>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($user->name) }}? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete User
                                </button>
                            </form>
                        @else
                            <div class="px-4 py-2 text-xs text-zinc-500 bg-white/5">
                                This is your account
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No users found.</td>
    </tr>
@endforelse
