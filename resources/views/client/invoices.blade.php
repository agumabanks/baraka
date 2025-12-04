@extends('client.layout')

@section('title', 'Invoices')
@section('header', 'Invoices')

@section('content')
    <div class="glass-panel">
        @if($invoices->count() > 0)
            <div class="divide-y divide-white/10">
                @foreach($invoices as $invoice)
                    <div class="p-5 hover:bg-white/5 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <div class="font-mono font-medium">{{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}</div>
                                    <div class="text-sm text-zinc-400">{{ $invoice->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold">${{ number_format($invoice->total ?? 0, 2) }}</div>
                                <span class="px-2 py-0.5 rounded text-xs {{ ($invoice->status ?? 'unpaid') === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                                    {{ ucfirst($invoice->status ?? 'Unpaid') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($invoices->hasPages())
                <div class="p-5 border-t border-white/10">
                    {{ $invoices->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h3 class="text-lg font-medium mb-2">No Invoices</h3>
                <p class="text-zinc-400">You don't have any invoices yet.</p>
            </div>
        @endif
    </div>
@endsection
