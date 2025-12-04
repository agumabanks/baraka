<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Models\Backend\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Display all users across the system
     */
    public function index(Request $request)
    {
        $admin = $request->user();
        
        // Ensure user is admin
        if (!$admin->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
            abort(403, 'Unauthorized');
        }

        $search = $request->get('search') ?? $request->get('q');
        $role = $request->get('role');
        $branch = $request->get('branch');
        $status = $request->get('status');
        
        // Per page with validation
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        // Build users query
        $users = User::query()
            ->with(['role', 'branchWorker.branch', 'branchManager.branch'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($q) use ($role) {
                $q->whereHas('role', function ($query) use ($role) {
                    $query->where('name', $role)->orWhere('slug', $role);
                });
            })
            ->when($branch, function ($q) use ($branch) {
                $q->where(function ($query) use ($branch) {
                    $query->where('primary_branch_id', $branch)
                          ->orWhereHas('branchWorker', function ($q2) use ($branch) {
                              $q2->where('branch_id', $branch);
                          })
                          ->orWhereHas('branchManager', function ($q2) use ($branch) {
                              $q2->where('branch_id', $branch);
                          });
                });
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);
        
        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('admin.users._table', compact('users'))->render(),
                'pagination' => view('admin.users._pagination', compact('users', 'perPage'))->render(),
                'total' => $users->total(),
            ]);
        }

        // Get statistics
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 1)->count(),
            'branch_managers' => BranchManager::count(),
            'branch_workers' => BranchWorker::count(),
            'admins' => User::whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin', 'hq_admin'])
                  ->orWhereIn('slug', ['admin', 'super-admin', 'hq_admin']);
            })->count(),
        ];

        // Get all branches for filter
        $branches = Branch::orderBy('name')->get();

        // Get impersonation history for admin
        $recentImpersonations = DB::table('impersonation_logs')
            ->join('users', 'impersonation_logs.impersonated_user_id', '=', 'users.id')
            ->where('admin_id', $admin->id)
            ->select('impersonation_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        return view('admin.users.index', compact(
            'users',
            'stats',
            'branches',
            'search',
            'role',
            'branch',
            'status',
            'perPage',
            'recentImpersonations'
        ));
    }

    /**
     * Create user form
     */
    public function create(): View
    {
        $this->authorizeAdmin();

        $roles = Role::orderBy('name')->get(['id', 'name']);
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.users.create', compact('roles', 'branches'));
    }

    /**
     * Store new user
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:50',
            'role_id' => 'nullable|exists:roles,id',
            'primary_branch_id' => 'nullable|exists:branches,id',
            'password' => 'nullable|string|min:8',
        ]);

        $password = $validated['password'] ?? str()->random(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'role_id' => $validated['role_id'] ?? null,
            'primary_branch_id' => $validated['primary_branch_id'] ?? null,
            'password' => Hash::make($password),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created. Temporary password: '.$password);
    }

    /**
     * Display all branch managers
     */
    public function branchManagers(Request $request): View
    {
        $admin = $request->user();
        
        if (!$admin->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
            abort(403, 'Unauthorized');
        }

        $search = $request->get('search');
        $branchFilter = $request->get('branch');

        $managers = BranchManager::query()
            ->with(['branch', 'user'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($branchFilter, function ($q) use ($branchFilter) {
                $q->where('branch_id', $branchFilter);
            })
            ->latest()
            ->paginate(30);

        $branches = Branch::orderBy('name')->get();

        return view('admin.users.branch-managers', compact('managers', 'branches', 'search', 'branchFilter'));
    }

    /**
     * Display impersonation logs
     */
    public function impersonationLogs(Request $request): View
    {
        $admin = $request->user();
        
        if (!$admin->hasRole(['admin', 'super-admin', 'hq_admin'])) {
            abort(403, 'Unauthorized');
        }

        $logs = DB::table('impersonation_logs')
            ->join('users as admins', 'impersonation_logs.admin_id', '=', 'admins.id')
            ->join('users as targets', 'impersonation_logs.impersonated_user_id', '=', 'targets.id')
            ->select(
                'impersonation_logs.*',
                'admins.name as admin_name',
                'admins.email as admin_email',
                'targets.name as target_name',
                'targets.email as target_email'
            )
            ->orderByDesc('started_at')
            ->paginate(50);

        // Get statistics
        $stats = [
            'total_sessions' => DB::table('impersonation_logs')->count(),
            'active_sessions' => DB::table('impersonation_logs')->where('status', 'started')->count(),
            'today' => DB::table('impersonation_logs')->whereDate('started_at', today())->count(),
            'this_month' => DB::table('impersonation_logs')->whereMonth('started_at', now()->month)->count(),
        ];

        return view('admin.users.impersonation-logs', compact('logs', 'stats'));
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user || ! $user->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
            abort(403, 'Unauthorized');
        }
    }
}
