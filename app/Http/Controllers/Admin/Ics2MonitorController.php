<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ics2Filing;

class Ics2MonitorController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Ics2Filing::class);
        $items = Ics2Filing::query()->latest('id')->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'ICS2 Monitor','items'=>$items]);
    }

    public function show(Ics2Filing $ics2)
    {
        $this->authorize('view', $ics2);
        return view('backend.admin.placeholder', ['title' => 'Filing','record'=>$ics2]);
    }
}

