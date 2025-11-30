@extends('admin.layout')

@section('title', 'Settlement ' . $settlement->settlement_number)
@section('header', 'Settlement Details')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.finance.branch-settlements') }}" class="chip text-xs mb-2">&larr; Back</a>
                <h1 class="text-xl font-bold">{{ $settlement->settlement_number }}</h1>
                <p class="text-sm muted">{{ $settlement->branch->name }} | {{ $settlement->period_start->format('M d') }} - {{ $settlement->period_end->format('M d, Y') }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded text-sm bg-{{ $settlement->status_badge }}-500/20 text-{{ $settlement->status_badge }}-400 border border-{{ $settlement->status_badge }}-500/30">
                {{ $settlement->status_label }}
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <!-- Financial Summary -->
                <div class="glass-panel p-5">
                    <div class="text-lg font-semibold mb-4">Financial Summary</div>
                    
                    <div class="grid gap-4 md:grid-cols-3 mb-6">
                        <div class="text-center p-4 bg-emerald-500/10 rounded border border-emerald-500/30">
                            <div class="text-2xs uppercase muted">Revenue</div>
                            <div class="text-xl font-bold text-emerald-400">{{ $settlement->currency }} {{ number_format($settlement->total_shipment_revenue, 2) }}</div>
                            <div class="text-xs muted">{{ $settlement->shipment_count }} shipments</div>
                        </div>
                        <div class="text-center p-4 bg-blue-500/10 rounded border border-blue-500/30">
                            <div class="text-2xs uppercase muted">COD Collected</div>
                            <div class="text-xl font-bold text-blue-400">{{ $settlement->currency }} {{ number_format($settlement->total_cod_collected, 2) }}</div>
                            <div class="text-xs muted">{{ $settlement->cod_shipment_count }} COD shipments</div>
                        </div>
                        <div class="text-center p-4 bg-amber-500/10 rounded border border-amber-500/30">
                            <div class="text-2xs uppercase muted">Due to HQ</div>
                            <div class="text-xl font-bold text-amber-400">{{ $settlement->currency }} {{ number_format($settlement->amount_due_to_hq, 2) }}</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-white/10 pb-2">
                            <span class="muted">Shipment Revenue</span>
                            <span>{{ $settlement->currency }} {{ number_format($settlement->total_shipment_revenue, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-white/10 pb-2">
                            <span class="muted">COD Collected</span>
                            <span>{{ $settlement->currency }} {{ number_format($settlement->total_cod_collected, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-white/10 pb-2">
                            <span class="muted">Total Expenses</span>
                            <span class="text-rose-400">-{{ $settlement->currency }} {{ number_format($settlement->total_expenses, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-white/10 pb-2">
                            <span class="muted">Net Amount</span>
                            <span class="font-medium">{{ $settlement->currency }} {{ number_format($settlement->net_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between pt-2">
                            <span class="font-semibold">Amount Due to HQ</span>
                            <span class="text-lg font-bold text-amber-400">{{ $settlement->currency }} {{ number_format($settlement->amount_due_to_hq, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($settlement->notes)
                    <div class="glass-panel p-5">
                        <div class="text-sm font-semibold mb-2">Branch Notes</div>
                        <p class="text-sm muted">{{ $settlement->notes }}</p>
                    </div>
                @endif

                @if($settlement->breakdown)
                    <div class="glass-panel p-5">
                        <div class="text-sm font-semibold mb-3">Detailed Breakdown</div>
                        <pre class="text-xs muted bg-obsidian-800 p-3 rounded overflow-auto max-h-64">{{ json_encode($settlement->breakdown, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>

            <!-- Actions Sidebar -->
            <div class="space-y-4">
                <div class="glass-panel p-5">
                    <div class="text-sm font-semibold mb-4">Actions</div>
                    
                    @if($settlement->status === 'submitted')
                        <div class="space-y-3">
                            <form method="POST" action="{{ route('admin.finance.branch-settlements.approve', $settlement) }}">
                                @csrf
                                <button type="submit" class="chip w-full justify-center bg-emerald-600 hover:bg-emerald-700">
                                    Approve Settlement
                                </button>
                            </form>
                            
                            <form method="POST" action="{{ route('admin.finance.branch-settlements.reject', $settlement) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="rejection_reason" placeholder="Rejection reason..." 
                                    class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" required>
                                <button type="submit" class="chip w-full justify-center bg-rose-600 hover:bg-rose-700">
                                    Reject
                                </button>
                            </form>
                        </div>
                    @elseif($settlement->status === 'approved')
                        <form method="POST" action="{{ route('admin.finance.branch-settlements.settle', $settlement) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs muted mb-1">Payment Method</label>
                                <select name="payment_method" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" required>
                                    <option value="">Select method</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cash">Cash</option>
                                    <option value="offset">Offset</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs muted mb-1">Payment Reference</label>
                                <input type="text" name="payment_reference" placeholder="Transaction ID / Reference" 
                                    class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" required>
                            </div>
                            <button type="submit" class="chip w-full justify-center bg-emerald-600 hover:bg-emerald-700">
                                Mark as Settled
                            </button>
                        </form>
                    @elseif($settlement->status === 'settled')
                        <div class="text-center py-4">
                            <div class="text-emerald-400 text-lg mb-2">✓ Settled</div>
                            <p class="text-xs muted">
                                {{ $settlement->payment_method }}<br>
                                Ref: {{ $settlement->payment_reference }}<br>
                                {{ $settlement->settled_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    @elseif($settlement->status === 'rejected')
                        <div class="text-center py-4">
                            <div class="text-rose-400 mb-2">Rejected</div>
                            <p class="text-xs muted">{{ $settlement->rejection_reason }}</p>
                        </div>
                    @else
                        <p class="text-sm muted text-center py-4">Awaiting branch submission</p>
                    @endif
                </div>

                <!-- Timeline -->
                <div class="glass-panel p-5">
                    <div class="text-sm font-semibold mb-3">Timeline</div>
                    <div class="space-y-3 text-xs">
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-slate-500 mt-1"></div>
                            <div>
                                <div class="font-medium">Created</div>
                                <div class="muted">{{ $settlement->created_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                        @if($settlement->submitted_at)
                            <div class="flex gap-3">
                                <div class="w-2 h-2 rounded-full bg-amber-500 mt-1"></div>
                                <div>
                                    <div class="font-medium">Submitted</div>
                                    <div class="muted">{{ $settlement->submitted_at->format('M d, Y H:i') }}</div>
                                    @if($settlement->submittedByUser)
                                        <div class="muted">by {{ $settlement->submittedByUser->name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($settlement->approved_at)
                            <div class="flex gap-3">
                                <div class="w-2 h-2 rounded-full bg-{{ $settlement->status === 'rejected' ? 'rose' : 'blue' }}-500 mt-1"></div>
                                <div>
                                    <div class="font-medium">{{ $settlement->status === 'rejected' ? 'Rejected' : 'Approved' }}</div>
                                    <div class="muted">{{ $settlement->approved_at->format('M d, Y H:i') }}</div>
                                    @if($settlement->approvedByUser)
                                        <div class="muted">by {{ $settlement->approvedByUser->name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($settlement->settled_at)
                            <div class="flex gap-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1"></div>
                                <div>
                                    <div class="font-medium">Settled</div>
                                    <div class="muted">{{ $settlement->settled_at->format('M d, Y H:i') }}</div>
                                    @if($settlement->settledByUser)
                                        <div class="muted">by {{ $settlement->settledByUser->name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
