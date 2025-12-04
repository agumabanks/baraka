@extends('branch.layout')

@section('title', 'Exception Management')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Exception Management</h1>
            <p class="text-sm muted">Track, manage, and resolve shipment exceptions</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('branch.operations') }}" class="btn btn-ghost">Back to Operations</a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="stat-card border-l-4 border-red-500">
            <div class="muted text-xs uppercase">Open Exceptions</div>
            <div class="text-2xl font-bold text-red-400">{{ $stats['total_open'] }}</div>
            <div class="muted text-2xs">Requiring attention</div>
        </div>
        <div class="stat-card border-l-4 border-red-600">
            <div class="muted text-xs uppercase">Critical</div>
            <div class="text-2xl font-bold text-red-500">{{ $stats['critical'] }}</div>
            <div class="muted text-2xs">Immediate action</div>
        </div>
        <div class="stat-card border-l-4 border-orange-500">
            <div class="muted text-xs uppercase">High Priority</div>
            <div class="text-2xl font-bold text-orange-400">{{ $stats['high'] }}</div>
            <div class="muted text-2xs">Today's focus</div>
        </div>
        <div class="stat-card border-l-4 border-emerald-500">
            <div class="muted text-xs uppercase">Resolved Today</div>
            <div class="text-2xl font-bold text-emerald-400">{{ $stats['total_resolved_today'] }}</div>
            <div class="muted text-2xs">Good progress</div>
        </div>
        <div class="stat-card border-l-4 border-sky-500">
            <div class="muted text-xs uppercase">Avg Resolution</div>
            <div class="text-2xl font-bold text-sky-400">{{ $stats['avg_resolution_hours'] }}h</div>
            <div class="muted text-2xs">Last 30 days</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-panel p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs muted mb-1">Status</label>
                <select name="status" class="input w-full" onchange="this.form.submit()">
                    <option value="open" {{ $statusFilter === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="resolved" {{ $statusFilter === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs muted mb-1">Category</label>
                <select name="category" class="input w-full" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ $categoryFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs muted mb-1">Severity</label>
                <select name="severity" class="input w-full" onchange="this.form.submit()">
                    <option value="">All Severities</option>
                    @foreach($severities as $key => $label)
                        <option value="{{ $key }}" {{ $severityFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('branch.exceptions.index') }}" class="btn btn-ghost">Reset</a>
        </form>
    </div>

    {{-- Exceptions Table --}}
    <div class="glass-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-white/10">
                    <tr>
                        <th class="text-left p-4 text-xs uppercase muted font-medium">Shipment</th>
                        <th class="text-left p-4 text-xs uppercase muted font-medium">Category</th>
                        <th class="text-left p-4 text-xs uppercase muted font-medium">Severity</th>
                        <th class="text-left p-4 text-xs uppercase muted font-medium">Status</th>
                        <th class="text-left p-4 text-xs uppercase muted font-medium">Flagged</th>
                        <th class="text-left p-4 text-xs uppercase muted font-medium">Assigned To</th>
                        <th class="text-right p-4 text-xs uppercase muted font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($exceptions as $shipment)
                        <tr class="hover:bg-white/5 transition">
                            <td class="p-4">
                                <div class="font-mono font-semibold">#{{ $shipment->tracking_number }}</div>
                                <div class="text-xs muted">{{ $shipment->customer?->name ?? $shipment->customer?->company_name ?? 'Walk-in' }}</div>
                                <div class="text-xs muted">{{ $shipment->originBranch?->code }} → {{ $shipment->destBranch?->code }}</div>
                            </td>
                            <td class="p-4">
                                <span class="chip text-xs">{{ $categories[$shipment->exception_category ?? 'other'] ?? 'Unknown' }}</span>
                            </td>
                            <td class="p-4">
                                @php
                                    $severity = $shipment->exception_severity ?? 'medium';
                                    $severityClass = match($severity) {
                                        'critical' => 'bg-red-500/20 text-red-400 border-red-500/50',
                                        'high' => 'bg-orange-500/20 text-orange-400 border-orange-500/50',
                                        'medium' => 'bg-amber-500/20 text-amber-400 border-amber-500/50',
                                        'low' => 'bg-sky-500/20 text-sky-400 border-sky-500/50',
                                        default => 'bg-zinc-500/20 text-zinc-400 border-zinc-500/50',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border {{ $severityClass }}">
                                    {{ ucfirst($severity) }}
                                </span>
                            </td>
                            <td class="p-4">
                                @if($shipment->exception_resolved_at)
                                    <span class="badge badge-success">Resolved</span>
                                @elseif($shipment->exception_escalated_at ?? false)
                                    <span class="badge badge-warn">Escalated</span>
                                @else
                                    <span class="badge badge-danger">Open</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="text-sm">{{ $shipment->exception_flagged_at?->diffForHumans() ?? 'N/A' }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm">{{ $shipment->assignedWorker?->user?->name ?? 'Unassigned' }}</div>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button onclick="openResolveModal({{ $shipment->id }}, '{{ $shipment->tracking_number }}')" 
                                            class="chip text-xs hover:bg-emerald-500/20"
                                            {{ $shipment->exception_resolved_at ? 'disabled' : '' }}>
                                        Resolve
                                    </button>
                                    <button onclick="openEscalateModal({{ $shipment->id }}, '{{ $shipment->tracking_number }}')"
                                            class="chip text-xs hover:bg-amber-500/20"
                                            {{ $shipment->exception_resolved_at || ($shipment->exception_escalated_at ?? false) ? 'disabled' : '' }}>
                                        Escalate
                                    </button>
                                    <a href="{{ route('branch.shipments.show', $shipment) }}" class="chip text-xs hover:bg-sky-500/20">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center muted">
                                <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>No exceptions found matching your criteria</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($exceptions->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $exceptions->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Resolve Modal --}}
<div id="resolveModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
    <div class="glass-panel p-6 max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Resolve Exception</h3>
            <button onclick="closeResolveModal()" class="muted hover:text-white">&times;</button>
        </div>
        <form id="resolveForm" method="POST">
            @csrf
            <input type="hidden" name="shipment_id" id="resolve_shipment_id">
            
            <div class="mb-4">
                <label class="block text-sm mb-1">Shipment</label>
                <div class="font-mono font-semibold" id="resolve_tracking"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm mb-1">Resolution Type *</label>
                <select name="resolution_type" class="input w-full" required>
                    <option value="resolved">Resolved - Issue Fixed</option>
                    <option value="rerouted">Rerouted - Different Route</option>
                    <option value="returned">Returned - Back to Sender</option>
                    <option value="cancelled">Cancelled - Shipment Cancelled</option>
                    <option value="escalated">Escalated - Needs HQ Review</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm mb-1">Resolution Notes *</label>
                <textarea name="resolution" rows="3" class="input w-full" required placeholder="Describe how the exception was resolved..."></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm mb-1">New Status (Optional)</label>
                <select name="new_status" class="input w-full">
                    <option value="">Keep Current Status</option>
                    <option value="IN_TRANSIT">In Transit</option>
                    <option value="OUT_FOR_DELIVERY">Out for Delivery</option>
                    <option value="DELIVERED">Delivered</option>
                    <option value="RETURNED">Returned</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="notify_customer" value="1" class="rounded">
                    <span class="text-sm">Notify customer of resolution</span>
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeResolveModal()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Resolve Exception</button>
            </div>
        </form>
    </div>
</div>

{{-- Escalate Modal --}}
<div id="escalateModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
    <div class="glass-panel p-6 max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Escalate to HQ</h3>
            <button onclick="closeEscalateModal()" class="muted hover:text-white">&times;</button>
        </div>
        <form id="escalateForm" method="POST">
            @csrf
            <input type="hidden" name="shipment_id" id="escalate_shipment_id">
            
            <div class="mb-4">
                <label class="block text-sm mb-1">Shipment</label>
                <div class="font-mono font-semibold" id="escalate_tracking"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm mb-1">Priority *</label>
                <select name="priority" class="input w-full" required>
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm mb-1">Reason for Escalation *</label>
                <textarea name="reason" rows="3" class="input w-full" required placeholder="Explain why this needs HQ attention..."></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEscalateModal()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-warn">Escalate to HQ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openResolveModal(shipmentId, trackingNumber) {
    document.getElementById('resolve_shipment_id').value = shipmentId;
    document.getElementById('resolve_tracking').textContent = '#' + trackingNumber;
    document.getElementById('resolveForm').action = '/branch/exceptions/' + shipmentId + '/resolve';
    document.getElementById('resolveModal').classList.remove('hidden');
    document.getElementById('resolveModal').classList.add('flex');
}

function closeResolveModal() {
    document.getElementById('resolveModal').classList.add('hidden');
    document.getElementById('resolveModal').classList.remove('flex');
}

function openEscalateModal(shipmentId, trackingNumber) {
    document.getElementById('escalate_shipment_id').value = shipmentId;
    document.getElementById('escalate_tracking').textContent = '#' + trackingNumber;
    document.getElementById('escalateForm').action = '/branch/exceptions/' + shipmentId + '/escalate';
    document.getElementById('escalateModal').classList.remove('hidden');
    document.getElementById('escalateModal').classList.add('flex');
}

function closeEscalateModal() {
    document.getElementById('escalateModal').classList.add('hidden');
    document.getElementById('escalateModal').classList.remove('flex');
}

// Close modals on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeResolveModal();
        closeEscalateModal();
    }
});
</script>
@endpush
@endsection
