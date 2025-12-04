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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkforceController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $search = $request->get('search') ?? $request->get('q');
        $roleFilter = $request->get('role');
        $statusFilter = $request->get('status');
        
        $perPage = (int) $request->get('per_page', 12);
        $perPage = in_array($perPage, [10, 12, 25, 50, 100]) ? $perPage : 12;

        $workers = BranchWorker::query()
            ->with(['user:id,name,email,mobile,phone_e164', 'assignedShipments'])
            ->where('branch_id', $branch->id)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            })
            ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
                if ($statusFilter === 'active') {
                    $q->active();
                } elseif ($statusFilter === 'inactive') {
                    $q->where('status', Status::INACTIVE);
                }
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('branch.workforce._table', compact('workers'))->render(),
                'pagination' => view('branch.workforce._pagination', compact('workers', 'perPage'))->render(),
                'total' => $workers->total(),
            ]);
        }

        // Statistics
        $stats = [
            'total' => BranchWorker::where('branch_id', $branch->id)->count(),
            'active' => BranchWorker::where('branch_id', $branch->id)->active()->count(),
            'on_duty' => BranchAttendance::where('branch_id', $branch->id)
                ->where('shift_date', now()->format('Y-m-d'))
                ->whereIn('status', ['IN_PROGRESS', 'LATE'])
                ->count(),
            'drivers' => BranchWorker::where('branch_id', $branch->id)
                ->whereIn('role', [BranchWorkerRole::DRIVER->value, BranchWorkerRole::COURIER->value])
                ->active()
                ->count(),
        ];

        return view('branch.workforce.index', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'workers' => $workers,
            'roleOptions' => BranchWorkerRole::options(),
            'statusOptions' => EmploymentStatus::options(),
            'stats' => $stats,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'perPage' => $perPage,
        ]);
    }

    public function show(Request $request, BranchWorker $worker): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($worker->branch_id === $branch->id, 403);

        $worker->load(['user', 'branch', 'assignedShipments' => fn($q) => $q->latest()->take(20)]);

        // Get performance metrics
        $performance = $worker->getPerformanceMetrics();
        $workload = $worker->getCurrentWorkload();
        $weeklySchedule = $worker->getWeeklySchedule();

        // Recent attendance (last 30 days)
        $recentAttendance = BranchAttendance::where('worker_id', $worker->id)
            ->where('shift_date', '>=', now()->subDays(30))
            ->orderByDesc('shift_date')
            ->get();

        // Attendance stats
        $attendanceStats = [
            'total_shifts' => $recentAttendance->count(),
            'completed' => $recentAttendance->where('status', 'COMPLETED')->count(),
            'late' => $recentAttendance->where('status', 'LATE')->count(),
            'no_show' => $recentAttendance->where('status', 'NO_SHOW')->count(),
        ];

        return view('branch.workforce.show', [
            'branch' => $branch,
            'worker' => $worker,
            'performance' => $performance,
            'workload' => $workload,
            'weeklySchedule' => $weeklySchedule,
            'recentAttendance' => $recentAttendance,
            'attendanceStats' => $attendanceStats,
            'roleOptions' => BranchWorkerRole::options(),
            'statusOptions' => EmploymentStatus::options(),
        ]);
    }

    public function edit(Request $request, BranchWorker $worker): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($worker->branch_id === $branch->id, 403);

        $worker->load('user');

        return view('branch.workforce.edit', [
            'branch' => $branch,
            'worker' => $worker,
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

        $workers = BranchWorker::query()
            ->with('user:id,name,email')
            ->where('branch_id', $branch->id)
            ->where('status', Status::ACTIVE)
            ->get();

        $weekDays = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $weekDays->push([
                'name' => $date->format('l'),
                'short' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'display' => $date->format('M d'),
                'is_today' => $date->isToday(),
            ]);
        }

        $shifts = BranchAttendance::query()
            ->with('worker.user')
            ->where('branch_id', $branch->id)
            ->whereBetween('shift_date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get();

        $scheduleMatrix = [];
        foreach ($workers as $worker) {
            $scheduleMatrix[$worker->id] = [];
            foreach ($weekDays as $day) {
                $scheduleMatrix[$worker->id][$day['date']] = $shifts
                    ->where('worker_id', $worker->id)
                    ->where('shift_date', $day['date'])
                    ->map(function ($shift) {
                        $statusClass = match($shift->status) {
                            'SCHEDULED' => 'bg-zinc-600',
                            'IN_PROGRESS' => 'bg-blue-600',
                            'LATE' => 'bg-amber-600',
                            'COMPLETED' => 'bg-emerald-600',
                            'NO_SHOW' => 'bg-rose-600',
                            default => 'bg-zinc-600',
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
                            'check_in_at' => $shift->check_in_at?->format('H:i'),
                            'check_out_at' => $shift->check_out_at?->format('H:i'),
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        $todayAttendance = BranchAttendance::query()
            ->with('worker.user')
            ->where('branch_id', $branch->id)
            ->where('shift_date', now()->format('Y-m-d'))
            ->get();

        $onDutyCount = $todayAttendance->whereIn('status', ['IN_PROGRESS', 'LATE'])->count();
        $scheduledToday = $todayAttendance->count();
        $lateCount = $todayAttendance->where('status', 'LATE')->count();
        $noShowCount = $todayAttendance->where('status', 'NO_SHOW')->count();

        return view('branch.workforce.schedule', compact(
            'branch',
            'workers',
            'weekDays',
            'scheduleMatrix',
            'todayAttendance',
            'onDutyCount',
            'scheduledToday',
            'lateCount',
            'noShowCount',
            'weekOffset',
            'startOfWeek'
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
            'hourly_rate' => 'nullable|numeric|min:0',
            'id_number' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:30',
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
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'id_number' => $data['id_number'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_at' => now(),
            ]
        );

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Worker onboarded successfully for '.$branch->name);
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

        return back()->with('success', 'Shift scheduled successfully');
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

        return back()->with('success', 'Check-in recorded at ' . $now->format('H:i'));
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
            'hourly_rate' => 'nullable|numeric|min:0',
            'id_number' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
            'name' => 'nullable|string|max:191',
            'email' => 'nullable|email',
            'mobile' => 'nullable|string|max:30',
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

        if (isset($data['hourly_rate'])) {
            $worker->hourly_rate = $data['hourly_rate'];
        }

        if (isset($data['id_number'])) {
            $worker->id_number = $data['id_number'];
        }

        if (isset($data['contact_phone'])) {
            $worker->contact_phone = $data['contact_phone'];
        }

        if (isset($data['notes'])) {
            $worker->notes = $data['notes'];
        }

        $worker->save();

        // Update user details if provided
        if ($worker->user && (isset($data['name']) || isset($data['email']) || isset($data['mobile']))) {
            $worker->user->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'mobile' => $data['mobile'] ?? null,
            ]));
        }

        activity()->performedOn($worker)->causedBy($user)->withProperties($data)->log('Worker updated');

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Worker updated successfully.');
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

        activity()->performedOn($worker)->causedBy($user)->log('Worker archived');

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Worker archived.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'action' => 'required|in:activate,deactivate,change_role',
            'worker_ids' => 'required|array',
            'worker_ids.*' => 'exists:branch_workers,id',
            'role' => 'required_if:action,change_role|nullable|string',
        ]);

        $count = BranchWorker::where('branch_id', $branch->id)
            ->whereIn('id', $data['worker_ids'])
            ->count();

        switch ($data['action']) {
            case 'activate':
                BranchWorker::where('branch_id', $branch->id)
                    ->whereIn('id', $data['worker_ids'])
                    ->update(['status' => Status::ACTIVE, 'unassigned_at' => null]);
                $message = "{$count} workers activated.";
                break;
            case 'deactivate':
                BranchWorker::where('branch_id', $branch->id)
                    ->whereIn('id', $data['worker_ids'])
                    ->update(['status' => Status::INACTIVE, 'unassigned_at' => now()]);
                $message = "{$count} workers deactivated.";
                break;
            case 'change_role':
                $role = BranchWorkerRole::fromString($data['role'])->value;
                BranchWorker::where('branch_id', $branch->id)
                    ->whereIn('id', $data['worker_ids'])
                    ->update(['role' => $role]);
                $message = "{$count} workers role updated.";
                break;
            default:
                $message = 'Unknown action.';
        }

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $query = BranchWorker::query()
            ->with('user')
            ->where('branch_id', $branch->id)
            ->when($request->role, fn($q, $v) => $q->where('role', $v))
            ->when($request->status === 'active', fn($q) => $q->active())
            ->orderBy('created_at', 'desc');

        $filename = 'workforce_export_' . $branch->code . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Email',
                'Mobile',
                'Role',
                'Employment Status',
                'Status',
                'Hourly Rate',
                'ID Number',
                'Assigned At',
                'Notes',
            ]);

            $query->chunk(500, function ($workers) use ($handle) {
                foreach ($workers as $worker) {
                    fputcsv($handle, [
                        $worker->user?->name ?? 'Unknown',
                        $worker->user?->email ?? '',
                        $worker->user?->mobile ?? '',
                        $worker->role?->label() ?? $worker->role,
                        $worker->employment_status?->label() ?? $worker->employment_status,
                        $worker->status === Status::ACTIVE ? 'Active' : 'Inactive',
                        $worker->hourly_rate,
                        $worker->id_number,
                        $worker->assigned_at?->format('Y-m-d'),
                        $worker->notes,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $workers = BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->active()
            ->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->with('user:id,name,email')
            ->limit(15)
            ->get();

        return response()->json($workers->map(fn($w) => [
            'id' => $w->id,
            'user_id' => $w->user_id,
            'name' => $w->user?->name,
            'email' => $w->user?->email,
            'role' => $w->role?->label() ?? $w->role,
        ]));
    }
}
