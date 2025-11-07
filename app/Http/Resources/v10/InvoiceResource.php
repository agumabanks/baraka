<?php

namespace App\Http\Resources\v10;

use App\Enums\InvoiceStatus;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status = $this->status;
        $statusLabel = match ((int) $status) {
            InvoiceStatus::PAID => __('invoice.'.InvoiceStatus::PAID),
            InvoiceStatus::PROCESSING => __('invoice.'.InvoiceStatus::PROCESSING),
            InvoiceStatus::UNPAID => __('invoice.'.InvoiceStatus::UNPAID),
            default => null,
        };

        $currentPayable = (float) ($this->current_payable ?? 0);
        $cashCollection = (float) ($this->cash_collection ?? 0);
        $totalCharge = (float) ($this->total_charge ?? 0);

        $invoiceDate = $this->invoice_date
            ? Carbon::parse($this->invoice_date)->format('d M Y')
            : optional($this->created_at)->format('d M Y');

        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'status' => $status,
            'status_label' => $statusLabel,
            'amount' => $currentPayable,
            'cash_collection' => $cashCollection,
            'total_charges' => $totalCharge,
            'invoice_date' => $invoiceDate,
        ];
    }
}
