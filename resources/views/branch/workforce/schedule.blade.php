@extends('branch.layout')

@section('title', 'Workforce Scheduling')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Workforce Scheduling</h1>
        <p class="text-sm muted">Manage shifts, attendance, and worker schedules</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('branch.workforce') }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Team Roster
        </a>
        <button type="button" onclick="document.getElementById('scheduleModal').classList.remove('hidden')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Schedule Shift
        </button>
    </div>
</div>

{{-- Quick Stats --}}
<div class="grid gap-4 md:grid-cols-4 mb-6">
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">On Duty Today</div>
                <div class="text-2xl font-bold text-emerald-400">{{ $onDutyCount }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Scheduled Today</div>
                <div class="text-2xl font-bold text-blue-400">{{ $scheduledToday }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">Late Check-ins</div>
                <div class="text-2xl font-bold text-amber-400">{{ $lateCount }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="glass-panel p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-2xs uppercase muted mb-1">No-Shows</div>
                <div class="text-2xl font-bold text-rose-400">{{ $noShowCount }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
        </div>
    </div>
</div>

{{-- Weekly Schedule --}}
<div class="glass-panel mb-6">
    <div class="p-4 border-b border-white/10 flex items-center justify-between">
        <div class="text-lg font-semibold">Weekly Schedule</div>
        <div class="flex items-center gap-2">
            <a href="{{ route('branch.workforce.schedule', ['week_offset' => $weekOffset - 1]) }}" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="px-3 py-1 bg-zinc-800 rounded-lg text-sm">Week of {{ $startOfWeek->format('M d, Y') }}</span>
            <a href="{{ route('branch.workforce.schedule', ['week_offset' => $weekOffset + 1]) }}" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-zinc-800/50">
                <tr class="text-xs uppercase text-zinc-400">
                    <th class="px-4 py-3 text-left font-medium" style="width: 180px;">Worker</th>
                    @foreach($weekDays as $day)
                        <th class="px-2 py-3 text-center font-medium {{ $day['is_today'] ? 'bg-emerald-500/10' : '' }}" style="min-width: 120px;">
                            <div>{{ $day['short'] }}</div>
                            <div class="text-zinc-500 font-normal">{{ $day['display'] }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($workers as $worker)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3">
                            <a href="{{ route('branch.workforce.show', $worker) }}" class="font-medium hover:text-emerald-400 transition-colors">{{ $worker->user->name }}</a>
                            <div class="text-2xs text-zinc-500">{{ $worker->role?->label() ?? 'Staff' }}</div>
                        </td>
                        @foreach($weekDays as $day)
                            <td class="px-2 py-2 text-center {{ $day['is_today'] ? 'bg-emerald-500/5' : '' }}">
                                @php $shifts = $scheduleMatrix[$worker->id][$day['date']] ?? []; @endphp
                                @forelse($shifts as $shift)
                                    <div class="mb-1 px-2 py-1 rounded text-xs {{ $shift['status_class'] }} cursor-pointer" onclick="showShiftDetails({{ json_encode($shift) }})">
                                        <div class="font-medium">{{ $shift['time_range'] }}</div>
                                        <div class="text-2xs opacity-80">{{ $shift['status_label'] }}</div>
                                    </div>
                                @empty
                                    <button type="button" onclick="openScheduleModal({{ $worker->id }}, '{{ $day['date'] }}')" class="w-full px-2 py-2 border border-dashed border-white/10 rounded text-zinc-600 hover:border-emerald-500/50 hover:text-emerald-500 transition-colors text-xs">
                                        + Add
                                    </button>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($weekDays) + 1 }}" class="px-4 py-8 text-center text-zinc-500">
                            No active workers found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Today's Attendance --}}
<div class="glass-panel">
    <div class="p-4 border-b border-white/10">
        <div class="text-lg font-semibold">Today's Attendance</div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-zinc-800/50">
                <tr class="text-xs uppercase text-zinc-400">
                    <th class="px-4 py-3 text-left font-medium">Worker</th>
                    <th class="px-4 py-3 text-left font-medium">Role</th>
                    <th class="px-4 py-3 text-left font-medium">Scheduled</th>
                    <th class="px-4 py-3 text-left font-medium">Check In</th>
                    <th class="px-4 py-3 text-left font-medium">Check Out</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($todayAttendance as $attendance)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3 font-medium">{{ $attendance->worker->user->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-400">{{ $attendance->worker->role?->label() ?? 'Staff' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($attendance->start_at && $attendance->end_at)
                                {{ \Carbon\Carbon::parse($attendance->start_at)->format('H:i') }} - {{ \Carbon\Carbon::parse($attendance->end_at)->format('H:i') }}
                            @else
                                <span class="text-zinc-500">Not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($attendance->check_in_at)
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/20 text-emerald-400">
                                    {{ \Carbon\Carbon::parse($attendance->check_in_at)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($attendance->check_out_at)
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-blue-500/20 text-blue-400">
                                    {{ \Carbon\Carbon::parse($attendance->check_out_at)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'SCHEDULED' => 'bg-zinc-500/20 text-zinc-400',
                                    'IN_PROGRESS' => 'bg-blue-500/20 text-blue-400',
                                    'LATE' => 'bg-amber-500/20 text-amber-400',
                                    'COMPLETED' => 'bg-emerald-500/20 text-emerald-400',
                                    'NO_SHOW' => 'bg-rose-500/20 text-rose-400',
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$attendance->status] ?? 'bg-zinc-500/20 text-zinc-400' }}">
                                {{ ucfirst(str_replace('_', ' ', strtolower($attendance->status))) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(!$attendance->check_in_at)
                                <form method="POST" action="{{ route('branch.workforce.checkin') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                                    <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-xs font-medium transition-colors">
                                        Check In
                                    </button>
                                </form>
                            @elseif(!$attendance->check_out_at)
                                <button type="button" onclick="openCheckoutModal({{ $attendance->id }})" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-xs font-medium transition-colors">
                                    Check Out
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500">No scheduled shifts for today</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Schedule Shift Modal --}}
<div id="scheduleModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('scheduleModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="glass-panel w-full max-w-md">
            <form method="POST" action="{{ route('branch.workforce.schedule.store') }}">
                @csrf
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Schedule Shift</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Worker <span class="text-rose-400">*</span></label>
                            <select name="worker_id" id="schedule_worker_id" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                                <option value="">Select Worker</option>
                                @foreach($workers as $worker)
                                    <option value="{{ $worker->id }}">{{ $worker->user->name }} - {{ $worker->role?->label() ?? 'Staff' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Shift Date <span class="text-rose-400">*</span></label>
                            <input type="date" name="shift_date" id="schedule_shift_date" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Start Time <span class="text-rose-400">*</span></label>
                                <input type="time" name="start_time" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">End Time <span class="text-rose-400">*</span></label>
                                <input type="time" name="end_time" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" class="chip bg-zinc-700">Cancel</button>
                    <button type="submit" class="chip bg-emerald-600 hover:bg-emerald-700">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Checkout Modal --}}
<div id="checkoutModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('checkoutModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="glass-panel w-full max-w-md">
            <form method="POST" action="{{ route('branch.workforce.checkout') }}" id="checkoutForm">
                @csrf
                <input type="hidden" name="attendance_id" id="checkout_attendance_id">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Check Out</h3>
                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 text-sm" placeholder="Any notes about the shift..."></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('checkoutModal').classList.add('hidden')" class="chip bg-zinc-700">Cancel</button>
                    <button type="submit" class="chip bg-blue-600 hover:bg-blue-700">Confirm Check Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openScheduleModal(workerId, date) {
    document.getElementById('schedule_worker_id').value = workerId || '';
    document.getElementById('schedule_shift_date').value = date || '';
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function openCheckoutModal(attendanceId) {
    document.getElementById('checkout_attendance_id').value = attendanceId;
    document.getElementById('checkoutModal').classList.remove('hidden');
}

function showShiftDetails(shift) {
    alert('Shift: ' + shift.time_range + '\nStatus: ' + shift.status_label + 
          (shift.check_in_at ? '\nCheck In: ' + shift.check_in_at : '') +
          (shift.check_out_at ? '\nCheck Out: ' + shift.check_out_at : ''));
}
</script>
@endsection
