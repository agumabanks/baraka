@forelse($workers as $worker)
    <tr class="hover:bg-white/[0.02] transition-colors group">
        <td class="px-4 py-3">
            <input type="checkbox" class="worker-checkbox rounded bg-zinc-700 border-zinc-600 text-emerald-500 focus:ring-emerald-500" 
                value="{{ $worker->id }}" onchange="updateSelection()">
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center gap-3">
                @php
                    $colors = ['from-emerald-500 to-teal-600', 'from-blue-500 to-indigo-600', 'from-purple-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-red-600'];
                    $colorIndex = crc32($worker->user?->email ?? '') % count($colors);
                @endphp
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $colors[$colorIndex] }} flex items-center justify-center text-sm font-bold text-white shadow-lg">
                    {{ strtoupper(substr($worker->user?->name ?? 'U', 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <a href="{{ route('branch.workforce.show', $worker) }}" class="font-medium text-white hover:text-emerald-400 transition-colors">
                        {{ $worker->user?->name ?? 'Unknown' }}
                    </a>
                    <div class="text-xs text-zinc-400">{{ $worker->user?->email }}</div>
                    @if($worker->user?->mobile)
                        <div class="text-xs text-zinc-500">{{ $worker->user?->mobile }}</div>
                    @endif
                </div>
            </div>
        </td>
        <td class="px-4 py-3">
            @php
                $roleColors = [
                    'BRANCH_MANAGER' => 'bg-purple-500/10 text-purple-400 ring-purple-500/20',
                    'OPS_SUPERVISOR' => 'bg-blue-500/10 text-blue-400 ring-blue-500/20',
                    'DRIVER' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
                    'COURIER' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
                    'DISPATCHER' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
                ];
                $roleValue = $worker->role?->value ?? $worker->role;
                $roleColor = $roleColors[$roleValue] ?? 'bg-zinc-500/10 text-zinc-400 ring-zinc-500/20';
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $roleColor }}">
                {{ $worker->role?->label() ?? $worker->role }}
            </span>
        </td>
        <td class="px-4 py-3">
            @if($worker->status === \App\Enums\Status::ACTIVE && !$worker->unassigned_at)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Active
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-500/10 text-zinc-400 ring-1 ring-inset ring-zinc-500/20">
                    Inactive
                </span>
            @endif
            <div class="text-2xs text-zinc-500 mt-1">{{ $worker->employment_status?->label() ?? $worker->employment_status }}</div>
        </td>
        <td class="px-4 py-3">
            @php
                $activeShipments = $worker->assignedShipments?->whereIn('current_status', ['PICKUP_SCHEDULED', 'OUT_FOR_DELIVERY', 'IN_TRANSIT'])->count() ?? 0;
                $capacityPercent = min(100, ($activeShipments / 10) * 100);
                $capacityColor = $capacityPercent > 80 ? 'bg-rose-500' : ($capacityPercent > 50 ? 'bg-amber-500' : 'bg-emerald-500');
            @endphp
            <div class="space-y-1">
                <div class="text-sm">
                    <span class="font-semibold text-white">{{ $activeShipments }}</span>
                    <span class="text-zinc-500">active</span>
                </div>
                <div class="w-20 h-1.5 bg-zinc-700 rounded-full overflow-hidden">
                    <div class="h-full {{ $capacityColor }} rounded-full transition-all" style="width: {{ $capacityPercent }}%"></div>
                </div>
            </div>
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1 opacity-50 group-hover:opacity-100 transition-opacity">
                <a href="{{ route('branch.workforce.show', $worker) }}" class="p-2 rounded-lg hover:bg-white/10 transition-colors text-zinc-400 hover:text-white" title="View Details">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </a>
                <a href="{{ route('branch.workforce.edit', $worker) }}" class="p-2 rounded-lg hover:bg-blue-500/20 transition-colors text-blue-500" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
                <form method="POST" action="{{ route('branch.workforce.archive', $worker) }}" class="inline" onsubmit="return confirm('Archive this worker?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-lg hover:bg-rose-500/20 transition-colors text-rose-500" title="Archive">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-4 py-16 text-center">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-zinc-400 mb-1">No workers found</p>
                <p class="text-zinc-600 text-sm">Onboard your first team member to get started</p>
            </div>
        </td>
    </tr>
@endforelse
