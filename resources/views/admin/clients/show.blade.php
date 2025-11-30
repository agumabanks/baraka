@extends('admin.layout')

@section('title', 'Client Details')
@section('header', $client->display_name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.clients.index') }}" class="chip text-sm">&larr; Back to Clients</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-semibold">Client Information</div>
                    <a href="{{ route('admin.clients.edit', $client) }}" class="chip text-sm">Edit</a>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Company Name</div>
                        <div class="font-medium">{{ $client->company_name ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Customer Code</div>
                        <div class="font-mono">{{ $client->customer_code }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Contact Person</div>
                        <div>{{ $client->contact_person ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Email</div>
                        <div>{{ $client->email ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Phone</div>
                        <div>{{ $client->phone ?: $client->mobile ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Primary Branch</div>
                        <div>{{ $client->primaryBranch?->name ?: 'Unassigned' }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Status</div>
                        <div class="capitalize">{{ $client->status }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Customer Type</div>
                        <div class="capitalize">{{ $client->customer_type }}</div>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Recent Shipments</div>
                @if($client->shipments->count())
                    <div class="table-card">
                        <table class="dhl-table">
                            <thead>
                                <tr>
                                    <th>AWB</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->shipments as $shipment)
                                    <tr>
                                        <td class="font-mono text-sm">{{ $shipment->awb_number ?? $shipment->tracking_number }}</td>
                                        <td><span class="chip text-2xs">{{ $shipment->status }}</span></td>
                                        <td class="text-sm muted">{{ $shipment->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="muted text-sm">No shipments yet.</p>
                @endif
            </div>

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">CRM Activities</div>
                @if($client->crmActivities->count())
                    <div class="space-y-3">
                        @foreach($client->crmActivities as $activity)
                            <div class="border border-white/10 rounded p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="chip text-2xs">{{ $activity->activity_type }}</span>
                                    <span class="text-2xs muted">{{ $activity->occurred_at?->format('M d, Y H:i') }}</span>
                                </div>
                                <div class="font-medium text-sm">{{ $activity->subject }}</div>
                                @if($activity->outcome)
                                    <div class="text-2xs muted mt-1">Outcome: {{ $activity->outcome }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="muted text-sm">No CRM activities recorded.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Analytics (30 days)</div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="muted text-sm">Recent Shipments</span>
                        <span class="font-medium">{{ $analytics['recent_shipments_count'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Recent Revenue</span>
                        <span class="font-medium">{{ number_format($analytics['recent_total_spent'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Avg Order Value</span>
                        <span class="font-medium">{{ number_format($analytics['average_order_value_recent'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Days Since Last Shipment</span>
                        <span class="font-medium">{{ $analytics['days_since_last_shipment'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Risk Level</span>
                        <span class="font-medium capitalize {{ $analytics['risk_level'] === 'high' ? 'text-rose-400' : ($analytics['risk_level'] === 'medium' ? 'text-amber-400' : 'text-emerald-400') }}">
                            {{ $analytics['risk_level'] }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Credit Utilization</span>
                        <span class="font-medium">{{ number_format($analytics['credit_utilization'], 1) }}%</span>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Credit Information</div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="muted text-sm">Credit Limit</span>
                        <span class="font-medium">{{ number_format($client->credit_limit ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Current Balance</span>
                        <span class="font-medium {{ $client->is_over_credit_limit ? 'text-rose-400' : '' }}">
                            {{ number_format($client->current_balance ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Available Credit</span>
                        <span class="font-medium text-emerald-400">{{ number_format($client->available_credit, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Payment Terms</span>
                        <span class="font-medium">{{ $client->payment_terms ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Reassign Branch</div>
                <form method="POST" action="{{ route('admin.clients.reassign', $client) }}">
                    @csrf
                    @method('PATCH')
                    <select name="primary_branch_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm mb-3">
                        @foreach(\App\Models\Backend\Branch::orderBy('name')->get() as $branch)
                            <option value="{{ $branch->id }}" @selected($client->primary_branch_id == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="chip w-full justify-center">Reassign</button>
                </form>
            </div>

            @if($client->crmReminders->count())
                <div class="glass-panel p-5">
                    <div class="text-lg font-semibold mb-4">Pending Reminders</div>
                    <div class="space-y-2">
                        @foreach($client->crmReminders as $reminder)
                            <div class="border border-white/10 rounded p-2">
                                <div class="font-medium text-sm">{{ $reminder->title }}</div>
                                <div class="text-2xs muted">{{ $reminder->reminder_at?->format('M d, Y H:i') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
