<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantController extends Controller
{
    public function index(): View
    {
        $merchants = Merchant::withCount('parcels')->latest()->paginate(20);
        
        $stats = [
            'total' => Merchant::count(),
            'active' => Merchant::where('status', 1)->count(),
        ];

        return view('admin.merchants.index', compact('merchants', 'stats'));
    }

    public function show(Merchant $merchant): View
    {
        // Load only relationships that definitely exist
        return view('admin.merchants.show', compact('merchant'));
    }

    public function statements(Merchant $merchant): View
    {
        return view('admin.merchants.statements', compact('merchant'));
    }
}
