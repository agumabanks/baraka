<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecmr;

class EcmrController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Ecmr::class);
        $items = Ecmr::query()->latest('id')->paginate(15);
        return view('backend.admin.placeholder', ['title'=>'e-CMR','items'=>$items]);
    }

    public function show(Ecmr $ecmr)
    {
        $this->authorize('view', $ecmr);
        return view('backend.admin.placeholder', ['title'=>'e-CMR','record'=>$ecmr]);
    }
}

