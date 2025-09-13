<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurchargeRule;
use Illuminate\Http\Request;

class SurchargeRuleController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SurchargeRule::class);
        $items = SurchargeRule::query()->latest('id')->paginate(20);
        return view('backend.admin.placeholder', ['title'=>'Surcharge Rules','items'=>$items]);
    }

    public function create()
    {
        $this->authorize('create', SurchargeRule::class);
        return view('backend.admin.placeholder', ['title'=>'Create Surcharge Rule']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SurchargeRule::class);
        $data = $request->validate([
            'code' => 'required|string|unique:surcharge_rules,code',
            'name' => 'required|string',
            'trigger' => 'required|in:fuel,security,remote_area,oversize,weekend,dg,re_attempt,custom',
            'rate_type' => 'required|in:flat,percent',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|size:3',
            'active_from' => 'required|date',
            'active_to' => 'nullable|date',
            'active' => 'sometimes|boolean',
        ]);
        SurchargeRule::create($data);
        return redirect()->route('admin.surcharges.index');
    }
}

