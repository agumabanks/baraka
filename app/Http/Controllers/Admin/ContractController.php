<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Contract::class);
        $items = Contract::query()->latest('id')->paginate(15);
        return view('backend.admin.contracts.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', Contract::class);
        return view('backend.admin.contracts.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Contract::class);
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'rate_card_id' => 'nullable|exists:rate_cards,id',
            'status' => 'nullable|in:active,suspended,ended'
        ]);
        $data['status'] = $data['status'] ?? 'active';
        $contract = Contract::create($data);
        return redirect()->route('admin.contracts.show', $contract);
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);
        return view('backend.admin.contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $this->authorize('update', $contract);
        return view('backend.admin.contracts.edit', compact('contract'));
    }

    public function update(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);
        $contract->update($request->only(['status','notes','rate_card_id','end_date']));
        return back()->with('status','Contract updated');
    }

    public function destroy(Contract $contract)
    {
        $this->authorize('delete', $contract);
        $contract->delete();
        return redirect()->route('admin.contracts.index');
    }
}
