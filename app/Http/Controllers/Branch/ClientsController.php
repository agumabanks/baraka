<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Backend\BranchWorker;
use App\Support\BranchCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientsController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $status = $request->get('status');

        // Use proper scoping: admin sees all, branch users see only their branch's clients
        $clients = \App\Models\Customer::query()
            ->visibleToUser($user)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['primaryBranch:id,name', 'accountManager:id,name'])
            ->withCount(['shipments', 'invoices'])
            ->latest()
            ->paginate(15);

        $accountManagers = BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->with('user:id,name')
            ->active()
            ->get();

        return view('branch.clients', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'clients' => $clients,
            'statusFilter' => $status,
            'accountManagers' => $accountManagers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'business_name' => 'required|string|max:191',
            'status' => 'nullable|string|max:60',
            'kyc_flag' => 'nullable|string|max:120',
            'credit_limit' => 'nullable|numeric',
            'contacts' => 'nullable|array',
            'addresses' => 'nullable|array',
            'pipeline_stage' => 'nullable|string|in:onboarding,active,at-risk,retention,lost',
            'account_manager_id' => 'nullable|integer|exists:branch_workers,id',
        ]);

        \App\Models\Customer::create([
            'primary_branch_id' => $branch->id,
            'created_by_branch_id' => $branch->id,
            'created_by_user_id' => $user->id,
            'company_name' => $data['business_name'],
            'status' => $data['status'] ?? 'active',
            'credit_limit' => $data['credit_limit'] ?? 0,
            'customer_type' => 'regular',
            'account_manager_id' => $data['account_manager_id'] ?? null,
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Client onboarded.');
    }

    public function update(Request $request, $customerId): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $customer = \App\Models\Customer::findOrFail($customerId);

        // Check access: admin can edit all, branch users only their branch's customers
        if (!$user->hasRole(['super-admin', 'admin'])) {
            abort_unless($customer->primary_branch_id === $branch->id, 403);
        }

        $data = $request->validate([
            'business_name' => 'nullable|string|max:191',
            'status' => 'nullable|string|max:60',
            'kyc_flag' => 'nullable|string|max:120',
            'credit_limit' => 'nullable|numeric',
            'contacts' => 'nullable|array',
            'addresses' => 'nullable|array',
            'pipeline_stage' => 'nullable|string|in:onboarding,active,at-risk,retention,lost',
            'account_manager_id' => 'nullable|integer|exists:branch_workers,id',
        ]);

        $customer->update(array_filter([
            'company_name' => $data['business_name'] ?? null,
            'status' => $data['status'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? null,
            'account_manager_id' => $data['account_manager_id'] ?? null,
        ]));

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Client updated.');
    }
}
