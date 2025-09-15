<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycRecord;
use App\Models\DpsScreening;

class KycController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', KycRecord::class);
        $items = KycRecord::query()->latest('id')->paginate(15);
        return view('backend.admin.kyc.index', compact('items'));
    }

    public function show(KycRecord $kyc)
    {
        $this->authorize('view', $kyc);
        $screenings = DpsScreening::query()->where('screened_type','customer')
            ->where('screened_id', $kyc->customer_id)->latest('id')->limit(10)->get();
        return view('backend.admin.kyc.show', ['kyc' => $kyc, 'screenings' => $screenings]);
    }

    public function update(KycRecord $kyc)
    {
        $this->authorize('update', $kyc);
        $kyc->update(['status' => request('status','approved'), 'reviewed_by_id'=>auth()->id(), 'reviewed_at'=>now()]);
        return back()->with('status','KYC updated');
    }
}
