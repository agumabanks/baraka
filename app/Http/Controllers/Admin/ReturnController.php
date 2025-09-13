<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnOrder;

class ReturnController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ReturnOrder::class);
        $items = ReturnOrder::query()->latest('id')->paginate(15);
        return view('backend.admin.placeholder', ['title'=>'Returns','items'=>$items]);
    }
}

