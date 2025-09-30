<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashOffice;
use Illuminate\Http\Request;

class CashOfficeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CashOffice::class);
        $items = CashOffice::query()->where('branch_id', $request->user()->hub_id)->orderByDesc('business_date')->paginate(15);

        return view('backend.admin.cash_office.index', compact('items'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CashOffice::class);
        $data = $request->validate([
            'business_date' => 'required|date',
            'cod_collected' => 'required|numeric',
            'cash_on_hand' => 'required|numeric',
            'banked_amount' => 'required|numeric',
        ]);
        $data['branch_id'] = $request->user()->hub_id;
        $data['submitted_by_id'] = $request->user()->id;
        $data['variance'] = ($data['cod_collected'] + $data['cash_on_hand']) - $data['banked_amount'];
        $data['submitted_at'] = now();
        CashOffice::create($data);

        return back()->with('status', 'Cash office day submitted');
    }
}
