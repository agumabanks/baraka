<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HsCode;

class HsCodeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', HsCode::class);
        $items = HsCode::latest()->paginate(15);
        return view('backend.admin.hscodes.index', compact('items'));
    }
}
