<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Models\Backend\Merchantpanel\InvoiceParcel;
use App\Repositories\Invoice\InvoiceInterface;

class InvoiceController extends Controller
{
    protected $repo;

    public function __construct(InvoiceInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $invoices = $this->repo->get();

        return view('backend.merchant_panel.invoice.index', compact('invoices'));
    }

    public function InvoiceDetails($invoiceId)
    {
        $invoice = $this->repo->InvoiceDetails($invoiceId);
        $invoiceParcels = InvoiceParcel::where('invoice_id', $invoice->id)->paginate(10);

        return view('backend.merchant_panel.invoice.invoice_details', compact('invoice', 'invoiceParcels'));
    }
}
