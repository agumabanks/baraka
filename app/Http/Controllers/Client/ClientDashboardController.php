<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientDashboardController extends Controller
{
    /**
     * Display the customer dashboard
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        
        // Get customer's recent shipments
        $shipments = $customer->shipments()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get statistics
        $stats = [
            'total_shipments' => $customer->total_shipments ?? 0,
            'total_spent' => $customer->total_spent ?? 0,
            'pending_shipments' => $customer->shipments()
                ->whereIn('status', ['pending', 'in_transit', 'out_for_delivery'])
                ->count(),
            'delivered_shipments' => $customer->shipments()
                ->where('status', 'delivered')
                ->count(),
        ];

        return view('client.dashboard', compact('customer', 'shipments', 'stats'));
    }
}
