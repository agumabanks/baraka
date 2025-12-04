<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Backend\BranchWorker;
use App\Models\CustomerContract;
use App\Services\Finance\CustomerStatementService;
use App\Support\BranchCache;
use App\Support\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientsController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $search = $request->get('q') ?? $request->get('search');
        $statusFilter = $request->get('status');
        $typeFilter = $request->get('customer_type');
        
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $customers = Customer::query()
            ->where('primary_branch_id', $branch->id)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('contact_person', 'like', "%{$search}%")
                       ->orWhere('company_name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('customer_code', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($typeFilter, fn ($q) => $q->where('customer_type', $typeFilter))
            ->with(['primaryBranch:id,name', 'accountManager:id,name'])
            ->withCount(['shipments', 'invoices', 'crmActivities'])
            ->latest()
            ->paginate($perPage);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('branch.clients._table', compact('customers'))->render(),
                'pagination' => view('branch.clients._pagination', compact('customers', 'perPage'))->render(),
                'total' => $customers->total(),
            ]);
        }

        $accountManagers = BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->with('user:id,name')
            ->active()
            ->get();

        $stats = [
            'total' => Customer::where('primary_branch_id', $branch->id)->count(),
            'active' => Customer::where('primary_branch_id', $branch->id)->where('status', 'active')->count(),
            'vip' => Customer::where('primary_branch_id', $branch->id)->where('customer_type', 'vip')->count(),
            'credit_issues' => Customer::where('primary_branch_id', $branch->id)->creditIssues()->count(),
        ];

        return view('branch.clients.index', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'customers' => $customers,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'search' => $search,
            'accountManagers' => $accountManagers,
            'perPage' => $perPage,
        ]);
    }
    
    public function show(Request $request, $customerId): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);
        
        $client = Customer::where('primary_branch_id', $branch->id)
            ->with([
                'primaryBranch',
                'accountManager',
                'salesRep',
                'shipments' => fn($q) => $q->latest()->take(10),
                'invoices' => fn($q) => $q->latest()->take(10),
                'crmActivities' => fn($q) => $q->latest()->take(10),
                'crmReminders' => fn($q) => $q->pending()->orderBy('reminder_at'),
            ])
            ->findOrFail($customerId);
        
        return view('branch.clients.show', [
            'branch' => $branch,
            'client' => $client,
            'analytics' => $client->getAnalyticsSummary(30),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $accountManagers = BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->with('user:id,name')
            ->active()
            ->get();

        return view('branch.clients.create', [
            'branch' => $branch,
            'accountManagers' => $accountManagers,
            'currency' => SystemSettings::defaultCurrency(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

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

        $data['primary_branch_id'] = $branch->id;
        $data['currency'] = SystemSettings::defaultCurrency();
        $data['created_by_user_id'] = auth()->id();
        $data['created_by_branch_id'] = $branch->id;

        $customer = Customer::create($data);

        BranchCache::flushForBranch($branch->id);

        return redirect()
            ->route('branch.clients.show', $customer)
            ->with('success', 'Client created successfully with code: ' . $customer->customer_code);
    }

    public function edit(Request $request, $customerId): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

        $accountManagers = BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->with('user:id,name')
            ->active()
            ->get();

        return view('branch.clients.edit', [
            'branch' => $branch,
            'client' => $client,
            'accountManagers' => $accountManagers,
        ]);
    }

    public function update(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $customer = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,suspended,blacklisted',
            'customer_type' => 'required|in:vip,regular,prospect',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $customer->update($data);

        BranchCache::flushForBranch($branch->id);

        return redirect()
            ->route('branch.clients.show', $customer)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

        if ($client->shipments()->exists()) {
            return back()->with('error', 'Cannot delete client with existing shipments.');
        }

        $client->delete();

        BranchCache::flushForBranch($branch->id);

        return redirect()
            ->route('branch.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $query = Customer::query()
            ->where('primary_branch_id', $branch->id)
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->customer_type, fn($q, $v) => $q->where('customer_type', $v))
            ->orderBy('company_name');

        $filename = 'clients_export_' . $branch->code . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Customer Code',
                'Company Name',
                'Contact Person',
                'Email',
                'Phone',
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

    public function bulkAction(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'action' => 'required|in:activate,suspend,deactivate',
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:customers,id',
        ]);

        $count = Customer::where('primary_branch_id', $branch->id)
            ->whereIn('id', $data['client_ids'])
            ->count();

        switch ($data['action']) {
            case 'activate':
                Customer::where('primary_branch_id', $branch->id)
                    ->whereIn('id', $data['client_ids'])
                    ->update(['status' => 'active']);
                $message = "{$count} clients activated.";
                break;
            case 'suspend':
                Customer::where('primary_branch_id', $branch->id)
                    ->whereIn('id', $data['client_ids'])
                    ->update(['status' => 'suspended']);
                $message = "{$count} clients suspended.";
                break;
            case 'deactivate':
                Customer::where('primary_branch_id', $branch->id)
                    ->whereIn('id', $data['client_ids'])
                    ->update(['status' => 'inactive']);
                $message = "{$count} clients deactivated.";
                break;
            default:
                $message = 'Unknown action.';
        }

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', $message);
    }

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where('primary_branch_id', $branch->id)
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get(['id', 'customer_code', 'company_name', 'contact_person', 'email', 'phone', 'customer_type', 'discount_rate', 'credit_limit', 'current_balance']);

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
        ]));
    }

    public function contracts(Request $request, $customerId): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

        $contracts = CustomerContract::where('customer_id', $client->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        return view('branch.clients.contracts', [
            'branch' => $branch,
            'client' => $client,
            'contracts' => $contracts,
            'currency' => SystemSettings::defaultCurrency(),
        ]);
    }

    public function statement(Request $request, $customerId, CustomerStatementService $statementService): Response
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

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

    public function statementPreview(Request $request, $customerId, CustomerStatementService $statementService): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

        $startDate = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->subMonths(3)->startOfMonth();
        $endDate = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now();

        $data = $statementService->generateStatement($client, $startDate, $endDate);
        $aging = $statementService->getAgingSummary($client);

        return view('branch.clients.statement', array_merge($data, [
            'branch' => $branch,
            'aging' => $aging,
            'start' => $startDate,
            'end' => $endDate,
        ]));
    }

    public function refreshStats(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);
        $client->updateStatistics();

        return back()->with('success', 'Client statistics refreshed.');
    }

    public function quickShipment(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

        return redirect()
            ->route('branch.pos.index', ['customer_id' => $client->id])
            ->with('info', 'Creating shipment for ' . $client->display_name);
    }

    /**
     * Store CRM activity for client
     */
    public function storeActivity(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

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
            'branch_id' => $branch->id,
        ]);

        $client->update(['last_contact_date' => now()]);
        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Activity logged successfully.');
    }

    /**
     * Store CRM reminder for client
     */
    public function storeReminder(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

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
            'branch_id' => $branch->id,
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Reminder created successfully.');
    }

    /**
     * Complete CRM reminder
     */
    public function completeReminder(Request $request, $customerId, int $reminderId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);
        $reminder = $client->crmReminders()->findOrFail($reminderId);
        
        $reminder->markCompleted($request->input('notes'));
        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Reminder marked as completed.');
    }

    /**
     * Adjust client credit balance
     */
    public function adjustCredit(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $client = Customer::where('primary_branch_id', $branch->id)->findOrFail($customerId);

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
            'branch_id' => $branch->id,
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Credit balance adjusted successfully.');
    }
}
