<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\InvoiceDetailsResource;
use App\Http\Resources\v10\InvoiceResource;
use App\Repositories\Invoice\InvoiceInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use ApiReturnFormatTrait;

    protected $repo;

    public $data = [];

    public function __construct(InvoiceInterface $repo)
    {
        $this->repo = $repo;
    }

    // invoice list
    public function invoiceLists()
    {
        try {
            $invoiceList = $this->repo->invoiceLists();

            return $this->responseWithSuccess('Invoices loaded', [
                'invoices' => InvoiceResource::collection($invoiceList),
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to fetch invoices', [
                'error' => $th->getMessage(),
            ]);

            return $this->responseWithError('Unable to load invoices', [
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function invoiceDetails($invoice_id)
    {
        try {
            $invoice = $this->repo->getFind($invoice_id);

            if (! $invoice) {
                return $this->responseWithError('Invoice not found', [], 404);
            }

            return $this->responseWithSuccess('Invoice detail loaded', [
                'invoice' => new InvoiceDetailsResource($invoice),
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to fetch invoice detail', [
                'invoice_id' => $invoice_id,
                'error' => $th->getMessage(),
            ]);

            return $this->responseWithError('Unable to load invoice detail', [
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
