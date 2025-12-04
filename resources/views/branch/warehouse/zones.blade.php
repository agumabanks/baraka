@extends('branch.layout')

@section('title', 'Warehouse Zones')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Warehouse Zones</h2>
            <p class="text-sm text-zinc-400">Manage warehouse zones and bin locations</p>
        </div>
        <a href="{{ route('branch.warehouse.index') }}" class="chip">Back to Warehouse</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Zones List -->
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Zone Layout</div>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse($zones as $zone)
                    <div class="p-4 border border-white/10 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="font-semibold">{{ $zone->code }}</span>
                                @if($zone->name)
                                    <span class="text-zinc-400 text-sm ml-2">{{ $zone->name }}</span>
                                @endif
                            </div>
                            <span class="chip text-2xs">{{ $zone->type }}</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-zinc-400">
                            <span>Capacity: {{ $zone->capacity ?? '∞' }}</span>
                            <span>Current: {{ $zone->shipments_count ?? 0 }}</span>
                        </div>
                        @if($zone->children && $zone->children->count() > 0)
                            <div class="mt-3 pt-3 border-t border-white/5">
                                <div class="text-xs text-zinc-500 mb-2">Sub-locations:</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($zone->children as $child)
                                        <span class="px-2 py-1 bg-white/5 rounded text-xs">
                                            {{ $child->code }} ({{ $child->shipments_count ?? 0 }})
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="col-span-2 text-center py-8 muted">No zones configured</div>
                @endforelse
            </div>
        </div>

        <!-- Add Zone -->
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Add Zone</div>
            <form method="POST" action="{{ route('branch.warehouse.zones.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs muted mb-1">Code *</label>
                    <input type="text" name="code" required placeholder="e.g., RECV-01" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Name</label>
                    <input type="text" name="name" placeholder="e.g., Receiving Bay 1" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Type *</label>
                    <select name="type" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        @foreach($zoneTypes as $type)
                            <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Parent Zone</label>
                    <select name="parent_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        <option value="">None (Top Level)</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs muted mb-1">Capacity</label>
                    <input type="number" name="capacity" placeholder="Max items" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="temperature_controlled" value="1" id="tempControl" class="rounded">
                    <label for="tempControl" class="text-sm text-zinc-400">Temperature Controlled</label>
                </div>
                <button type="submit" class="chip w-full justify-center">Create Zone</button>
            </form>
        </div>
    </div>
</div>
@endsection
