<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $account = $this->merchantAccount;

        return [
            'id' => $this->id,
            'transaction_id' => (string) $this->transaction_id,
            'description' => $this->description,
            'amount' => (string) $this->amount,
            'currency' => settings()->currency,
            'payment_method' => $account?->payment_method,
            'paymentMethodName' => ($account && $account->payment_method) ? __('merchant.'.$account->payment_method) : null,
            'bank_name' => $account?->bank_name,
            'holder_name' => $account?->holder_name,
            'account_no' => $account?->account_no,
            'branch_name' => $account?->branch_name,
            'routing_no' => $account?->routing_no,
            'mobile_company' => $account?->mobile_company,
            'mobile_no' => $account?->mobile_no,
            'account_type' => $account?->account_type,
            'status' => (int) $this->status,
            'statusName' => $this->status !== null ? trans('approvalstatus.'.$this->status) : null,
            'request_date' => $this->created_at->format('d M Y, h:i A'),
            'created_at' => $this->created_at->format('d M Y, h:i A'),
            'updated_at' => $this->updated_at->format('d M Y, h:i A'),
        ];
    }
}
