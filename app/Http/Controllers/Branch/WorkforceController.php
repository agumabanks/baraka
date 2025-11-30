<?php

namespace App\Http\Controllers\Branch;

use App\Enums\BranchWorkerRole;
use App\Enums\EmploymentStatus;
use App\Enums\Status;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\BranchAttendance;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use App\Support\BranchCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkforceController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $workers = BranchWorker::query()
            ->with('user:id,name,email,mobile,phone_e164')
            ->where('branch_id', $branch->id)
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('branch.workforce', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'workers' => $workers,
            'roleOptions' => BranchWorkerRole::options(),
            'statusOptions' => EmploymentStatus::options(),
        ]);
    }

    public function scheduleView(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $weekOffset = (int) $request->get('week_offset', 0);
        $startOfWeek = now()->addWeeks($weekOffset)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        // Get all workers
        $workers = BranchWorker::query()
            ->with('user:id,name,email')
            ->where('branch_id', $branch->id)
            ->where('status', Status::ACTIVE)
            ->get();

        // Build week days
        $weekDays = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $weekDays->push([
                'name' => $date->format('l'),
                'date' => $date->format('Y-m-d'),
                'is_today' => $date->isToday(),
            ]);
        }

        // Get all shifts for the week
        $shifts = BranchAttendance::query()
            ->with('worker.user')
            ->where('branch_id', $branch->id)
            ->whereBetween('shift_date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get();

        // Build schedule matrix
        $scheduleMatrix = [];
        foreach ($workers as $worker) {
            $scheduleMatrix[$worker->id] = [];
            foreach ($weekDays as $day) {
                $scheduleMatrix[$worker->id][$day['date']] = $shifts
                    ->where('worker_id', $worker->id)
                    ->where('shift_date', $day['date'])
                    ->map(function ($shift) {
                        $statusClass = match($shift->status) {
                            'SCHEDULED' => 'bg-secondary',
                            'IN_PROGRESS' => 'bg-primary',
                            'LATE' => 'bg-warning',
                            'COMPLETED' => 'bg-success',
                            'NO_SHOW' => 'bg-danger',
                            default => 'bg-secondary',
                        };

                        $timeRange = 'Not set';
                        if ($shift->start_at && $shift->end_at) {
                            $timeRange = \Carbon\Carbon::parse($shift->start_at)->format('H:i') . '-' . 
                                        \Carbon\Carbon::parse($shift->end_at)->format('H:i');
                        }

                        return [
                            'id' => $shift->id,
                            'time_range' => $timeRange,
                            'status' => $shift->status,
                            'status_label' => ucfirst(str_replace('_', ' ', strtolower($shift->status))),
                            'status_class' => $statusClass,
                            'check_in_at' => $shift->check_in_at,
                            'check_out_at' => $shift->check_out_at,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        // Today's attendance
        $todayAttendance = BranchAttendance::query()
            ->with('worker.user')
            ->where('branch_id', $branch->id)
            ->where('shift_date', now()->format('Y-m-d'))
            ->get();

        // Quick stats
        $onDutyCount = $todayAttendance->whereIn('status', ['IN_PROGRESS', 'LATE'])->count();
        $scheduledToday = $todayAttendance->count();
        $lateCount = $todayAttendance->where('status', 'LATE')->count();
        $noShowCount = $todayAttendance->where('status', 'NO_SHOW')->count();

        return view('branch.workforce_schedule', compact(
            'branch',
            'workers',
            'weekDays',
            'scheduleMatrix',
            'todayAttendance',
            'onDutyCount',
            'scheduledToday',
            'lateCount',
            'noShowCount',
            'weekOffset'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'name' => 'required_without:user_id|string|max:191',
            'email' => 'required_without:user_id|email|unique:users,email',
            'mobile' => 'nullable|string|max:30',
            'user_id' => 'nullable|exists:users,id',
            'role' => 'required|string',
            'employment_status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $workerUser = $data['user_id']
            ? User::findOrFail($data['user_id'])
            : User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make(Str::random(14)),
                'primary_branch_id' => $branch->id,
            ]);

        $role = BranchWorkerRole::fromString($data['role']);
        $employmentStatus = isset($data['employment_status'])
            ? EmploymentStatus::fromString($data['employment_status'])
            : EmploymentStatus::ACTIVE;

        BranchWorker::updateOrCreate(
            ['branch_id' => $branch->id, 'user_id' => $workerUser->id],
            [
                'role' => $role->value,
                'employment_status' => $employmentStatus->value,
                'status' => Status::ACTIVE,
                'notes' => $data['notes'] ?? null,
                'assigned_at' => now(),
            ]
        );

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Worker onboarded for '.$branch->code);
    }

    public function schedule(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'worker_id' => 'required|integer|exists:branch_workers,id',
            'shift_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'notes' => 'nullable|string|max:255',
        ]);

        $worker = BranchWorker::findOrFail($data['worker_id']);
        abort_unless($worker->branch_id === $branch->id, 403);

        // Combine date with time
        $startAt = null;
        $endAt = null;
        if (isset($data['start_time'])) {
            $startAt = $data['shift_date'] . ' ' . $data['start_time'] . ':00';
        }
        if (isset($data['end_time'])) {
            $endAt = $data['shift_date'] . ' ' . $data['end_time'] . ':00';
        }

        BranchAttendance::create([
            'branch_id' => $branch->id,
            'worker_id' => $worker->id,
            'shift_date' => $data['shift_date'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => 'SCHEDULED',
            'notes' => $data['notes'] ?? null,
        ]);

        activity()->performedOn($worker)->causedBy($user)->withProperties($data)->log('Shift scheduled');

        return back()->with('success', 'Shift scheduled');
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'attendance_id' => 'required|integer|exists:branch_attendances,id',
        ]);

        $attendance = BranchAttendance::findOrFail($data['attendance_id']);
        abort_unless($attendance->branch_id === $branch->id, 403);

        $now = now();
        $status = 'IN_PROGRESS';
        if ($attendance->start_at && $now->gt($attendance->start_at)) {
            $status = 'LATE';
        }

        $attendance->update([
            'check_in_at' => $now,
            'status' => $status,
        ]);

        activity()->performedOn($attendance)->causedBy($user)->log('Shift check-in');

        return back()->with('success', 'Check-in recorded');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'attendance_id' => 'required|integer|exists:branch_attendances,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $attendance = BranchAttendance::findOrFail($data['attendance_id']);
        abort_unless($attendance->branch_id === $branch->id, 403);

        $attendance->update([
            'check_out_at' => now(),
            'status' => 'COMPLETED',
            'notes' => $data['notes'] ?? $attendance->notes,
        ]);

        activity()->performedOn($attendance)->causedBy($user)->log('Shift check-out');

        return back()->with('success', 'Check-out recorded');
    }

    public function update(Request $request, BranchWorker $worker): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($worker->branch_id === $branch->id, 403);

        $data = $request->validate([
            'role' => 'nullable|string',
            'employment_status' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if (isset($data['role'])) {
            $worker->role = BranchWorkerRole::fromString($data['role'])->value;
        }

        if (isset($data['employment_status'])) {
            $worker->employment_status = EmploymentStatus::fromString($data['employment_status'])->value;
        }

        if (isset($data['status'])) {
            $worker->status = (int) $data['status'];
            if ($worker->status === Status::INACTIVE) {
                $worker->unassigned_at = now();
            }
        }

        $worker->save();

        activity()->performedOn($worker)->causedBy($user)->withProperties($data)->log('Worker updated');

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Worker updated.');
    }

    public function archive(Request $request, BranchWorker $worker): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($worker->branch_id === $branch->id, 403);

        $worker->status = Status::INACTIVE;
        $worker->unassigned_at = now();
        $worker->save();

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Worker archived.');
    }
}
