@extends('branch.layout')

@section('title', 'Workforce Scheduling')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-1">Workforce Scheduling</h2>
            <p class="text-gray-400">Manage shifts, attendance, and worker schedules</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-emerald-600" data-bs-toggle="modal" data-bs-target="#scheduleShiftModal">
                <i class="fas fa-calendar-plus me-2"></i>Schedule Shift
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-gray-400 mb-1">On Duty Today</p>
                            <h3 class="text-white mb-0">{{ $onDutyCount }}</h3>
                        </div>
                        <div class="bg-green-500/10 p-3 rounded">
                            <i class="fas fa-user-check fa-2x text-green-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-gray-400 mb-1">Scheduled Today</p>
                            <h3 class="text-white mb-0">{{ $scheduledToday }}</h3>
                        </div>
                        <div class="bg-blue-500/10 p-3 rounded">
                            <i class="fas fa-calendar fa-2x text-blue-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-gray-400 mb-1">Late Check-ins</p>
                            <h3 class="text-white mb-0">{{ $lateCount }}</h3>
                        </div>
                        <div class="bg-yellow-500/10 p-3 rounded">
                            <i class="fas fa-clock fa-2x text-yellow-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-gray-400 mb-1">No-Shows</p>
                            <h3 class="text-white mb-0">{{ $noShowCount }}</h3>
                        </div>
                        <div class="bg-red-500/10 p-3 rounded">
                            <i class="fas fa-user-times fa-2x text-red-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Schedule View -->
    <div class="card bg-gray-800 border-gray-700 mb-4">
        <div class="card-header bg-gray-900 border-gray-700">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Weekly Schedule</h5>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('branch.workforce.schedule', ['week_offset' => ($weekOffset ?? 0) - 1]) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <button class="btn btn-outline-secondary" disabled>
                        Week of {{ now()->addWeeks($weekOffset ?? 0)->startOfWeek()->format('M d') }}
                    </button>
                    <a href="{{ route('branch.workforce.schedule', ['week_offset' => ($weekOffset ?? 0) + 1]) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Worker</th>
                            @foreach($weekDays as $day)
                            <th class="text-center">
                                <div>{{ $day['name'] }}</div>
                                <small class="text-gray-400">{{ $day['date'] }}</small>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workers as $worker)
                        <tr>
                            <td class="fw-bold">
                                <div>{{ $worker->user->name }}</div>
                                <small class="text-gray-400">{{ $worker->role?->label() ?? 'Staff' }}</small>
                            </td>
                            @foreach($weekDays as $day)
                            <td class="text-center p-1" style="min-width: 120px;">
                                @php
                                    $shifts = $scheduleMatrix[$worker->id][$day['date']] ?? [];
                                @endphp
                                @forelse($shifts as $shift)
                                <div class="badge {{ $shift['status_class'] }} w-100 mb-1 text-start" style="cursor: pointer;" 
                                     onclick="showShiftDetails({{ json_encode($shift) }})">
                                    <div><i class="fas fa-clock me-1"></i>{{ $shift['time_range'] }}</div>
                                    <div class="small">{{ $shift['status_label'] }}</div>
                                </div>
                                @empty
                                <button class="btn btn-sm btn-outline-secondary w-100" 
                                        onclick="scheduleShift({{ $worker->id }}, '{{ $day['date'] }}')">
                                    <i class="fas fa-plus"></i>
                                </button>
                                @endforelse
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($weekDays) + 1 }}" class="text-center text-gray-400 py-4">
                                No workers found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="card bg-gray-800 border-gray-700">
        <div class="card-header bg-gray-900 border-gray-700">
            <h5 class="mb-0 text-white">Today's Attendance</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Role</th>
                            <th>Scheduled Time</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todayAttendance as $attendance)
                        <tr>
                            <td>{{ $attendance->worker->user->name }}</td>
                            <td>{{ ucfirst($attendance->worker->role) }}</td>
                            <td>
                                @if($attendance->start_at && $attendance->end_at)
                                    {{ \Carbon\Carbon::parse($attendance->start_at)->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($attendance->end_at)->format('H:i') }}
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->check_in_at)
                                    <span class="badge bg-success">{{ \Carbon\Carbon::parse($attendance->check_in_at)->format('H:i') }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->check_out_at)
                                    <span class="badge bg-info">{{ \Carbon\Carbon::parse($attendance->check_out_at)->format('H:i') }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->status === 'SCHEDULED')
                                    <span class="badge bg-secondary">Scheduled</span>
                                @elseif($attendance->status === 'IN_PROGRESS')
                                    <span class="badge bg-primary">On Duty</span>
                                @elseif($attendance->status === 'LATE')
                                    <span class="badge bg-warning">Late</span>
                                @elseif($attendance->status === 'COMPLETED')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($attendance->status === 'NO_SHOW')
                                    <span class="badge bg-danger">No Show</span>
                                @endif
                            </td>
                            <td>
                                @if(!$attendance->check_in_at)
                                <form method="POST" action="{{ route('branch.workforce.checkin') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-sign-in-alt"></i> Check In
                                    </button>
                                </form>
                                @elseif(!$attendance->check_out_at)
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="showCheckoutModal({{ $attendance->id }})">
                                    <i class="fas fa-sign-out-alt"></i> Check Out
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-4">No scheduled shifts for today</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Shift Modal -->
<div class="modal fade" id="scheduleShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-gray-800">
            <form method="POST" action="{{ route('branch.workforce.schedule.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-white">Schedule Shift</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-white">Worker *</label>
                        <select name="worker_id" id="schedule_worker_id" class="form-select bg-gray-900 border-gray-700 text-white" required>
                            <option value="">Select Worker</option>
                            @foreach($workers as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->user->name }} - {{ $worker->role?->label() ?? 'Staff' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Shift Date *</label>
                        <input type="date" name="shift_date" id="schedule_shift_date" class="form-control bg-gray-900 border-gray-700 text-white" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white">Start Time *</label>
                            <input type="time" name="start_time" class="form-control bg-gray-900 border-gray-700 text-white" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white">End Time *</label>
                            <input type="time" name="end_time" class="form-control bg-gray-900 border-gray-700 text-white" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Notes</label>
                        <textarea name="notes" class="form-control bg-gray-900 border-gray-700 text-white" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-gray-800">
            <form method="POST" action="{{ route('branch.workforce.checkout') }}" id="checkoutForm">
                @csrf
                <input type="hidden" name="attendance_id" id="checkout_attendance_id">
                <div class="modal-header">
                    <h5 class="modal-title text-white">Check Out</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-white">Notes</label>
                        <textarea name="notes" class="form-control bg-gray-900 border-gray-700 text-white" rows="3" placeholder="Any notes about the shift..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Check Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function scheduleShift(workerId, date) {
    document.getElementById('schedule_worker_id').value = workerId;
    document.getElementById('schedule_shift_date').value = date;
    new bootstrap.Modal(document.getElementById('scheduleShiftModal')).show();
}

function showCheckoutModal(attendanceId) {
    document.getElementById('checkout_attendance_id').value = attendanceId;
    new bootstrap.Modal(document.getElementById('checkoutModal')).show();
}

function showShiftDetails(shift) {
    alert('Shift Details:\n' + JSON.stringify(shift, null, 2));
}
</script>
@endsection
