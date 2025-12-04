<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $query = Branch::with('parent');
        
        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Type filter
        if ($type = $request->get('type')) {
            if ($type === 'hub') {
                $query->where(function($q) {
                    $q->where('is_hub', true)->orWhere('type', 'hub');
                });
            } else {
                $query->where('type', $type);
            }
        }
        
        // Status filter
        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('status', $request->get('status'));
        }
        
        $branches = $query->latest()->paginate($perPage)->withQueryString();
        
        $stats = [
            'total' => Branch::count(),
            'active' => Branch::where('status', 1)->count(),
            'hubs' => Branch::where('is_hub', true)->orWhere('type', 'hub')->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('admin.branches._table', compact('branches'))->render(),
                'pagination' => view('admin.branches._pagination', compact('branches', 'perPage'))->render(),
                'total' => $branches->total(),
            ]);
        }

        return view('admin.branches.index', compact('branches', 'stats', 'perPage'));
    }

    public function show(Branch $branch): View
    {
        $branch->load(['parent', 'children']);
        
        return view('admin.branches.show', compact('branch'));
    }

    public function create(): View
    {
        $branches = Branch::where('is_hub', true)->orWhere('type', 'hub')->get();
        return view('admin.branches.create', compact('branches'));
    }

    public function edit(Branch $branch): View
    {
        $branches = Branch::where(function ($q) {
            $q->where('is_hub', true)->orWhere('type', 'hub');
        })->where('id', '!=', $branch->id)->get();
        return view('admin.branches.edit', compact('branch', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'type' => 'nullable|string|in:branch,hub,regional',
            'parent_branch_id' => 'nullable|exists:branches,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_hub' => 'nullable|boolean',
        ]);

        $validated['is_hub'] = $request->boolean('is_hub');
        
        Branch::create($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'type' => 'nullable|string|in:branch,hub,regional',
            'parent_branch_id' => 'nullable|exists:branches,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_hub' => 'nullable|boolean',
        ]);

        $validated['is_hub'] = $request->boolean('is_hub');
        
        $branch->update($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
