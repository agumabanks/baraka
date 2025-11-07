<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class StatementsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $currency = settings()->currency;

        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'merchant_name' => optional($this->merchant)->business_name,
            'parcel_id' => $this->parcel_id,
            'parcel_tracking_id' => optional($this->parcel)->tracking_id,
            'note' => $this->note,
            'date' => $this->date ? (string) dateFormat($this->date) : null,
            'amount' => (float) $this->amount,
            'amount_formatted' => number_format((float) $this->amount, 2),
            'currency' => $currency,
            'type' => (int) $this->type,
            'type_name' => trans('AccountHeads.'.$this->type),
            'created_at' => optional($this->created_at)->format('d M Y, h:i A'),
            'updated_at' => optional($this->updated_at)->format('d M Y, h:i A'),
        ];
    }
}
