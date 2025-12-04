@extends('branch.layout')

@section('title', 'Client Details')
@section('header', $client->display_name)

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('branch.clients.index') }}" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="flex items-center gap-3">
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
                        @if($client->customer_type === 'vip')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                VIP
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('branch.clients.quick-shipment', $client) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Shipment
            </a>
            <a href="{{ route('branch.clients.statement', $client) }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Statement
            </a>
            <a href="{{ route('branch.clients.edit', $client) }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/20 border border-emerald-500/30 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-2 text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-semibold">Client Information</div>
                    <div class="flex items-center gap-2">
                        @php
                            $statusColors = [
                                'active' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                'inactive' => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
                                'suspended' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                'blacklisted' => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
                            ];
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border {{ $statusColors[$client->status] ?? $statusColors['inactive'] }}">
                            {{ ucfirst($client->status ?? 'unknown') }}
                        </span>
                    </div>
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
                        <div class="text-2xs uppercase muted mb-1">Status</div>
                        <div class="capitalize">{{ $client->status }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Customer Type</div>
                        <div class="capitalize">{{ $client->customer_type }}</div>
                    </div>
                    <div>
                        <div class="text-2xs uppercase muted mb-1">Account Manager</div>
                        <div>{{ $client->accountManager?->name ?: 'Unassigned' }}</div>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Recent Shipments</div>
                @if($client->shipments->count())
                    <div class="overflow-x-auto rounded-lg border border-white/10">
                        <table class="w-full">
                            <thead class="bg-zinc-800/50">
                                <tr class="text-left text-xs uppercase text-zinc-400">
                                    <th class="px-4 py-3">AWB</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach($client->shipments as $shipment)
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-3 font-mono text-sm">{{ $shipment->awb_number ?? $shipment->tracking_number }}</td>
                                        <td class="px-4 py-3"><span class="chip text-2xs">{{ $shipment->status }}</span></td>
                                        <td class="px-4 py-3 text-sm muted">{{ $shipment->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="muted text-sm">No shipments yet.</p>
                @endif
            </div>

            @if($client->crmActivities && $client->crmActivities->count())
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">CRM Activities</div>
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
            </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Analytics (30 days)</div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="muted text-sm">Recent Shipments</span>
                        <span class="font-medium">{{ $analytics['recent_shipments_count'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Recent Revenue</span>
                        <span class="font-medium">{{ number_format($analytics['recent_total_spent'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Avg Order Value</span>
                        <span class="font-medium">{{ number_format($analytics['average_order_value_recent'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Days Since Last Shipment</span>
                        <span class="font-medium">{{ $analytics['days_since_last_shipment'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Risk Level</span>
                        <span class="font-medium capitalize {{ ($analytics['risk_level'] ?? 'low') === 'high' ? 'text-rose-400' : (($analytics['risk_level'] ?? 'low') === 'medium' ? 'text-amber-400' : 'text-emerald-400') }}">
                            {{ $analytics['risk_level'] ?? 'low' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Credit Utilization</span>
                        <span class="font-medium">{{ number_format($analytics['credit_utilization'] ?? 0, 1) }}%</span>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-semibold">Credit Information</div>
                    <button type="button" onclick="document.getElementById('creditModal').classList.remove('hidden')" class="text-xs text-emerald-400 hover:text-emerald-300">
                        Adjust Balance
                    </button>
                </div>
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
                        <span class="font-medium text-emerald-400">{{ number_format($client->available_credit ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="muted text-sm">Payment Terms</span>
                        <span class="font-medium">{{ $client->payment_terms ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Log Activity --}}
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Log Activity</div>
                <form method="POST" action="{{ route('branch.clients.activity.store', $client) }}" class="space-y-3">
                    @csrf
                    <select name="activity_type" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="">Select Activity Type</option>
                        <option value="call">Phone Call</option>
                        <option value="email">Email</option>
                        <option value="meeting">Meeting</option>
                        <option value="note">Note</option>
                        <option value="complaint">Complaint</option>
                        <option value="follow_up">Follow Up</option>
                    </select>
                    <input type="text" name="subject" required placeholder="Subject" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <textarea name="description" rows="2" placeholder="Description (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm"></textarea>
                    <input type="text" name="outcome" placeholder="Outcome (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <button type="submit" class="chip w-full justify-center bg-emerald-600 hover:bg-emerald-700">Log Activity</button>
                </form>
            </div>

            {{-- Add Reminder --}}
            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Add Reminder</div>
                <form method="POST" action="{{ route('branch.clients.reminder.store', $client) }}" class="space-y-3">
                    @csrf
                    <input type="text" name="title" required placeholder="Reminder Title" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <input type="datetime-local" name="reminder_at" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                    <select name="priority" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="normal">Normal Priority</option>
                        <option value="low">Low Priority</option>
                        <option value="high">High Priority</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <textarea name="description" rows="2" placeholder="Notes (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm"></textarea>
                    <button type="submit" class="chip w-full justify-center bg-blue-600 hover:bg-blue-700">Add Reminder</button>
                </form>
            </div>

            @if($client->crmReminders && $client->crmReminders->count())
                <div class="glass-panel p-5">
                    <div class="text-lg font-semibold mb-4">Pending Reminders</div>
                    <div class="space-y-2">
                        @foreach($client->crmReminders as $reminder)
                            <div class="border border-white/10 rounded p-3">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="font-medium text-sm">{{ $reminder->title }}</div>
                                        <div class="text-2xs muted">{{ $reminder->reminder_at?->format('M d, Y H:i') }}</div>
                                        @if($reminder->priority === 'urgent' || $reminder->priority === 'high')
                                            <span class="inline-flex text-2xs px-1.5 py-0.5 rounded mt-1 {{ $reminder->priority === 'urgent' ? 'bg-rose-500/20 text-rose-400' : 'bg-amber-500/20 text-amber-400' }}">
                                                {{ ucfirst($reminder->priority) }}
                                            </span>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('branch.clients.reminder.complete', [$client, $reminder]) }}">
                                        @csrf
                                        <button type="submit" class="p-1 rounded hover:bg-emerald-500/20 text-emerald-400" title="Mark Complete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Quick Actions</div>
                <div class="space-y-2">
                    <a href="{{ route('branch.clients.contracts', $client) }}" class="flex items-center gap-2 px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-sm">View Contracts</span>
                    </a>
                    <form method="POST" action="{{ route('branch.clients.refresh-stats', $client) }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="text-sm">Refresh Statistics</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Credit Adjustment Modal --}}
    <div id="creditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="glass-panel p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Adjust Credit Balance</h3>
                <button type="button" onclick="document.getElementById('creditModal').classList.add('hidden')" class="p-1 rounded hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('branch.clients.adjust-credit', $client) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Adjustment Type</label>
                    <select name="adjustment_type" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="payment">Payment (Decrease Balance)</option>
                        <option value="credit">Credit (Decrease Balance)</option>
                        <option value="debit">Debit (Increase Balance)</option>
                        <option value="correction">Correction (Set Balance)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Amount</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Reason</label>
                    <textarea name="reason" rows="2" required placeholder="Reason for adjustment..." class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium">Apply</button>
                    <button type="button" onclick="document.getElementById('creditModal').classList.add('hidden')" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection
