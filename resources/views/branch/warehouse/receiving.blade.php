@extends('branch.layout')

@section('title', 'Receiving Dock')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Receiving Dock</h2>
            <p class="text-sm text-zinc-400">Process incoming shipments and putaway</p>
        </div>
        <div class="flex gap-2">
            <span class="chip bg-emerald-500/20 text-emerald-400">Received Today: {{ $receivedToday }}</span>
            <a href="{{ route('branch.warehouse.index') }}" class="chip">Back to Warehouse</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Scan & Receive -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Receive Shipment</div>
            <form method="POST" action="{{ route('branch.warehouse.receiving.process') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs muted mb-1">Tracking Number *</label>
                    <input type="text" name="tracking_number" required autofocus placeholder="Scan or enter tracking #" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Location</label>
                    <select name="location_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        <option value="">Select location</option>
                        @foreach($receivingLocations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->code }} - {{ $loc->name ?? $loc->type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Condition</label>
                    <select name="condition" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        <option value="good">Good</option>
                        <option value="damaged">Damaged</option>
                        <option value="partial">Partial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2"></textarea>
                </div>
                <button type="submit" class="chip w-full justify-center bg-emerald-600 hover:bg-emerald-500">Receive Shipment</button>
            </form>
        </div>

        <!-- Expected Arrivals -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Expected Arrivals</div>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($expectedArrivals as $shipment)
                    <div class="p-3 border border-white/5 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm text-emerald-400">{{ $shipment->tracking_number }}</span>
                            <span class="chip text-2xs">{{ $shipment->originBranch?->code ?? '?' }}</span>
                        </div>
                        <div class="text-xs text-zinc-500 mt-1">{{ $shipment->customer?->name ?? 'N/A' }}</div>
                    </div>
                @empty
                    <p class="muted text-sm text-center py-4">No expected arrivals</p>
                @endforelse
            </div>
        </div>

        <!-- Pending Putaway -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Pending Putaway</div>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($pendingPutaway as $shipment)
                    <div class="p-3 border border-amber-500/30 bg-amber-500/10 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm">{{ $shipment->tracking_number }}</span>
                            <span class="text-xs text-amber-400">{{ $shipment->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-xs text-zinc-500 mt-1">From: {{ $shipment->originBranch?->code ?? '?' }}</div>
                    </div>
                @empty
                    <p class="muted text-sm text-center py-4">All shipments assigned locations</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
