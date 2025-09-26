<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Customer;
use App\Services\RatingService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Quotation::class);
        $query = Quotation::query()->latest('id');
        $user = $request->user();
        if (!$user->hasRole(['hq_admin','admin','super-admin']) && !is_null($user->hub_id)) {
            $query->where('origin_branch_id', $user->hub_id);
        }
        $quotes = $query->paginate(15);
        return view('backend.admin.quotations.index', compact('quotes'));
    }

    public function create()
    {
        $this->authorize('create', Quotation::class);
        $customers = Customer::query()->select('id','name')->orderBy('name')->limit(100)->get();
        return view('backend.admin.quotations.create', compact('customers'));
    }

    public function store(Request $request, RatingService $rating)
    {
        $this->authorize('create', Quotation::class);

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'destination_country' => 'required|string|size:2',
            'service_type' => 'required|string',
            'pieces' => 'required|integer|min:1',
            'weight_kg' => 'required|numeric|min:0.001',
            'volume_cm3' => 'nullable|integer|min:1',
            'dim_factor' => 'nullable|integer|min:1000',
            'base_charge' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'valid_until' => 'nullable|date',
        ]);

        $user = $request->user();
        $data['origin_branch_id'] = $user->hub_id;
        $data['created_by_id'] = $user->id;
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['dim_factor'] = $data['dim_factor'] ?? 5000;

        $volume = $data['volume_cm3'] ?? null;
        $dimWeight = $volume ? $rating->dimWeightKg($volume, (int)$data['dim_factor']) : 0;
        $billable = max((float)$data['weight_kg'], (float)$dimWeight);

        $pricing = $rating->priceWithSurcharges((float)$data['base_charge'], $billable, now());
        $data['surcharges_json'] = $pricing['applied'] ?? [];
        $data['total_amount'] = $pricing['total'];
        $data['status'] = 'draft';

        $quote = Quotation::create($data);
        return redirect()->route('admin.quotations.show', $quote)->with('status','Quotation created');
    }

    public function show(Quotation $quotation)
    {
        $this->authorize('view', $quotation);
        return view('backend.admin.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $this->authorize('update', $quotation);
        return view('backend.admin.quotations.edit', compact('quotation'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->authorize('update', $quotation);
        $quotation->update($request->only(['status','valid_until']));
        return back()->with('status','Quotation updated');
    }
}
