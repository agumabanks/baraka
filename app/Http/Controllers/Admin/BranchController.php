<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::with('parent')->latest()->paginate(20);
        
        $stats = [
            'total' => Branch::count(),
            'active' => Branch::where('status', 1)->count(),
            'hubs' => Branch::where('is_hub', true)->orWhere('type', 'hub')->count(),
        ];

        return view('admin.branches.index', compact('branches', 'stats'));
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
}
