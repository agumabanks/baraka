@extends('branch.layout')

@section('title', 'Cash Position')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Daily Cash Position</h1>
        <p class="text-sm muted">{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</p>
    </div>
    <form class="flex items-center gap-2">
        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
    </form>
</div>

@include('branch.finance._nav')

{{-- Summary --}}
<div class="grid gap-4 md:grid-cols-3 mb-6">
    <div class="glass-panel p-4 border-l-4 border-emerald-500">
        <div class="text-2xs uppercase muted mb-1">Cash In</div>
        <div class="text-2xl font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format(($cashIn['cod_collections'] ?? 0) + ($cashIn['prepaid_revenue'] ?? 0), 0) }}</div>
    </div>
    <div class="glass-panel p-4 border-l-4 border-rose-500">
        <div class="text-2xs uppercase muted mb-1">Cash Out</div>
        <div class="text-2xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format(($cashOut['expenses'] ?? 0) + ($cashOut['cod_remittances'] ?? 0), 0) }}</div>
    </div>
    @php
        $netCash = (($cashIn['cod_collections'] ?? 0) + ($cashIn['prepaid_revenue'] ?? 0)) - (($cashOut['expenses'] ?? 0) + ($cashOut['cod_remittances'] ?? 0));
    @endphp
    <div class="glass-panel p-4 border-l-4 {{ $netCash >= 0 ? 'border-blue-500' : 'border-amber-500' }}">
        <div class="text-2xs uppercase muted mb-1">Net Cash Flow</div>
        <div class="text-2xl font-bold {{ $netCash >= 0 ? 'text-blue-400' : 'text-amber-400' }}">{{ $defaultCurrency }} {{ number_format($netCash, 0) }}</div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2 mb-6">
    {{-- Cash In --}}
    <div class="glass-panel p-5">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div class="text-lg font-semibold">Cash In</div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-zinc-800/50 rounded-lg">
                <div>
                    <div class="font-medium">COD Collections</div>
                    <div class="text-xs text-zinc-400">Cash collected from deliveries</div>
                </div>
                <div class="text-lg font-semibold text-emerald-400">{{ $defaultCurrency }} {{ number_format($cashIn['cod_collections'] ?? 0, 0) }}</div>
            </div>
            <div class="flex justify-between items-center p-3 bg-zinc-800/50 rounded-lg">
                <div>
                    <div class="font-medium">Prepaid Revenue</div>
                    <div class="text-xs text-zinc-400">Bookings paid upfront</div>
                </div>
                <div class="text-lg font-semibold text-emerald-400">{{ $defaultCurrency }} {{ number_format($cashIn['prepaid_revenue'] ?? 0, 0) }}</div>
            </div>
            <div class="flex justify-between items-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                <div class="font-semibold">Total Cash In</div>
                <div class="text-xl font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format(($cashIn['cod_collections'] ?? 0) + ($cashIn['prepaid_revenue'] ?? 0), 0) }}</div>
            </div>
        </div>
    </div>

    {{-- Cash Out --}}
    <div class="glass-panel p-5">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </div>
            <div class="text-lg font-semibold">Cash Out</div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-zinc-800/50 rounded-lg">
                <div>
                    <div class="font-medium">Expenses</div>
                    <div class="text-xs text-zinc-400">Operational costs</div>
                </div>
                <div class="text-lg font-semibold text-rose-400">{{ $defaultCurrency }} {{ number_format($cashOut['expenses'] ?? 0, 0) }}</div>
            </div>
            <div class="flex justify-between items-center p-3 bg-zinc-800/50 rounded-lg">
                <div>
                    <div class="font-medium">COD Remittances</div>
                    <div class="text-xs text-zinc-400">COD sent to HQ</div>
                </div>
                <div class="text-lg font-semibold text-rose-400">{{ $defaultCurrency }} {{ number_format($cashOut['cod_remittances'] ?? 0, 0) }}</div>
            </div>
            <div class="flex justify-between items-center p-3 bg-rose-500/10 rounded-lg border border-rose-500/30">
                <div class="font-semibold">Total Cash Out</div>
                <div class="text-xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format(($cashOut['expenses'] ?? 0) + ($cashOut['cod_remittances'] ?? 0), 0) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- 7-Day Trend --}}
<div class="glass-panel p-5">
    <div class="text-lg font-semibold mb-4">7-Day Cash Flow Trend</div>
    <div class="h-64">
        <canvas id="cashFlowChart"></canvas>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const weekTrend = @json($weekTrend);
const ctx = document.getElementById('cashFlowChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: weekTrend.map(d => d.date),
        datasets: [
            {
                label: 'Cash In',
                data: weekTrend.map(d => parseFloat(d.cash_in) || 0),
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
            },
            {
                label: 'Cash Out',
                data: weekTrend.map(d => parseFloat(d.cash_out) || 0),
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#9ca3af' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#9ca3af' },
                grid: { color: '#374151' }
            },
            x: {
                ticks: { color: '#9ca3af' },
                grid: { color: '#374151' }
            }
        }
    }
});
</script>
@endpush
