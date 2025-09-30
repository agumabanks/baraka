<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Epod;

class EpodController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Epod::class);
        $items = Epod::latest()->paginate(15);

        return view('backend.admin.epod.index', compact('items'));
    }
}
