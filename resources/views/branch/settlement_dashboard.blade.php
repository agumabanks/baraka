@extends('branch.layout')

@section('title', 'P&L Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Period Selector -->
        <div class="glass-panel p-4">
            <form method="GET" action="{{ route('branch.settlements.dashboard') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs muted mb-1">From</label>
                    <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" 
                        class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs muted mb-1">To</label>
                    <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" 
                        class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <button type="submit" class="chip">Update</button>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 md:grid-cols-4">
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">Revenue (Delivered)</div>
                <div class="text-2xl font-bold text-emerald-400">
                    {{ $defaultCurrency }} {{ number_format($summary['revenue']['delivered'], 2) }}
                </div>
                <div class="text-xs muted mt-1">
                    Booked: {{ $defaultCurrency }} {{ number_format($summary['revenue']['booked'], 2) }}
                </div>
            </div>
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">COD Collected</div>
                <div class="text-2xl font-bold text-blue-400">
                    {{ $defaultCurrency }} {{ number_format($summary['collections']['cod_collected'], 2) }}
                </div>
            </div>
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">Pending Invoices</div>
                <div class="text-2xl font-bold text-amber-400">
                    {{ $defaultCurrency }} {{ number_format($summary['receivables']['pending_invoices'], 2) }}
                </div>
            </div>
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">Pending Settlement</div>
                <div class="text-2xl font-bold text-purple-400">
                    {{ $defaultCurrency }} {{ number_format($summary['settlements']['pending'], 2) }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Generate Settlement -->
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Create Settlement</div>
                @if($draftSettlement)
                    <div class="border border-amber-500/30 bg-amber-500/10 rounded p-4 mb-4">
                        <div class="text-sm font-semibold text-amber-100">Draft Settlement Exists</div>
                        <div class="text-xs muted mt-1">
                            Period: {{ $draftSettlement->period_start->format('M d') }} - {{ $draftSettlement->period_end->format('M d, Y') }}
                        </div>
                        <div class="text-sm mt-2">
                            Net: {{ $draftSettlement->currency }} {{ number_format($draftSettlement->net_amount, 2) }}
                        </div>
                        <a href="{{ route('branch.settlements.show', $draftSettlement) }}" class="chip text-xs mt-3">
                            Review & Submit
                        </a>
                    </div>
                @else
                    <form method="POST" action="{{ route('branch.settlements.create') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs muted mb-1">Period Start</label>
                            <input type="date" name="period_start" value="{{ now()->startOfMonth()->format('Y-m-d') }}" 
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs muted mb-1">Period End</label>
                            <input type="date" name="period_end" value="{{ now()->format('Y-m-d') }}" 
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs muted mb-1">Currency</label>
                            <select name="currency" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="CDF">CDF - Congolese Franc</option>
                                <option value="RWF">RWF - Rwandan Franc</option>
                            </select>
                        </div>
                        <button type="submit" class="chip w-full justify-center">Generate Settlement</button>
                    </form>
                @endif
            </div>

            <!-- Recent Settlements -->
            <div class="lg:col-span-2 glass-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-semibold">Recent Settlements</div>
                    <a href="{{ route('branch.settlements.index') }}" class="chip text-xs">View All</a>
                </div>
                
                <div class="table-card">
                    <table class="dhl-table">
                        <thead>
                            <tr>
                                <th>Settlement #</th>
                                <th>Period</th>
                                <th>Net Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($settlements as $settlement)
                                <tr>
                                    <td>
                                        <a href="{{ route('branch.settlements.show', $settlement) }}" class="text-emerald-400 hover:underline">
                                            {{ $settlement->settlement_number }}
                                        </a>
                                    </td>
                                    <td class="text-sm muted">
                                        {{ $settlement->period_start->format('M d') }} - {{ $settlement->period_end->format('M d, Y') }}
                                    </td>
                                    <td class="font-medium">
                                        {{ $settlement->currency }} {{ number_format($settlement->net_amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="inline-flex px-2 py-0.5 rounded text-2xs bg-{{ $settlement->status_badge }}-500/20 text-{{ $settlement->status_badge }}-400 border border-{{ $settlement->status_badge }}-500/30">
                                            {{ $settlement->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 muted">No settlements yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Settlement to HQ Explanation -->
        <div class="glass-panel p-5 border border-blue-500/30">
            <div class="text-sm font-semibold text-blue-100 mb-2">Settlement Process</div>
            <ol class="text-xs muted space-y-1 list-decimal list-inside">
                <li>Generate settlement for a period (shows revenue, COD collected, expenses)</li>
                <li>Review the breakdown and add any notes</li>
                <li>Submit to HQ for approval</li>
                <li>Once approved, HQ processes payment or requests fund transfer</li>
                <li>Settlement marked as complete</li>
            </ol>
        </div>
    </div>
@endsection
