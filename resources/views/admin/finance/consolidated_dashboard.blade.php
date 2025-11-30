@extends('admin.layout')

@section('title', 'Finance Overview')
@section('header', 'Consolidated Finance')

@section('content')
    <div class="space-y-6">
        <!-- Period Selector -->
        <div class="glass-panel p-4">
            <form method="GET" action="{{ route('admin.finance.consolidated') }}" class="flex flex-wrap gap-4 items-end">
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

        <!-- Network Totals -->
        <div class="grid gap-4 md:grid-cols-4">
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">Network Revenue</div>
                <div class="text-2xl font-bold text-emerald-400">
                    USD {{ number_format($summary['totals']['revenue'], 2) }}
                </div>
            </div>
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">COD Collected</div>
                <div class="text-2xl font-bold text-blue-400">
                    USD {{ number_format($summary['totals']['cod_collected'], 2) }}
                </div>
            </div>
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">Pending Settlements</div>
                <div class="text-2xl font-bold text-amber-400">
                    USD {{ number_format($summary['totals']['pending_settlements'], 2) }}
                </div>
            </div>
            <div class="glass-panel p-5">
                <div class="text-2xs uppercase muted mb-1">Awaiting Approval</div>
                <div class="text-2xl font-bold text-purple-400">
                    {{ $summary['totals']['pending_approvals'] }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Pending Approvals -->
            <div class="glass-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-semibold">Pending Approvals</div>
                    <a href="{{ route('admin.finance.branch-settlements') }}" class="chip text-xs">View All</a>
                </div>
                
                @if($pendingSettlements->count())
                    <div class="space-y-3">
                        @foreach($pendingSettlements as $settlement)
                            <div class="border border-white/10 rounded p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <div class="font-semibold">{{ $settlement->branch->name }}</div>
                                        <div class="text-xs muted">{{ $settlement->settlement_number }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-lg">{{ $settlement->currency }} {{ number_format($settlement->amount_due_to_hq, 2) }}</div>
                                        <div class="text-xs muted">Due to HQ</div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="muted">{{ $settlement->period_start->format('M d') }} - {{ $settlement->period_end->format('M d, Y') }}</span>
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.finance.branch-settlements.show', $settlement) }}" class="chip text-2xs">Review</a>
                                        <form method="POST" action="{{ route('admin.finance.branch-settlements.approve', $settlement) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="chip text-2xs bg-emerald-600">Approve</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center py-8 muted">No settlements pending approval</p>
                @endif
            </div>

            <!-- By Branch -->
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Revenue by Branch</div>
                
                <div class="table-card">
                    <table class="dhl-table">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Revenue</th>
                                <th>COD</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['by_branch'] as $branchId => $data)
                                <tr>
                                    <td class="font-medium">{{ $data['branch_name'] }}</td>
                                    <td class="text-sm">USD {{ number_format($data['revenue']['delivered'] ?? 0, 2) }}</td>
                                    <td class="text-sm">USD {{ number_format($data['collections']['cod_collected'] ?? 0, 2) }}</td>
                                    <td class="text-sm text-amber-400">USD {{ number_format($data['settlements']['pending'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recently Settled -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Recently Settled</div>
            
            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Settlement #</th>
                            <th>Branch</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Settled</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSettled as $settlement)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.finance.branch-settlements.show', $settlement) }}" class="text-emerald-400 hover:underline">
                                        {{ $settlement->settlement_number }}
                                    </a>
                                </td>
                                <td>{{ $settlement->branch->name }}</td>
                                <td class="text-sm muted">
                                    {{ $settlement->period_start->format('M d') }} - {{ $settlement->period_end->format('M d') }}
                                </td>
                                <td class="font-medium">{{ $settlement->currency }} {{ number_format($settlement->amount_due_to_hq, 2) }}</td>
                                <td class="text-sm">{{ $settlement->payment_method }} - {{ $settlement->payment_reference }}</td>
                                <td class="text-sm muted">{{ $settlement->settled_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 muted">No settlements yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
