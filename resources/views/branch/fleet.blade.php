@extends('branch.layout')

@section('title', 'Fleet')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Vehicles</div>
                    <p class="muted text-xs">Track utilization, health, and downtime.</p>
                </div>
                <span class="pill-soft">{{ $vehicles->count() }} units</span>
            </div>
            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Plate</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $vehicle)
                            <tr>
                                <td class="font-semibold text-sm">{{ $vehicle->registration ?? $vehicle->plate_no ?? 'Vehicle '.$vehicle->id }}</td>
                                <td class="muted text-xs">{{ $vehicle->type ?? $vehicle->model }}</td>
                                <td class="muted text-xs">{{ $vehicle->capacity_kg ?? $vehicle->capacity_volume ?? '—' }}</td>
                                <td><span class="chip text-2xs">{{ $vehicle->status ?? 'UNKNOWN' }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('branch.fleet.vehicle.update', $vehicle) }}" class="flex items-center gap-2 text-xs">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="status" value="{{ $vehicle->status }}" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 w-24">
                                        <button class="chip text-2xs" type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 muted">No vehicles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="glass-panel p-4 space-y-2">
                <div class="text-sm font-semibold">Driver roster</div>
                @forelse($rosters as $roster)
                    <div class="border border-white/5 rounded-lg p-3">
                        <div class="font-semibold text-sm">{{ $roster->driver?->user?->name ?? 'Driver '.$roster->driver_id }}</div>
                        <div class="muted text-2xs">{{ $roster->shift_type }} • {{ $roster->start_time }} → {{ $roster->end_time }}</div>
                        <span class="chip text-2xs">{{ $roster->status->name ?? $roster->status }}</span>
                    </div>
                @empty
                    <p class="muted text-sm">No rosters scheduled.</p>
                @endforelse
            </div>

            <div class="glass-panel p-4 space-y-2">
                <div class="text-sm font-semibold">Schedule roster</div>
                <form method="POST" action="{{ route('branch.fleet.roster.store') }}" class="space-y-2 text-sm">
                    @csrf
                    <select name="driver_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        @foreach($driverPool as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->user?->name ?? 'Driver '.$driver->id }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="shift_type" placeholder="Shift type" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="datetime-local" name="start_time" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <input type="datetime-local" name="end_time" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <button class="chip w-full justify-center" type="submit">Schedule</button>
                </form>
            </div>

            <div class="glass-panel p-4 space-y-2">
                <div class="text-sm font-semibold">Downtime alerts</div>
                @forelse($downtimeAlerts as $alert)
                    <div class="border border-white/5 rounded-lg p-3">
                        <div class="font-semibold text-sm">{{ $alert->title }}</div>
                        <p class="muted text-xs">{{ $alert->message }}</p>
                    </div>
                @empty
                    <p class="muted text-sm">No fleet alerts.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
