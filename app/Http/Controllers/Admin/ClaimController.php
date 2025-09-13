<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;

class ClaimController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Claim::class);
        $items = Claim::query()->latest('id')->paginate(15);
        return view('backend.admin.placeholder', ['title'=>'Claims','items'=>$items]);
    }
}

