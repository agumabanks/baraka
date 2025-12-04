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
            @if(auth()->id() !== $user->id)
                <button onclick="openImpersonateModal({{ $user->id }}, '{{ addslashes($user->name) }}')" 
                        class="text-xs bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/30 px-2 py-1 rounded transition">
                    Login As
                </button>
            @else
                <span class="text-xs text-zinc-500">You</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No users found.</td>
    </tr>
@endforelse
