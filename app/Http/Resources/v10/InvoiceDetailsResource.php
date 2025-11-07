<?php

namespace App\Http\Resources\v10;

use App\Enums\BooleanStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $parcelIds = collect($this->parcels_id ?? []);

        $parcels = $parcelIds->isNotEmpty()
            ? Parcel::whereIn('id', $parcelIds)->get()
            : collect();

        $deliveredParcels = $parcels->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED]);
        $returnParcels = $parcels->whereIn('status', [ParcelStatus::RETURN_RECEIVED_BY_MERCHANT, ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, ParcelStatus::RETURN_TO_COURIER]);
        $partialDeliveryReturns = $returnParcels->where('partial_delivered', BooleanStatus::YES);

        $totalDeliveredAmount = $deliveredParcels->sum('cash_collection') + $partialDeliveryReturns->sum('cash_collection');
        $totalDeliveryCharge = $deliveredParcels->sum('delivery_charge');
        $totalCodCharge = $parcels->sum('cod_amount');
        $totalReturnFee = $returnParcels->sum('return_charges');
        $totalReturnDeliveryCharge = $returnParcels->sum('delivery_charge');

        $payableAmount = ($totalDeliveredAmount - $totalDeliveryCharge - $totalCodCharge - $totalReturnFee - $totalReturnDeliveryCharge);

        $status = $this->status;
        $statusLabel = match ((int) $status) {
            InvoiceStatus::PAID => __('invoice.'.InvoiceStatus::PAID),
            InvoiceStatus::PROCESSING => __('invoice.'.InvoiceStatus::PROCESSING),
            InvoiceStatus::UNPAID => __('invoice.'.InvoiceStatus::UNPAID),
            default => null,
        };

        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'status' => $status,
            'status_label' => $statusLabel,
            'total_delivered_amount' => $totalDeliveredAmount,
            'delivery_charge' => $totalDeliveryCharge,
            'cod_amount' => $totalCodCharge,
            'total_return_fee' => $totalReturnFee,
            'payable_amount' => $payableAmount,
            'invoice_date' => $this->invoice_date ? Carbon::parse($this->invoice_date)->format('d M Y') : optional($this->created_at)->format('d M Y'),
            'merchant_name' => optional($this->merchant)->business_name,
            'merchant_phone' => optional(optional($this->merchant)->user)->mobile,
            'merchant_address' => optional($this->merchant)->address,
            'total_parcels' => $parcels->count(),
            'parcels' => $this->InvoiceParcelList,

        ];
    }
}
