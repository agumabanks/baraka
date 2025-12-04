@extends('branch.layout')

@section('title', 'Capacity Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Capacity Report</h2>
            <p class="text-sm text-zinc-400">Warehouse utilization and capacity planning</p>
        </div>
        <a href="{{ route('branch.warehouse.index') }}" class="chip">Back to Warehouse</a>
    </div>

    <!-- Overall Stats -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Total Capacity</div>
            <div class="text-2xl font-bold">{{ number_format($totalCapacity) }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Currently Used</div>
            <div class="text-2xl font-bold text-blue-400">{{ number_format($totalUsed) }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Available</div>
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($totalCapacity - $totalUsed) }}</div>
        </div>
        <div class="glass-panel p-5">
            <div class="text-2xs uppercase muted mb-1">Utilization</div>
            <div class="text-2xl font-bold {{ $overallUtilization > 80 ? 'text-rose-400' : ($overallUtilization > 60 ? 'text-amber-400' : 'text-emerald-400') }}">
                {{ $overallUtilization }}%
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Location Breakdown -->
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Location Utilization</div>
            <div class="space-y-3">
                @foreach($locations as $loc)
                    <div class="p-3 border border-white/5 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="font-semibold">{{ $loc->code }}</span>
                                <span class="text-xs text-zinc-500 ml-2">{{ $loc->type }}</span>
                            </div>
                            <span class="text-sm {{ $loc->utilization > 80 ? 'text-rose-400' : ($loc->utilization > 60 ? 'text-amber-400' : 'text-emerald-400') }}">
                                {{ $loc->utilization }}%
                            </span>
                        </div>
                        <div class="w-full bg-white/10 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $loc->utilization > 80 ? 'bg-rose-500' : ($loc->utilization > 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($loc->utilization, 100) }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-zinc-500 mt-1">
                            <span>{{ $loc->shipments_count ?? 0 }} / {{ $loc->capacity }} items</span>
                            <span>{{ $loc->capacity - ($loc->shipments_count ?? 0) }} available</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Critical Zones & Trend -->
        <div class="space-y-4">
            @if($criticalZones->count() > 0)
                <div class="glass-panel p-5 border border-rose-500/30">
                    <div class="text-lg font-semibold text-rose-400 mb-4">Critical Zones</div>
                    <div class="space-y-2">
                        @foreach($criticalZones as $zone)
                            <div class="flex items-center justify-between py-2 border-b border-rose-500/20">
                                <span class="font-medium">{{ $zone->code }}</span>
                                <span class="text-rose-400">{{ $zone->utilization }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">7-Day Trend</div>
                <div class="space-y-2">
                    @foreach($historicalTrend as $day)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-400">{{ \Carbon\Carbon::parse($day['date'])->format('D') }}</span>
                            <span>{{ $day['count'] }} items</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="glass-panel p-5">
                <div class="text-lg font-semibold mb-4">Actions</div>
                <div class="space-y-2">
                    <a href="{{ route('branch.warehouse.cycle-count') }}" class="chip w-full justify-center">Cycle Count</a>
                    <a href="{{ route('branch.warehouse.zones') }}" class="chip w-full justify-center">Manage Zones</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
