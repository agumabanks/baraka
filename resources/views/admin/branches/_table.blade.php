@forelse($branches as $branch)
    <tr class="hover:bg-white/5">
        <td class="px-4 py-3">
            <a href="{{ route('admin.branches.show', $branch) }}" class="font-semibold text-sky-400 hover:text-sky-300">
                {{ $branch->name }}
            </a>
            @if($branch->parent)
                <div class="text-xs text-zinc-500">Parent: {{ $branch->parent->name }}</div>
            @endif
        </td>
        <td class="px-4 py-3">
            <span class="font-mono text-sm text-zinc-400">{{ $branch->code ?? $branch->branch_code }}</span>
        </td>
        <td class="px-4 py-3">
            @php
                $type = $branch->type ?? ($branch->is_hub ? 'hub' : 'branch');
                $typeStyles = [
                    'hub' => 'bg-purple-500/20 text-purple-400',
                    'regional' => 'bg-blue-500/20 text-blue-400',
                    'branch' => 'bg-zinc-500/20 text-zinc-400',
                ];
            @endphp
            <span class="px-2 py-1 text-xs rounded-full {{ $typeStyles[$type] ?? $typeStyles['branch'] }}">
                {{ ucfirst($type) }}
            </span>
        </td>
        <td class="px-4 py-3">
            <div class="text-sm text-zinc-400 max-w-xs truncate">{{ $branch->address ?? '-' }}</div>
            @if($branch->phone)
                <div class="text-xs text-zinc-500">{{ $branch->phone }}</div>
            @endif
        </td>
        <td class="px-4 py-3">
            @if($branch->status)
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Active
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-zinc-500/20 text-zinc-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-500"></span>
                    Inactive
                </span>
            @endif
        </td>
        <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.branches.show', $branch) }}" class="p-1.5 rounded hover:bg-white/10 text-zinc-400 hover:text-white" title="View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </a>
                <a href="{{ route('admin.branches.edit', $branch) }}" class="p-1.5 rounded hover:bg-white/10 text-amber-400 hover:text-amber-300" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-4 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <div class="text-zinc-400 mb-2">No branches found</div>
            <a href="{{ route('admin.branches.create') }}" class="text-sky-400 hover:text-sky-300 text-sm">Add your first branch</a>
        </td>
    </tr>
@endforelse
