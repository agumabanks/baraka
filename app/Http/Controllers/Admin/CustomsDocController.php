<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomsDoc;

class CustomsDocController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', CustomsDoc::class);
        $items = CustomsDoc::latest()->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'Customs Docs', 'items' => $items]);
    }

    public function show(CustomsDoc $customs_doc)
    {
        $this->authorize('view', $customs_doc);
        return view('backend.admin.placeholder', ['title' => 'Customs Doc #'.$customs_doc->id, 'record' => $customs_doc]);
    }

    public function store()
    {
        $this->authorize('create', CustomsDoc::class);
        return back()->with('status','Customs doc upload not yet implemented');
    }
}

