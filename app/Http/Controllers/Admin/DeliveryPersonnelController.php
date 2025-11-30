<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryPersonnelController extends Controller
{
    public function index(): View
    {
        $personnel = DeliveryMan::with('hub')->latest()->paginate(20);
        
        $stats = [
            'total' => DeliveryMan::count(),
            'active' => DeliveryMan::where('status', 1)->count(),
        ];

        return view('admin.delivery-personnel.index', compact('personnel', 'stats'));
    }

    public function show(DeliveryMan $personnel): View
    {
        // Load only hub relationship
        $personnel->load('hub');
        
        return view('admin.delivery-personnel.show', compact('personnel'));
    }
}
