@extends('branch.layout')

@section('title', 'Client Contracts')
@section('header', 'Client Contracts')

@section('content')
    <div class="mb-4">
        <a href="{{ route('branch.clients.show', $client) }}" class="chip text-sm">&larr; Back to Client</a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            @php
                $colors = ['from-emerald-500 to-teal-600', 'from-blue-500 to-indigo-600', 'from-purple-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-red-600'];
                $colorIndex = crc32($client->customer_code ?? '') % count($colors);
            @endphp
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $colors[$colorIndex] }} flex items-center justify-center text-lg font-bold text-white">
                {{ strtoupper(substr($client->company_name ?: $client->contact_person, 0, 2)) }}
            </div>
            <div>
                <h1 class="text-xl font-bold">{{ $client->display_name }}</h1>
                <div class="flex items-center gap-2 text-sm muted">
                    <span class="font-mono">{{ $client->customer_code }}</span>
                    <span>-</span>
                    <span>Contracts</span>
                </div>
            </div>
        </div>
    </div>

    @if($contracts->count())
        <div class="space-y-6">
            @foreach($contracts as $contract)
                <div class="glass-panel p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="text-lg font-semibold">{{ $contract->contract_number ?? 'Contract #' . $contract->id }}</div>
                            <div class="text-sm text-zinc-400">
                                {{ $contract->start_date?->format('M d, Y') }} - {{ $contract->end_date?->format('M d, Y') ?? 'Ongoing' }}
                            </div>
                        </div>
                        @php
                            $statusStyles = [
                                'active' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
                                'expired' => 'bg-zinc-500/10 text-zinc-400 ring-zinc-500/20',
                                'pending' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
                                'cancelled' => 'bg-rose-500/10 text-rose-400 ring-rose-500/20',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusStyles[$contract->status] ?? $statusStyles['pending'] }}">
                            {{ ucfirst($contract->status ?? 'pending') }}
                        </span>
                    </div>

                    @if($contract->items && $contract->items->count())
                        <div class="overflow-x-auto rounded-lg border border-white/10">
                            <table class="w-full">
                                <thead class="bg-zinc-800/50">
                                    <tr class="text-left text-xs uppercase text-zinc-400">
                                        <th class="px-4 py-3">Service</th>
                                        <th class="px-4 py-3">Rate</th>
                                        <th class="px-4 py-3">Unit</th>
                                        <th class="px-4 py-3">Min Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach($contract->items as $item)
                                        <tr class="hover:bg-white/[0.02]">
                                            <td class="px-4 py-3 text-sm">{{ $item->service_name ?? $item->description }}</td>
                                            <td class="px-4 py-3 text-sm font-medium">{{ $currency }} {{ number_format($item->rate ?? 0, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $item->unit ?? 'per shipment' }}</td>
                                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $item->min_quantity ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">No contract items defined.</p>
                    @endif

                    @if($contract->notes)
                        <div class="mt-4 p-3 bg-zinc-800/50 rounded-lg">
                            <div class="text-xs uppercase text-zinc-500 mb-1">Notes</div>
                            <p class="text-sm text-zinc-300">{{ $contract->notes }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="glass-panel p-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-zinc-400 mb-1">No contracts found</p>
            <p class="text-zinc-600 text-sm">This client doesn't have any contracts yet.</p>
        </div>
    @endif
@endsection
