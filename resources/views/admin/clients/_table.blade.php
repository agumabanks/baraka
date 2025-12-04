@forelse($customers as $customer)
    <tr class="hover:bg-white/[0.02] transition-colors group">
        <td class="px-4 py-3">
            <input type="checkbox" class="client-checkbox rounded bg-zinc-700 border-zinc-600 text-emerald-500 focus:ring-emerald-500" 
                value="{{ $customer->id }}" onchange="updateSelection()">
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center gap-3">
                @php
                    $colors = ['from-emerald-500 to-teal-600', 'from-blue-500 to-indigo-600', 'from-purple-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-red-600'];
                    $colorIndex = crc32($customer->customer_code ?? '') % count($colors);
                @endphp
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $colors[$colorIndex] }} flex items-center justify-center text-sm font-bold text-white shadow-lg">
                    {{ strtoupper(substr($customer->contact_person ?: $customer->company_name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="font-medium text-white truncate max-w-[200px]">{{ $customer->contact_person ?: $customer->company_name }}</div>
                    @if($customer->phone)
                        <div class="text-xs text-zinc-400">{{ $customer->phone }}</div>
                    @endif
                    @if($customer->company_name && $customer->contact_person)
                        <div class="text-xs text-zinc-500 truncate max-w-[200px]">{{ $customer->company_name }}</div>
                    @endif
                </div>
            </div>
        </td>
        <td class="px-4 py-3">
            @if($customer->primaryBranch)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-zinc-800 text-xs font-medium text-zinc-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    {{ $customer->primaryBranch->name }}
                </span>
            @else
                <span class="text-xs text-zinc-600">Unassigned</span>
            @endif
        </td>
        <td class="px-4 py-3">
            @php
                $statusStyles = [
                    'active' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
                    'inactive' => 'bg-zinc-500/10 text-zinc-400 ring-zinc-500/20',
                    'suspended' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
                    'blacklisted' => 'bg-rose-500/10 text-rose-400 ring-rose-500/20',
                ];
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusStyles[$customer->status] ?? $statusStyles['inactive'] }}">
                {{ ucfirst($customer->status ?? 'unknown') }}
            </span>
        </td>
        <td class="px-4 py-3">
            @php
                $typeStyles = [
                    'vip' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
                    'regular' => 'bg-blue-500/10 text-blue-400 ring-blue-500/20',
                    'prospect' => 'bg-purple-500/10 text-purple-400 ring-purple-500/20',
                ];
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $typeStyles[$customer->customer_type] ?? $typeStyles['regular'] }}">
                {{ ucfirst($customer->customer_type ?? 'regular') }}
            </span>
        </td>
        <td class="px-4 py-3">
            <div class="text-sm">
                <span class="font-semibold text-white">{{ $customer->shipments_count }}</span>
                <span class="text-zinc-500">shipments</span>
            </div>
            <div class="text-xs text-zinc-500">{{ $customer->invoices_count }} invoices</div>
        </td>
        <td class="px-4 py-3">
            @if($customer->credit_limit > 0)
                @php
                    $creditUsed = $customer->current_balance ?? 0;
                    $utilization = $customer->credit_limit > 0 ? min(100, ($creditUsed / $customer->credit_limit) * 100) : 0;
                    $utilizationColor = $utilization > 90 ? 'bg-rose-500' : ($utilization > 70 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="space-y-1">
                    <div class="text-xs">
                        <span class="text-white font-medium">${{ number_format($creditUsed / 1000, 1) }}K</span>
                        <span class="text-zinc-500">/ ${{ number_format($customer->credit_limit / 1000, 1) }}K</span>
                    </div>
                    <div class="w-20 h-1.5 bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $utilizationColor }} rounded-full transition-all" style="width: {{ $utilization }}%"></div>
                    </div>
                </div>
            @else
                <span class="text-xs text-zinc-600">No credit</span>
            @endif
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1 opacity-50 group-hover:opacity-100 transition-opacity">
                <a href="{{ route('admin.clients.show', $customer) }}" class="p-2 rounded-lg hover:bg-white/10 transition-colors text-zinc-400 hover:text-white" title="View Details">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </a>
                <a href="{{ route('admin.clients.edit', $customer) }}" class="p-2 rounded-lg hover:bg-blue-500/20 transition-colors text-blue-500" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
                <a href="{{ route('admin.clients.quick-shipment', $customer) }}" class="p-2 rounded-lg hover:bg-emerald-500/20 transition-colors text-emerald-500" title="New Shipment">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </a>
                <div class="relative dropdown-container">
                    <button type="button" onclick="toggleDropdown(this)" class="dropdown-trigger p-2 rounded-lg hover:bg-white/10 transition-colors text-zinc-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-zinc-900 border border-white/10 rounded-xl shadow-2xl z-50 py-1 overflow-hidden">
                        <a href="{{ route('admin.clients.statement', $customer) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            View Statement
                        </a>
                        <a href="{{ route('admin.clients.statement.download', $customer) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download PDF
                        </a>
                        <a href="{{ route('admin.clients.contracts', $customer) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Contracts
                        </a>
                        <hr class="border-white/10 my-1">
                        <form method="POST" action="{{ route('admin.clients.refresh-stats', $customer) }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors text-left">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Refresh Stats
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="px-4 py-16 text-center">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-zinc-400 mb-1">No clients found</p>
                <p class="text-zinc-600 text-sm mb-4">Get started by adding your first client</p>
                <a href="{{ route('admin.clients.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add First Client
                </a>
            </div>
        </td>
    </tr>
@endforelse
