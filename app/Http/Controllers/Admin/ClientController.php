<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Backend\Branch;
use App\Models\User;
use App\Services\Finance\CustomerStatementService;
use App\Support\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $branchFilter = $request->get('branch_id');
        $statusFilter = $request->get('status');
        $typeFilter = $request->get('customer_type');
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $customers = Customer::query()
            ->with(['primaryBranch:id,name', 'accountManager:id,name'])
            ->when($branchFilter, fn($q) => $q->where('primary_branch_id', $branchFilter))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($typeFilter, fn($q) => $q->where('customer_type', $typeFilter))
            ->when($search, function($q) use ($search) {
                $q->where(function($query) use ($search) {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                });
            })
            ->withCount(['shipments', 'invoices', 'crmActivities'])
            ->latest()
            ->paginate($perPage);

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'active')->count(),
            'vip' => Customer::where('customer_type', 'vip')->count(),
            'credit_issues' => Customer::creditIssues()->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('admin.clients._table', compact('customers'))->render(),
                'pagination' => view('admin.clients._pagination', compact('customers', 'perPage'))->render(),
                'total' => $customers->total(),
            ]);
        }

        return view('admin.clients.index', [
            'customers' => $customers,
            'branches' => $branches,
            'branchFilter' => $branchFilter,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'search' => $search,
            'perPage' => $perPage,
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

    /**
     * Show create client form
     */
    public function create(): View
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);
        $accountManagers = User::whereHas('role', function ($q) {
                $roleKeys = ['admin', 'super-admin', 'regional-manager'];
                $q->whereIn('slug', $roleKeys)->orWhereIn('name', $roleKeys);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.clients.create', [
            'branches' => $branches,
            'accountManagers' => $accountManagers,
            'currency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Store new client
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'required|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'primary_branch_id' => 'required|exists:branches,id',
            'account_manager_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,prospect',
            'customer_type' => 'required|in:vip,regular,prospect',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:50',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'tax_id' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['currency'] = SystemSettings::defaultCurrency();
        $data['created_by_user_id'] = auth()->id();
        $data['created_by_branch_id'] = auth()->user()->primary_branch_id;

        $customer = Customer::create($data);

        return redirect()
            ->route('admin.clients.show', $customer)
            ->with('success', 'Client created successfully with code: ' . $customer->customer_code);
    }

    /**
     * Export clients to CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Customer::query()
            ->with(['primaryBranch:id,name'])
            ->when($request->branch_id, fn($q, $v) => $q->where('primary_branch_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->customer_type, fn($q, $v) => $q->where('customer_type', $v))
            ->orderBy('company_name');

        $filename = 'clients_export_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Customer Code',
                'Company Name',
                'Contact Person',
                'Email',
                'Phone',
                'Branch',
                'Status',
                'Type',
                'Credit Limit',
                'Current Balance',
                'Total Shipments',
                'Total Spent',
                'Customer Since',
                'Last Shipment Date',
            ]);

            $query->chunk(500, function ($clients) use ($handle) {
                foreach ($clients as $client) {
                    fputcsv($handle, [
                        $client->customer_code,
                        $client->company_name,
                        $client->contact_person,
                        $client->email,
                        $client->phone,
                        $client->primaryBranch?->name ?? 'Unassigned',
                        ucfirst($client->status),
                        ucfirst($client->customer_type),
                        $client->credit_limit,
                        $client->current_balance,
                        $client->total_shipments,
                        $client->total_spent,
                        $client->customer_since?->format('Y-m-d'),
                        $client->last_shipment_date?->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Bulk actions on clients
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => 'required|in:activate,suspend,deactivate,assign_branch,assign_manager',
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:customers,id',
            'branch_id' => 'required_if:action,assign_branch|nullable|exists:branches,id',
            'manager_id' => 'required_if:action,assign_manager|nullable|exists:users,id',
        ]);

        $count = count($data['client_ids']);

        switch ($data['action']) {
            case 'activate':
                Customer::whereIn('id', $data['client_ids'])->update(['status' => 'active']);
                $message = "{$count} clients activated.";
                break;
            case 'suspend':
                Customer::whereIn('id', $data['client_ids'])->update(['status' => 'suspended']);
                $message = "{$count} clients suspended.";
                break;
            case 'deactivate':
                Customer::whereIn('id', $data['client_ids'])->update(['status' => 'inactive']);
                $message = "{$count} clients deactivated.";
                break;
            case 'assign_branch':
                Customer::whereIn('id', $data['client_ids'])->update(['primary_branch_id' => $data['branch_id']]);
                $message = "{$count} clients assigned to new branch.";
                break;
            case 'assign_manager':
                Customer::whereIn('id', $data['client_ids'])->update(['account_manager_id' => $data['manager_id']]);
                $message = "{$count} clients assigned to new account manager.";
                break;
            default:
                $message = 'Unknown action.';
        }

        return back()->with('success', $message);
    }

    /**
     * API: Search clients (for POS and other modules)
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->with('primaryBranch:id,name')
            ->limit(15)
            ->get(['id', 'customer_code', 'company_name', 'contact_person', 'email', 'phone', 'customer_type', 'discount_rate', 'credit_limit', 'current_balance', 'primary_branch_id']);

        return response()->json($customers->map(fn($c) => [
            'id' => $c->id,
            'code' => $c->customer_code,
            'name' => $c->company_name ?: $c->contact_person,
            'company' => $c->company_name,
            'contact' => $c->contact_person,
            'email' => $c->email,
            'phone' => $c->phone,
            'type' => $c->customer_type,
            'discount' => $c->discount_rate,
            'credit_limit' => $c->credit_limit,
            'balance' => $c->current_balance,
            'branch' => $c->primaryBranch?->name,
        ]));
    }

    /**
     * Show client contracts
     */
    public function contracts(Customer $client): View
    {
        $contracts = CustomerContract::where('customer_id', $client->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.clients.contracts', [
            'client' => $client,
            'contracts' => $contracts,
            'currency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Update client statistics
     */
    public function refreshStats(Customer $client): RedirectResponse
    {
        $client->updateStatistics();

        return back()->with('success', 'Client statistics refreshed.');
    }

    /**
     * Quick create shipment from client
     */
    public function quickShipment(Customer $client): RedirectResponse
    {
        return redirect()
            ->route('admin.pos.index', ['customer_id' => $client->id])
            ->with('info', 'Creating shipment for ' . $client->display_name);
    }

    /**
     * Store CRM activity for client
     */
    public function storeActivity(Request $request, Customer $client): RedirectResponse
    {
        $data = $request->validate([
            'activity_type' => 'required|in:call,email,meeting,note,complaint,follow_up,other',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'outcome' => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
        ]);

        $client->crmActivities()->create([
            'activity_type' => $data['activity_type'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'occurred_at' => now(),
            'user_id' => auth()->id(),
            'branch_id' => $client->primary_branch_id,
        ]);

        $client->update(['last_contact_date' => now()]);

        return back()->with('success', 'Activity logged successfully.');
    }

    /**
     * Store CRM reminder for client
     */
    public function storeReminder(Request $request, Customer $client): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'reminder_at' => 'required|date|after:now',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $client->crmReminders()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'reminder_at' => $data['reminder_at'],
            'priority' => $data['priority'],
            'status' => 'pending',
            'user_id' => auth()->id(),
            'created_by' => auth()->id(),
            'branch_id' => $client->primary_branch_id,
        ]);

        return back()->with('success', 'Reminder created successfully.');
    }

    /**
     * Complete CRM reminder
     */
    public function completeReminder(Request $request, Customer $client, int $reminderId): RedirectResponse
    {
        $reminder = $client->crmReminders()->findOrFail($reminderId);
        
        $reminder->markCompleted($request->input('notes'));

        return back()->with('success', 'Reminder marked as completed.');
    }

    /**
     * Adjust client credit balance
     */
    public function adjustCredit(Request $request, Customer $client): RedirectResponse
    {
        $data = $request->validate([
            'adjustment_type' => 'required|in:payment,credit,debit,correction',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        $amount = $data['amount'];
        $currentBalance = $client->current_balance ?? 0;

        switch ($data['adjustment_type']) {
            case 'payment':
            case 'credit':
                $newBalance = $currentBalance - $amount;
                break;
            case 'debit':
                $newBalance = $currentBalance + $amount;
                break;
            case 'correction':
                $newBalance = $amount;
                break;
            default:
                return back()->with('error', 'Invalid adjustment type.');
        }

        $client->update(['current_balance' => max(0, $newBalance)]);

        $client->crmActivities()->create([
            'activity_type' => 'note',
            'subject' => 'Credit Adjustment: ' . ucfirst($data['adjustment_type']),
            'description' => "Amount: {$amount}\nReason: {$data['reason']}\nNew Balance: {$newBalance}",
            'outcome' => 'completed',
            'occurred_at' => now(),
            'user_id' => auth()->id(),
            'branch_id' => $client->primary_branch_id,
        ]);

        return back()->with('success', 'Credit balance adjusted successfully.');
    }
}
