<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(): View
    {
        // Placeholder data for reports
        $reports = [
            [
                'name' => 'User Activity',
                'description' => 'Daily user login and action summary',
                'category' => 'Security',
                'last_generated' => now()->subHours(2),
                'status' => 'Ready',
            ],
            [
                'name' => 'Branch Performance',
                'description' => 'Shipment throughput and SLA adherence',
                'category' => 'Operations',
                'last_generated' => now()->subDay(),
                'status' => 'Ready',
            ],
            [
                'name' => 'Financial Overview',
                'description' => 'Revenue, expenses, and outstanding invoices',
                'category' => 'Finance',
                'last_generated' => now()->subHours(4),
                'status' => 'Processing',
            ],
            [
                'name' => 'System Health',
                'description' => 'Server load, error rates, and uptime',
                'category' => 'System',
                'last_generated' => now()->subMinutes(15),
                'status' => 'Ready',
            ],
        ];

        return view('admin.reports.index', compact('reports'));
    }
}
