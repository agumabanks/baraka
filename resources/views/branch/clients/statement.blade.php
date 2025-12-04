@extends('branch.layout')

@section('title', 'Statement - ' . $customer->display_name)
@section('header', 'Account Statement')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('branch.clients.show', $customer) }}" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold">Account Statement</h1>
                <div class="text-sm muted">{{ $customer->display_name }} ({{ $customer->customer_code }})</div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('branch.clients.statement.download', ['client' => $customer, 'start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]) }}" 
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
        </div>
    </div>

    {{-- Date Range Selector --}}
    <div class="glass-panel p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Start Date</label>
                <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" 
                    class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">End Date</label>
                <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" 
                    class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors">
                Update
            </button>
            <div class="flex gap-2">
                <a href="?start={{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}&end={{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}" 
                    class="px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded text-sm">Last Month</a>
                <a href="?start={{ now()->startOfMonth()->format('Y-m-d') }}&end={{ now()->format('Y-m-d') }}" 
                    class="px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded text-sm">This Month</a>
                <a href="?start={{ now()->subMonths(3)->startOfMonth()->format('Y-m-d') }}&end={{ now()->format('Y-m-d') }}" 
                    class="px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded text-sm">Last 3 Months</a>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Opening Balance</div>
            <div class="text-2xl font-bold">{{ number_format($summary['opening_balance'], 2) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Total Invoiced</div>
            <div class="text-2xl font-bold text-amber-400">{{ number_format($summary['total_invoiced'], 2) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Total Paid</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($summary['total_paid'], 2) }}</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-2xs uppercase muted mb-1">Closing Balance</div>
            <div class="text-2xl font-bold {{ $summary['closing_balance'] > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                {{ number_format($summary['closing_balance'], 2) }}
            </div>
        </div>
    </div>

    {{-- Aging Summary --}}
    @if(isset($aging))
    <div class="glass-panel p-5 mb-6">
        <div class="text-lg font-semibold mb-4">Aging Summary</div>
        <div class="grid gap-4 md:grid-cols-5">
            <div class="text-center p-3 bg-emerald-500/10 rounded-lg">
                <div class="text-2xs uppercase muted mb-1">Current (0-30)</div>
                <div class="text-lg font-bold text-emerald-400">{{ number_format($aging['current'], 2) }}</div>
            </div>
            <div class="text-center p-3 bg-amber-500/10 rounded-lg">
                <div class="text-2xs uppercase muted mb-1">31-60 Days</div>
                <div class="text-lg font-bold text-amber-400">{{ number_format($aging['days_31_60'], 2) }}</div>
            </div>
            <div class="text-center p-3 bg-orange-500/10 rounded-lg">
                <div class="text-2xs uppercase muted mb-1">61-90 Days</div>
                <div class="text-lg font-bold text-orange-400">{{ number_format($aging['days_61_90'], 2) }}</div>
            </div>
            <div class="text-center p-3 bg-rose-500/10 rounded-lg">
                <div class="text-2xs uppercase muted mb-1">Over 90 Days</div>
                <div class="text-lg font-bold text-rose-400">{{ number_format($aging['over_90'], 2) }}</div>
            </div>
            <div class="text-center p-3 bg-zinc-700/50 rounded-lg">
                <div class="text-2xs uppercase muted mb-1">Total Outstanding</div>
                <div class="text-lg font-bold">{{ number_format($aging['total'], 2) }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Transaction Ledger --}}
    <div class="glass-panel p-5 mb-6">
        <div class="text-lg font-semibold mb-4">Transaction Ledger</div>
        <div class="overflow-x-auto rounded-lg border border-white/10">
            <table class="w-full">
                <thead class="bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase text-zinc-400">
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Description</th>
                        <th class="px-4 py-3 font-medium text-right">Debit</th>
                        <th class="px-4 py-3 font-medium text-right">Credit</th>
                        <th class="px-4 py-3 font-medium text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr class="bg-zinc-800/30">
                        <td class="px-4 py-3 text-sm">{{ $start->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm">—</td>
                        <td class="px-4 py-3 text-sm font-medium">Opening Balance</td>
                        <td class="px-4 py-3 text-sm text-right">—</td>
                        <td class="px-4 py-3 text-sm text-right">—</td>
                        <td class="px-4 py-3 text-sm text-right font-bold">{{ number_format($summary['opening_balance'], 2) }}</td>
                    </tr>
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-sm">{{ $tx['date']->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-sm font-mono">{{ $tx['reference'] }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($tx['type'] === 'invoice')
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        {{ $tx['description'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        {{ $tx['description'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right {{ $tx['debit'] > 0 ? 'text-rose-400' : '' }}">
                                {{ $tx['debit'] > 0 ? number_format($tx['debit'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right {{ $tx['credit'] > 0 ? 'text-emerald-400' : '' }}">
                                {{ $tx['credit'] > 0 ? number_format($tx['credit'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($tx['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm muted">
                                No transactions found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-zinc-800/50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-sm font-bold">Closing Balance</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-rose-400">
                            {{ number_format($summary['total_invoiced'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-emerald-400">
                            {{ number_format($summary['total_paid'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-bold {{ $summary['closing_balance'] > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                            {{ number_format($summary['closing_balance'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Shipment Summary --}}
    @if($shipments->count())
    <div class="glass-panel p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="text-lg font-semibold">Shipments ({{ $shipments->count() }})</div>
            <div class="text-sm muted">Total Value: {{ number_format($summary['total_shipment_value'], 2) }}</div>
        </div>
        <div class="overflow-x-auto rounded-lg border border-white/10">
            <table class="w-full">
                <thead class="bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase text-zinc-400">
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">AWB/Tracking</th>
                        <th class="px-4 py-3 font-medium">Origin</th>
                        <th class="px-4 py-3 font-medium">Destination</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($shipments->take(50) as $shipment)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-sm">{{ $shipment->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-sm font-mono">{{ $shipment->tracking_number ?? $shipment->awb_number ?? "SHP-{$shipment->id}" }}</td>
                            <td class="px-4 py-3 text-sm">{{ $shipment->originBranch?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $shipment->destBranch?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="chip text-2xs">{{ $shipment->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($shipment->total_amount ?? $shipment->price_amount ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($shipments->count() > 50)
            <div class="mt-4 text-sm text-center muted">
                Showing 50 of {{ $shipments->count() }} shipments. Download PDF for full list.
            </div>
        @endif
    </div>
    @endif
@endsection
