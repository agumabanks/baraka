<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Backend\Branch;
use App\Services\Finance\CustomerStatementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $branchFilter = $request->get('branch_id');
        $statusFilter = $request->get('status');
        $typeFilter = $request->get('customer_type');
        $search = $request->get('search');

        $customers = Customer::query()
            ->with(['primaryBranch:id,name', 'accountManager:id,name'])
            ->when($branchFilter, fn($q) => $q->where('primary_branch_id', $branchFilter))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($typeFilter, fn($q) => $q->where('customer_type', $typeFilter))
            ->when($search, function($q) use ($search) {
                $q->where(function($query) use ($search) {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                });
            })
            ->withCount(['shipments', 'invoices', 'crmActivities'])
            ->latest()
            ->paginate(25);

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'active')->count(),
            'vip' => Customer::where('customer_type', 'vip')->count(),
            'credit_issues' => Customer::creditIssues()->count(),
        ];

        return view('admin.clients.index', [
            'customers' => $customers,
            'branches' => $branches,
            'branchFilter' => $branchFilter,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'search' => $search,
            'stats' => $stats,
        ]);
    }

    public function show(Customer $client): View
    {
        $client->load([
            'primaryBranch',
            'accountManager',
            'salesRep',
            'shipments' => fn($q) => $q->latest()->take(10),
            'invoices' => fn($q) => $q->latest()->take(10),
            'crmActivities' => fn($q) => $q->latest()->take(10),
            'crmReminders' => fn($q) => $q->pending()->orderBy('reminder_at'),
        ]);

        return view('admin.clients.show', [
            'client' => $client,
            'analytics' => $client->getAnalyticsSummary(30),
        ]);
    }

    public function edit(Customer $client): View
    {
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.clients.edit', [
            'client' => $client,
            'branches' => $branches,
        ]);
    }

    public function update(Request $request, Customer $client): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'primary_branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive,suspended,blacklisted',
            'customer_type' => 'required|in:vip,regular,prospect',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $client->update($data);

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Customer $client): RedirectResponse
    {
        if ($client->shipments()->exists()) {
            return back()->with('error', 'Cannot delete client with existing shipments.');
        }

        $client->delete();

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function reassign(Request $request, Customer $client): RedirectResponse
    {
        $data = $request->validate([
            'primary_branch_id' => 'required|exists:branches,id',
        ]);

        $client->update([
            'primary_branch_id' => $data['primary_branch_id'],
        ]);

        return back()->with('success', 'Client reassigned to new branch.');
    }

    /**
     * Generate customer statement PDF
     */
    public function statement(Request $request, Customer $client, CustomerStatementService $statementService): Response
    {
        $startDate = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->subMonths(3)->startOfMonth();
        $endDate = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now();

        $pdf = $statementService->generatePdf($client, $startDate, $endDate);

        $filename = "statement_{$client->customer_code}_{$startDate->format('Ymd')}_{$endDate->format('Ymd')}.pdf";

        return $pdf->download($filename);
    }

    /**
     * View customer statement (HTML preview)
     */
    public function statementPreview(Request $request, Customer $client, CustomerStatementService $statementService): View
    {
        $startDate = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->subMonths(3)->startOfMonth();
        $endDate = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now();

        $data = $statementService->generateStatement($client, $startDate, $endDate);
        $aging = $statementService->getAgingSummary($client);

        return view('admin.clients.statement', array_merge($data, [
            'aging' => $aging,
            'start' => $startDate,
            'end' => $endDate,
        ]));
    }
}
