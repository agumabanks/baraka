@extends('branch.layout')

@section('title', 'Settlements')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">Settlements</h1>
                <p class="text-sm muted">Branch to HQ settlement history</p>
            </div>
            <a href="{{ route('branch.settlements.dashboard') }}" class="chip">P&L Dashboard</a>
        </div>

        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <form method="GET" action="{{ route('branch.settlements') }}" class="flex gap-2">
                    <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="draft" @selected($statusFilter === 'draft')>Draft</option>
                        <option value="submitted" @selected($statusFilter === 'submitted')>Pending Approval</option>
                        <option value="approved" @selected($statusFilter === 'approved')>Approved</option>
                        <option value="rejected" @selected($statusFilter === 'rejected')>Rejected</option>
                        <option value="settled" @selected($statusFilter === 'settled')>Settled</option>
                    </select>
                </form>
            </div>

            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Settlement #</th>
                            <th>Period</th>
                            <th>Revenue</th>
                            <th>COD</th>
                            <th>Net Amount</th>
                            <th>Due to HQ</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr>
                                <td class="font-medium">{{ $settlement->settlement_number }}</td>
                                <td class="text-sm muted">
                                    {{ $settlement->period_start->format('M d') }} - {{ $settlement->period_end->format('M d, Y') }}
                                </td>
                                <td class="text-sm">
                                    {{ $settlement->currency }} {{ number_format($settlement->total_shipment_revenue, 2) }}
                                </td>
                                <td class="text-sm">
                                    {{ $settlement->currency }} {{ number_format($settlement->total_cod_collected, 2) }}
                                </td>
                                <td class="font-medium {{ $settlement->net_amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $settlement->currency }} {{ number_format($settlement->net_amount, 2) }}
                                </td>
                                <td class="text-sm text-amber-400">
                                    {{ $settlement->currency }} {{ number_format($settlement->amount_due_to_hq, 2) }}
                                </td>
                                <td>
                                    <span class="inline-flex px-2 py-0.5 rounded text-2xs bg-{{ $settlement->status_badge }}-500/20 text-{{ $settlement->status_badge }}-400 border border-{{ $settlement->status_badge }}-500/30">
                                        {{ $settlement->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('branch.settlements.show', $settlement) }}" class="chip text-2xs">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 muted">No settlements found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $settlements->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
