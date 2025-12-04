@extends('admin.layout')

@section('title', 'Client Contracts')
@section('header', 'Contracts - ' . $client->display_name)

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.clients.show', $client) }}" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold">Contracts</h1>
                <div class="text-sm muted">{{ $client->display_name }} ({{ $client->customer_code }})</div>
            </div>
        </div>
    </div>

    <div class="glass-panel p-5">
        @if($contracts->count())
            <div class="space-y-4">
                @foreach($contracts as $contract)
                    <div class="border border-white/10 rounded-lg p-4 hover:border-white/20 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="font-semibold">{{ $contract->contract_number ?? 'Contract #' . $contract->id }}</div>
                                <div class="text-sm muted">
                                    {{ $contract->start_date?->format('M d, Y') }} - {{ $contract->end_date?->format('M d, Y') ?? 'Ongoing' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($contract->status === 'active')
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Active</span>
                                @elseif($contract->status === 'expired')
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-slate-500/20 text-slate-400 border border-slate-500/30">Expired</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30">{{ ucfirst($contract->status) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-4 text-sm">
                            <div>
                                <div class="text-2xs uppercase muted mb-1">Discount Rate</div>
                                <div class="font-medium text-emerald-400">{{ number_format($contract->discount_percentage ?? 0, 1) }}%</div>
                            </div>
                            <div>
                                <div class="text-2xs uppercase muted mb-1">Credit Limit</div>
                                <div class="font-medium">{{ $currency }} {{ number_format($contract->credit_limit ?? 0, 0) }}</div>
                            </div>
                            <div>
                                <div class="text-2xs uppercase muted mb-1">Payment Terms</div>
                                <div class="font-medium">{{ strtoupper($contract->payment_terms ?? 'N/A') }}</div>
                            </div>
                            <div>
                                <div class="text-2xs uppercase muted mb-1">Tier</div>
                                <div class="font-medium capitalize">{{ $contract->tier ?? 'Standard' }}</div>
                            </div>
                        </div>

                        @if($contract->items && $contract->items->count())
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <div class="text-xs uppercase muted mb-2">Contract Items</div>
                                <div class="space-y-2">
                                    @foreach($contract->items as $item)
                                        <div class="flex items-center justify-between text-sm">
                                            <span>{{ $item->service_type ?? $item->description }}</span>
                                            <span class="text-emerald-400">{{ number_format($item->discount_percentage ?? 0, 1) }}% off</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="muted mb-3">No contracts found for this client.</p>
                <p class="text-sm muted">Contract discounts are automatically applied during shipment pricing.</p>
            </div>
        @endif
    </div>

    <div class="mt-6 glass-panel p-5">
        <div class="text-lg font-semibold mb-4">Contract Pricing Benefits</div>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="p-4 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">Volume Discounts</span>
                </div>
                <p class="text-sm muted">Automatic discounts applied based on shipment volume and contract tier.</p>
            </div>
            <div class="p-4 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="font-medium">Extended Credit</span>
                </div>
                <p class="text-sm muted">Higher credit limits and flexible payment terms for contract customers.</p>
            </div>
            <div class="p-4 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="font-medium">Priority Service</span>
                </div>
                <p class="text-sm muted">VIP handling and priority processing for all shipments.</p>
            </div>
        </div>
    </div>
@endsection
