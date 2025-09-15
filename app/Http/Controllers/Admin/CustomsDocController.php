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
        return view('backend.admin.customs_docs.index', compact('items'));
    }

    public function show(CustomsDoc $customs_doc)
    {
        $this->authorize('view', $customs_doc);
        return view('backend.admin.customs_docs.show', ['doc' => $customs_doc]);
    }

    public function store()
    {
        $this->authorize('create', CustomsDoc::class);
        return back()->with('status','Customs doc upload not yet implemented');
    }
}
