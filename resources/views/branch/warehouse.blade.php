@extends('branch.layout')

@section('title', 'Warehouse')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Storage & flow</div>
                    <p class="muted text-xs">Inbound vs outbound visibility with capacity signals.</p>
                </div>
                <div class="flex gap-2">
                    <span class="chip text-2xs">Inbound: {{ $inbound }}</span>
                    <span class="chip text-2xs">Outbound: {{ $outbound }}</span>
                </div>
            </div>
            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                            <tr>
                                <td class="font-semibold text-sm">{{ $location->code }}</td>
                                <td class="muted text-xs">{{ $location->type }}</td>
                                <td class="muted text-xs">{{ $location->capacity ?? '—' }}</td>
                                <td><span class="chip text-2xs">{{ $location->status }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('branch.warehouse.update', $location) }}" class="flex items-center gap-2 text-xs">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="status" value="{{ $location->status }}" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 w-24">
                                        <input type="number" name="capacity" value="{{ $location->capacity }}" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 w-20">
                                        <button class="chip text-2xs" type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 muted">No locations defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="glass-panel p-4 space-y-2">
                <div class="text-sm font-semibold">Add location</div>
                <form method="POST" action="{{ route('branch.warehouse.store') }}" class="space-y-2 text-sm">
                    @csrf
                    <input type="text" name="code" placeholder="Code" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="type" placeholder="Type" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="number" name="capacity" placeholder="Capacity" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="text" name="status" placeholder="Status" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <button class="chip w-full justify-center" type="submit">Save location</button>
                </form>
            </div>
            <div class="glass-panel p-4 space-y-2">
                <div class="text-sm font-semibold">Alerts</div>
                @forelse($alerts as $alert)
                    <div class="border border-white/5 rounded-lg p-3">
                        <div class="font-semibold text-sm">{{ $alert->title }}</div>
                        <p class="muted text-xs">{{ $alert->message }}</p>
                    </div>
                @empty
                    <p class="muted text-sm">No warehouse alerts.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
