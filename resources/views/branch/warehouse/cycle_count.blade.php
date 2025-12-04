@extends('branch.layout')

@section('title', 'Cycle Count')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Cycle Count</h2>
            <p class="text-sm text-zinc-400">Verify inventory counts by location</p>
        </div>
        <a href="{{ route('branch.warehouse.index') }}" class="chip">Back to Warehouse</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Locations to Count -->
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Locations</div>
            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Type</th>
                            <th>System Count</th>
                            <th>Last Counted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $loc)
                            <tr>
                                <td class="font-semibold">{{ $loc->code }}</td>
                                <td><span class="chip text-2xs">{{ $loc->type }}</span></td>
                                <td class="text-center">{{ $loc->shipments_count ?? 0 }}</td>
                                <td class="text-sm text-zinc-400">
                                    {{ $loc->last_counted_at ? \Carbon\Carbon::parse($loc->last_counted_at)->diffForHumans() : 'Never' }}
                                </td>
                                <td>
                                    <button onclick="openCountModal('{{ $loc->id }}', '{{ $loc->code }}', {{ $loc->shipments_count ?? 0 }})" class="chip text-xs">
                                        Count
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-8 muted">No locations configured</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Counts & Discrepancies -->
        <div class="space-y-4">
            @if($discrepancies->count() > 0)
                <div class="glass-panel p-5 border border-rose-500/30">
                    <div class="text-lg font-semibold text-rose-400 mb-4">Discrepancies</div>
                    <div class="space-y-2">
                        @foreach($discrepancies as $disc)
                            <div class="p-3 border border-rose-500/20 rounded-lg">
                                <div class="flex justify-between">
                                    <span class="font-medium">Location #{{ $disc->location_id }}</span>
                                    <span class="text-rose-400">{{ $disc->discrepancy > 0 ? '+' : '' }}{{ $disc->discrepancy }}</span>
                                </div>
                                <div class="text-xs text-zinc-500 mt-1">
                                    {{ \Carbon\Carbon::parse($disc->counted_at)->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Recent Counts</div>
                <div class="space-y-2">
                    @forelse($recentCounts as $count)
                        <div class="p-3 border border-white/5 rounded-lg">
                            <div class="flex justify-between">
                                <span class="font-medium">Location #{{ $count->location_id }}</span>
                                <span class="{{ $count->discrepancy == 0 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $count->actual_count }} / {{ $count->expected_count }}
                                </span>
                            </div>
                            <div class="text-xs text-zinc-500 mt-1">
                                {{ \Carbon\Carbon::parse($count->counted_at)->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <p class="muted text-sm text-center">No recent counts</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Count Modal -->
<div id="countModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-obsidian-800 rounded-xl p-6 w-full max-w-md">
        <div class="text-lg font-semibold mb-4">Record Cycle Count</div>
        <form method="POST" action="{{ route('branch.warehouse.cycle-count.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="location_id" id="countLocationId">
            <div>
                <label class="block text-sm text-zinc-400 mb-1">Location</label>
                <input type="text" id="countLocationCode" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2" readonly>
            </div>
            <div>
                <label class="block text-sm text-zinc-400 mb-1">System Count (Expected)</label>
                <input type="number" name="expected_count" id="countExpected" readonly class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-zinc-400 mb-1">Actual Count *</label>
                <input type="number" name="actual_count" required min="0" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-zinc-400 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCountModal()" class="flex-1 py-2 bg-white/5 hover:bg-white/10 rounded-lg">Cancel</button>
                <button type="submit" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg">Submit Count</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCountModal(locationId, code, expected) {
    document.getElementById('countLocationId').value = locationId;
    document.getElementById('countLocationCode').value = code;
    document.getElementById('countExpected').value = expected;
    document.getElementById('countModal').classList.remove('hidden');
    document.getElementById('countModal').classList.add('flex');
}
function closeCountModal() {
    document.getElementById('countModal').classList.add('hidden');
    document.getElementById('countModal').classList.remove('flex');
}
</script>
@endpush
@endsection
