@extends('branch.layout')

@section('title', 'Settlement ' . $settlement->settlement_number)

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('branch.settlements.dashboard') }}" class="chip text-xs mb-2">&larr; Back to P&L</a>
                <h1 class="text-xl font-bold">{{ $settlement->settlement_number }}</h1>
                <p class="text-sm muted">
                    Period: {{ $settlement->period_start->format('M d, Y') }} - {{ $settlement->period_end->format('M d, Y') }}
                </p>
            </div>
            <span class="inline-flex px-3 py-1 rounded text-sm bg-{{ $settlement->status_badge }}-500/20 text-{{ $settlement->status_badge }}-400 border border-{{ $settlement->status_badge }}-500/30">
                {{ $settlement->status_label }}
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Summary -->
            <div class="lg:col-span-2 space-y-4">
                <div class="glass-panel p-5">
                    <div class="text-lg font-semibold mb-4">Financial Summary</div>
                    
                    <div class="space-y-4">
                        <!-- Revenue Section -->
                        <div class="border-b border-white/10 pb-4">
                            <div class="text-sm font-medium text-emerald-400 mb-2">Revenue</div>
                            <div class="flex justify-between text-sm">
                                <span class="muted">Shipment Revenue ({{ $settlement->shipment_count }} shipments)</span>
                                <span>{{ $settlement->currency }} {{ number_format($settlement->total_shipment_revenue, 2) }}</span>
                            </div>
                        </div>

                        <!-- Collections Section -->
                        <div class="border-b border-white/10 pb-4">
                            <div class="text-sm font-medium text-blue-400 mb-2">Collections</div>
                            <div class="flex justify-between text-sm">
                                <span class="muted">COD Collected ({{ $settlement->cod_shipment_count }} shipments)</span>
                                <span>{{ $settlement->currency }} {{ number_format($settlement->total_cod_collected, 2) }}</span>
                            </div>
                        </div>

                        <!-- Expenses Section -->
                        <div class="border-b border-white/10 pb-4">
                            <div class="text-sm font-medium text-rose-400 mb-2">Expenses</div>
                            <div class="flex justify-between text-sm">
                                <span class="muted">Driver Payments</span>
                                <span>{{ $settlement->currency }} {{ number_format($settlement->driver_payments, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span class="muted">Operational Costs</span>
                                <span>{{ $settlement->currency }} {{ number_format($settlement->operational_costs, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium mt-2">
                                <span>Total Expenses</span>
                                <span>{{ $settlement->currency }} {{ number_format($settlement->total_expenses, 2) }}</span>
                            </div>
                        </div>

                        <!-- Net Section -->
                        <div class="pt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">Net Amount</span>
                                <span class="text-xl font-bold {{ $settlement->net_amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $settlement->currency }} {{ number_format($settlement->net_amount, 2) }}
                                </span>
                            </div>
                            
                            @if($settlement->amount_due_to_hq > 0)
                                <div class="flex justify-between text-sm mt-2 text-amber-400">
                                    <span>Amount to Remit to HQ</span>
                                    <span>{{ $settlement->currency }} {{ number_format($settlement->amount_due_to_hq, 2) }}</span>
                                </div>
                            @endif
                            
                            @if($settlement->amount_due_from_hq > 0)
                                <div class="flex justify-between text-sm mt-2 text-emerald-400">
                                    <span>Amount Due from HQ</span>
                                    <span>{{ $settlement->currency }} {{ number_format($settlement->amount_due_from_hq, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($settlement->breakdown)
                    <div class="glass-panel p-5">
                        <div class="text-sm font-semibold mb-3">Breakdown Details</div>
                        <pre class="text-xs muted bg-obsidian-800 p-3 rounded overflow-auto">{{ json_encode($settlement->breakdown, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>

            <!-- Actions Sidebar -->
            <div class="space-y-4">
                <!-- Status & Actions -->
                <div class="glass-panel p-5">
                    <div class="text-sm font-semibold mb-4">Actions</div>
                    
                    @if($settlement->status === 'draft')
                        <form method="POST" action="{{ route('branch.settlements.submit', $settlement) }}">
                            @csrf
                            <button type="submit" class="chip w-full justify-center bg-emerald-600 hover:bg-emerald-700">
                                Submit for Approval
                            </button>
                        </form>
                        <p class="text-xs muted mt-2">Once submitted, HQ will review and approve.</p>
                    @elseif($settlement->status === 'submitted')
                        <div class="text-center py-4">
                            <div class="text-amber-400 mb-2">Awaiting HQ Approval</div>
                            <p class="text-xs muted">Submitted {{ $settlement->submitted_at->diffForHumans() }}</p>
                        </div>
                    @elseif($settlement->status === 'approved')
                        <div class="text-center py-4">
                            <div class="text-blue-400 mb-2">Approved - Pending Payment</div>
                            <p class="text-xs muted">Approved {{ $settlement->approved_at->diffForHumans() }}</p>
                        </div>
                    @elseif($settlement->status === 'rejected')
                        <div class="text-center py-4">
                            <div class="text-rose-400 mb-2">Rejected</div>
                            <p class="text-xs muted">{{ $settlement->rejection_reason }}</p>
                        </div>
                    @elseif($settlement->status === 'settled')
                        <div class="text-center py-4">
                            <div class="text-emerald-400 mb-2">Settled</div>
                            <p class="text-xs muted">
                                {{ $settlement->payment_method }} - {{ $settlement->payment_reference }}<br>
                                {{ $settlement->settled_at->format('M d, Y') }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Notes -->
                <div class="glass-panel p-5">
                    <div class="text-sm font-semibold mb-3">Notes</div>
                    @if($settlement->canSubmit())
                        <form method="POST" action="{{ route('branch.settlements.notes', $settlement) }}">
                            @csrf
                            <textarea name="notes" rows="3" placeholder="Add notes for HQ..."
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">{{ $settlement->notes }}</textarea>
                            <button type="submit" class="chip text-xs mt-2">Save Notes</button>
                        </form>
                    @else
                        <p class="text-sm muted">{{ $settlement->notes ?: 'No notes' }}</p>
                    @endif
                </div>

                <!-- Timeline -->
                <div class="glass-panel p-5">
                    <div class="text-sm font-semibold mb-3">Timeline</div>
                    <div class="space-y-3 text-xs">
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1"></div>
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
                                </div>
                            </div>
                        @endif
                        @if($settlement->approved_at)
                            <div class="flex gap-3">
                                <div class="w-2 h-2 rounded-full bg-blue-500 mt-1"></div>
                                <div>
                                    <div class="font-medium">{{ $settlement->status === 'rejected' ? 'Rejected' : 'Approved' }}</div>
                                    <div class="muted">{{ $settlement->approved_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                        @if($settlement->settled_at)
                            <div class="flex gap-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1"></div>
                                <div>
                                    <div class="font-medium">Settled</div>
                                    <div class="muted">{{ $settlement->settled_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
