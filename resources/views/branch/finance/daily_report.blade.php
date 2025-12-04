@extends('branch.layout')

@section('title', 'Daily Financial Report')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Daily Financial Report</h1>
        <p class="text-sm muted">{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</p>
    </div>
    <form class="flex items-center gap-2">
        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="bg-zinc-800 border border-white/10 rounded px-3 py-2 text-sm">
    </form>
</div>

@include('branch.finance._nav')

{{-- Summary Cards --}}
<div class="grid gap-4 md:grid-cols-5 mb-6">
    <div class="glass-panel p-4">
        <div class="text-2xs uppercase muted mb-1">Deliveries</div>
        <div class="text-2xl font-bold text-blue-400">{{ $deliveredShipments }}</div>
    </div>
    <div class="glass-panel p-4">
        <div class="text-2xs uppercase muted mb-1">Revenue</div>
        <div class="text-2xl font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format($revenue, 0) }}</div>
    </div>
    <div class="glass-panel p-4">
        <div class="text-2xs uppercase muted mb-1">COD Collected</div>
        <div class="text-2xl font-bold text-purple-400">{{ $defaultCurrency }} {{ number_format($codCollected, 0) }}</div>
    </div>
    <div class="glass-panel p-4">
        <div class="text-2xs uppercase muted mb-1">Expenses</div>
        <div class="text-2xl font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($expenses, 0) }}</div>
    </div>
    <div class="glass-panel p-4 {{ $netPosition >= 0 ? 'border-emerald-500/30' : 'border-rose-500/30' }}">
        <div class="text-2xs uppercase muted mb-1">Net Position</div>
        <div class="text-2xl font-bold {{ $netPosition >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
            {{ $defaultCurrency }} {{ number_format($netPosition, 0) }}
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Financial Breakdown --}}
    <div class="glass-panel p-5">
        <div class="text-lg font-semibold mb-4">Financial Summary</div>
        <div class="space-y-4">
            <div class="p-4 bg-emerald-500/10 rounded-lg">
                <div class="text-sm text-emerald-400 mb-2">Cash In</div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Revenue (Bookings)</span>
                        <span class="font-medium">{{ $defaultCurrency }} {{ number_format($revenue, 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-400">COD Collected</span>
                        <span class="font-medium">{{ $defaultCurrency }} {{ number_format($codCollected, 0) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-emerald-500/30">
                        <span class="font-semibold">Total In</span>
                        <span class="font-bold text-emerald-400">{{ $defaultCurrency }} {{ number_format($revenue + $codCollected, 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-rose-500/10 rounded-lg">
                <div class="text-sm text-rose-400 mb-2">Cash Out</div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Expenses</span>
                        <span class="font-medium">{{ $defaultCurrency }} {{ number_format($expenses, 0) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-rose-500/30">
                        <span class="font-semibold">Total Out</span>
                        <span class="font-bold text-rose-400">{{ $defaultCurrency }} {{ number_format($expenses, 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-zinc-800 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">Net Position</span>
                    <span class="text-2xl font-bold {{ $netPosition >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $defaultCurrency }} {{ number_format($netPosition, 0) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Worker Performance --}}
    <div class="glass-panel p-5">
        <div class="text-lg font-semibold mb-4">Worker Performance</div>
        <div class="overflow-x-auto rounded-lg border border-white/10">
            <table class="w-full">
                <thead class="bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase text-zinc-400">
                        <th class="px-4 py-3">Worker</th>
                        <th class="px-4 py-3 text-right">Deliveries</th>
                        <th class="px-4 py-3 text-right">COD Collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($workerStats as $stat)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 font-medium">{{ $stat->name }}</td>
                            <td class="px-4 py-3 text-right">{{ $stat->deliveries }}</td>
                            <td class="px-4 py-3 text-right text-emerald-400">{{ $defaultCurrency }} {{ number_format($stat->cod_collected, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-zinc-500">No deliveries recorded</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Quick Navigation --}}
<div class="mt-6 flex gap-3">
    <a href="{{ route('branch.finance.daily-report', ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()]) }}" 
       class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg text-sm transition-colors">
        &larr; Previous Day
    </a>
    @if(\Carbon\Carbon::parse($date)->lt(now()->startOfDay()))
        <a href="{{ route('branch.finance.daily-report', ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()]) }}" 
           class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg text-sm transition-colors">
            Next Day &rarr;
        </a>
    @endif
</div>
@endsection
