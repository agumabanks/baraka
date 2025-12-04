@extends('branch.layout')

@section('title', 'P&L Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Profit & Loss Report</h2>
            <p class="text-sm text-zinc-400">{{ $start->format('M d') }} - {{ $end->format('M d, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('branch.settlements.dashboard') }}" class="chip">Back to Settlements</a>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="glass-panel p-4">
        <form method="GET" action="{{ route('branch.settlements.pl-report') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs muted mb-1">From</label>
                <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs muted mb-1">To</label>
                <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
            </div>
            <button type="submit" class="chip">Update</button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Total Revenue</div>
            <div class="text-2xl font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format($revenue['total'], 0) }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Total Expenses</div>
            <div class="text-2xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($expensesTotal, 0) }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Net Profit</div>
            <div class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ $defaultCurrency }} {{ number_format($netProfit, 0) }}
            </div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">COD Position</div>
            <div class="text-2xl font-bold text-blue-400">{{ $defaultCurrency }} {{ number_format($codPosition, 0) }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Revenue Breakdown -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4 text-emerald-400">Revenue Breakdown</div>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-white/5">
                    <span class="text-zinc-400">Delivery Charges</span>
                    <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($revenue['delivery_charges'], 0) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-white/5">
                    <span class="text-zinc-400">Fuel Surcharge</span>
                    <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($revenue['fuel_surcharge'], 0) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-white/5">
                    <span class="text-zinc-400">Insurance Fees</span>
                    <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($revenue['insurance_fees'], 0) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-white/5">
                    <span class="text-zinc-400">Special Handling</span>
                    <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($revenue['special_handling'], 0) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-white/5">
                    <span class="text-zinc-400">COD Fees</span>
                    <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($revenue['cod_fees'], 0) }}</span>
                </div>
                <div class="flex justify-between py-2 font-bold text-emerald-400">
                    <span>Total Revenue</span>
                    <span>{{ $defaultCurrency }} {{ number_format($revenue['total'], 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4 text-rose-400">Expense Breakdown</div>
            <div class="space-y-3">
                @foreach($expenses as $category => $amount)
                    <div class="flex justify-between py-2 border-b border-white/5">
                        <span class="text-zinc-400">{{ $category }}</span>
                        <span class="font-semibold">{{ $defaultCurrency }} {{ number_format($amount, 0) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between py-2 font-bold text-rose-400">
                    <span>Total Expenses</span>
                    <span>{{ $defaultCurrency }} {{ number_format($expensesTotal, 0) }}</span>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('branch.settlements.expense-breakdown') }}" class="chip w-full justify-center">Detailed Expense Report</a>
            </div>
        </div>
    </div>

    <!-- COD Section -->
    <div class="glass-panel p-5">
        <div class="text-lg font-semibold mb-4">COD Cash Position</div>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="p-4 border border-emerald-500/30 bg-emerald-500/10 rounded-lg">
                <div class="text-sm text-emerald-100 mb-1">COD Collected</div>
                <div class="text-xl font-bold">{{ $defaultCurrency }} {{ number_format($codCollected, 0) }}</div>
            </div>
            <div class="p-4 border border-rose-500/30 bg-rose-500/10 rounded-lg">
                <div class="text-sm text-rose-100 mb-1">COD Remitted</div>
                <div class="text-xl font-bold">{{ $defaultCurrency }} {{ number_format($codRemitted, 0) }}</div>
            </div>
            <div class="p-4 border border-blue-500/30 bg-blue-500/10 rounded-lg">
                <div class="text-sm text-blue-100 mb-1">Net COD Position</div>
                <div class="text-xl font-bold">{{ $defaultCurrency }} {{ number_format($codPosition, 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Net Summary -->
    <div class="glass-panel p-5 border {{ $netProfit >= 0 ? 'border-emerald-500/30' : 'border-rose-500/30' }}">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold">Net Profit/Loss</div>
                <div class="text-sm text-zinc-400">Revenue minus Expenses for the period</div>
            </div>
            <div class="text-3xl font-bold {{ $netProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ $defaultCurrency }} {{ number_format($netProfit, 0) }}
            </div>
        </div>
    </div>
</div>
@endsection
