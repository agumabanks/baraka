<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FxRate;

class FxRateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', FxRate::class);
        $items = FxRate::query()->orderByDesc('effective_at')->paginate(20);
        return view('backend.admin.placeholder', ['title'=>'FX Rates','items'=>$items]);
    }
}

