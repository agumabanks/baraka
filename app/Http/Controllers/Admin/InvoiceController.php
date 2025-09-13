<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Invoice::class);
        $items = Invoice::latest()->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'Invoices', 'items' => $items]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        return view('backend.admin.placeholder', ['title' => 'Invoice #'.$invoice->id, 'record' => $invoice]);
    }
}

