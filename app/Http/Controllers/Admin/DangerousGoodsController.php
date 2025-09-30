<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DangerousGood;

class DangerousGoodsController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', DangerousGood::class);
        $items = DangerousGood::query()->latest('id')->paginate(15);

        return view('backend.admin.dg.index', compact('items'));
    }

    public function show(DangerousGood $dg)
    {
        $this->authorize('view', $dg);

        return view('backend.admin.dg.show', ['dg' => $dg]);
    }
}
