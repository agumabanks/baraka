<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route as RouteModel;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', RouteModel::class);
        $routes = RouteModel::latest()->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'Routes', 'items' => $routes]);
    }

    public function show(RouteModel $route)
    {
        $this->authorize('view', $route);
        return view('backend.admin.placeholder', ['title' => 'Route #'.$route->id, 'record' => $route]);
    }

    public function create()
    {
        $this->authorize('create', RouteModel::class);
        return view('backend.admin.placeholder', ['title' => 'Create Route']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', RouteModel::class);
        return back()->with('status','Route creation not yet implemented');
    }

    public function update(Request $request, RouteModel $route)
    {
        $this->authorize('update', $route);
        return back()->with('status','Route update not yet implemented');
    }
}

