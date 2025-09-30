<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodReceipt;

class CodReceiptController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', CodReceipt::class);
        $items = CodReceipt::latest()->paginate(15);

        return view('backend.admin.cod_receipts.index', compact('items'));
    }

    public function show(CodReceipt $cod_receipt)
    {
        $this->authorize('view', $cod_receipt);

        return view('backend.admin.cod_receipts.show', ['cod' => $cod_receipt]);
    }
}
