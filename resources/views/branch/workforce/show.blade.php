@extends('branch.layout')

@section('title', 'Worker Details')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('branch.workforce') }}" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="flex items-center gap-3">
            @php
                $colors = ['from-emerald-500 to-teal-600', 'from-blue-500 to-indigo-600', 'from-purple-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-red-600'];
                $colorIndex = crc32($worker->user?->email ?? '') % count($colors);
            @endphp
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br {{ $colors[$colorIndex] }} flex items-center justify-center text-xl font-bold text-white shadow-lg">
                {{ strtoupper(substr($worker->user?->name ?? 'U', 0, 2)) }}
            </div>
            <div>
                <h1 class="text-xl font-bold">{{ $worker->user?->name ?? 'Unknown Worker' }}</h1>
                <div class="flex items-center gap-2 text-sm muted">
                    <span>{{ $worker->role?->label() ?? $worker->role }}</span>
                    @if($worker->isCurrentlyActive)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('branch.workforce.schedule') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Schedule
        </a>
        <a href="{{ route('branch.workforce.edit', $worker) }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
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
        {{-- Worker Information --}}
        <div class="glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-lg font-semibold">Worker Information</div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $worker->isCurrentlyActive ? 'bg-emerald-500/10 text-emerald-400 ring-1 ring-inset ring-emerald-500/20' : 'bg-zinc-500/10 text-zinc-400 ring-1 ring-inset ring-zinc-500/20' }}">
                    {{ $worker->isCurrentlyActive ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="text-2xs uppercase muted mb-1">Full Name</div>
                    <div class="font-medium">{{ $worker->user?->name ?? 'Unknown' }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">Email</div>
                    <div>{{ $worker->user?->email ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">Mobile</div>
                    <div>{{ $worker->user?->mobile ?? $worker->contact_phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">ID Number</div>
                    <div>{{ $worker->id_number ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">Role</div>
                    <div class="capitalize">{{ $worker->role?->label() ?? $worker->role }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">Employment Status</div>
                    <div class="capitalize">{{ $worker->employment_status?->label() ?? $worker->employment_status }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">Hourly Rate</div>
                    <div>{{ $worker->hourly_rate ? number_format($worker->hourly_rate, 2) : '—' }}</div>
                </div>
                <div>
                    <div class="text-2xs uppercase muted mb-1">Assigned Since</div>
                    <div>{{ $worker->assigned_at?->format('M d, Y') ?? '—' }}</div>
                </div>
            </div>
            @if($worker->notes)
                <div class="mt-4 p-3 bg-zinc-800/50 rounded-lg">
                    <div class="text-2xs uppercase muted mb-1">Notes</div>
                    <p class="text-sm">{{ $worker->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Recent Shipments --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Recent Assigned Shipments</div>
            @if($worker->assignedShipments && $worker->assignedShipments->count())
                <div class="overflow-x-auto rounded-lg border border-white/10">
                    <table class="w-full">
                        <thead class="bg-zinc-800/50">
                            <tr class="text-left text-xs uppercase text-zinc-400">
                                <th class="px-4 py-3">AWB</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Assigned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($worker->assignedShipments->take(10) as $shipment)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 font-mono text-sm">{{ $shipment->awb_number ?? $shipment->tracking_number }}</td>
                                    <td class="px-4 py-3"><span class="chip text-2xs">{{ $shipment->current_status }}</span></td>
                                    <td class="px-4 py-3 text-sm muted">{{ $shipment->assigned_at?->format('M d, H:i') ?? $shipment->created_at->format('M d, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="muted text-sm">No shipments assigned yet.</p>
            @endif
        </div>

        {{-- Attendance History --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Recent Attendance (30 days)</div>
            @if($recentAttendance->count())
                <div class="overflow-x-auto rounded-lg border border-white/10">
                    <table class="w-full">
                        <thead class="bg-zinc-800/50">
                            <tr class="text-left text-xs uppercase text-zinc-400">
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Scheduled</th>
                                <th class="px-4 py-3">Check In</th>
                                <th class="px-4 py-3">Check Out</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($recentAttendance->take(15) as $attendance)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 text-sm">{{ $attendance->shift_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($attendance->start_at && $attendance->end_at)
                                            {{ $attendance->start_at->format('H:i') }} - {{ $attendance->end_at->format('H:i') }}
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $attendance->check_in_at?->format('H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $attendance->check_out_at?->format('H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'SCHEDULED' => 'bg-zinc-500/10 text-zinc-400',
                                                'IN_PROGRESS' => 'bg-blue-500/10 text-blue-400',
                                                'LATE' => 'bg-amber-500/10 text-amber-400',
                                                'COMPLETED' => 'bg-emerald-500/10 text-emerald-400',
                                                'NO_SHOW' => 'bg-rose-500/10 text-rose-400',
                                            ];
                                        @endphp
                                        <span class="inline-flex px-2 py-0.5 rounded text-2xs font-medium {{ $statusColors[$attendance->status] ?? 'bg-zinc-500/10 text-zinc-400' }}">
                                            {{ ucfirst(str_replace('_', ' ', strtolower($attendance->status))) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="muted text-sm">No attendance records yet.</p>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        {{-- Performance Metrics --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Performance (30 days)</div>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm muted">Completed Shipments</span>
                    <span class="font-semibold text-lg">{{ $performance['completed_shipments_30_days'] }}</span>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm muted">On-Time Delivery</span>
                        <span class="font-semibold {{ $performance['on_time_delivery_rate'] >= 90 ? 'text-emerald-400' : ($performance['on_time_delivery_rate'] >= 70 ? 'text-amber-400' : 'text-rose-400') }}">
                            {{ number_format($performance['on_time_delivery_rate'], 1) }}%
                        </span>
                    </div>
                    <div class="w-full h-2 bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $performance['on_time_delivery_rate'] >= 90 ? 'bg-emerald-500' : ($performance['on_time_delivery_rate'] >= 70 ? 'bg-amber-500' : 'bg-rose-500') }} rounded-full" style="width: {{ $performance['on_time_delivery_rate'] }}%"></div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm muted">Avg Delivery Time</span>
                    <span class="font-semibold">{{ $performance['average_delivery_time_hours'] ? number_format($performance['average_delivery_time_hours'], 1) . 'h' : '—' }}</span>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm muted">Efficiency Score</span>
                        <span class="font-semibold {{ $performance['workload_efficiency'] >= 80 ? 'text-emerald-400' : ($performance['workload_efficiency'] >= 60 ? 'text-amber-400' : 'text-rose-400') }}">
                            {{ number_format($performance['workload_efficiency'], 1) }}%
                        </span>
                    </div>
                    <div class="w-full h-2 bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $performance['workload_efficiency'] >= 80 ? 'bg-emerald-500' : ($performance['workload_efficiency'] >= 60 ? 'bg-amber-500' : 'bg-rose-500') }} rounded-full" style="width: {{ $performance['workload_efficiency'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Workload --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Current Workload</div>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm muted">Active Shipments</span>
                    <span class="font-semibold">{{ $workload['active_shipments'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm muted">Pending Tasks</span>
                    <span class="font-semibold">{{ $workload['pending_tasks'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm muted">Today's Shipments</span>
                    <span class="font-semibold">{{ $workload['today_shipments'] }}</span>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm muted">Capacity</span>
                        <span class="font-semibold">{{ number_format($workload['capacity_utilization'], 0) }}%</span>
                    </div>
                    <div class="w-full h-2 bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $workload['capacity_utilization'] > 80 ? 'bg-rose-500' : ($workload['capacity_utilization'] > 50 ? 'bg-amber-500' : 'bg-emerald-500') }} rounded-full" style="width: {{ $workload['capacity_utilization'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Summary --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Attendance Summary</div>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-zinc-800/50 rounded-lg">
                    <div class="text-2xl font-bold">{{ $attendanceStats['total_shifts'] }}</div>
                    <div class="text-2xs muted">Total Shifts</div>
                </div>
                <div class="text-center p-3 bg-emerald-500/10 rounded-lg">
                    <div class="text-2xl font-bold text-emerald-400">{{ $attendanceStats['completed'] }}</div>
                    <div class="text-2xs muted">Completed</div>
                </div>
                <div class="text-center p-3 bg-amber-500/10 rounded-lg">
                    <div class="text-2xl font-bold text-amber-400">{{ $attendanceStats['late'] }}</div>
                    <div class="text-2xs muted">Late</div>
                </div>
                <div class="text-center p-3 bg-rose-500/10 rounded-lg">
                    <div class="text-2xl font-bold text-rose-400">{{ $attendanceStats['no_show'] }}</div>
                    <div class="text-2xs muted">No Show</div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="glass-panel p-5">
            <div class="text-lg font-semibold mb-4">Quick Actions</div>
            <div class="space-y-2">
                <a href="{{ route('branch.workforce.schedule') }}" class="flex items-center gap-2 px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm">Schedule Shift</span>
                </a>
                <form method="POST" action="{{ route('branch.workforce.archive', $worker) }}" onsubmit="return confirm('Are you sure you want to archive this worker?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 bg-rose-600/20 hover:bg-rose-600/30 rounded-lg transition-colors text-rose-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <span class="text-sm">Archive Worker</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
