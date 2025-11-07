<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\DeliveryChargeResource;
use App\Models\Backend\Merchant;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use App\Traits\ApiReturnFormatTrait;

class SettingsController extends Controller
{
    use ApiReturnFormatTrait;

    protected $deliveryCharges;

    public function __construct(MerchantDeliveryChargeInterface $deliveryCharges)
    {
        $this->deliveryCharges = $deliveryCharges;
    }

    public function codCharges()
    {
        try {
            $codCharge = Merchant::where('user_id', auth()->user()->id)->first();
            $codCharges = [];
            $i = 0;
            if (! blank($codCharge) && is_array($codCharge->cod_charges)) {
                foreach ($codCharge->cod_charges as $key => $charge) {
                    $codCharges[$i]['name'] = __('merchant.'.$key);
                    $codCharges[$i]['charge'] = $charge;
                    $i++;
                }
            }

            return $this->responseWithSuccess(__('delivery_charge.cod_charges'), ['codCharges' => $codCharges], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('delivery_charge.error_msg'), [], 500);

        }
    }

    public function deliveryCharges()
    {
        try {
            $merchant = Merchant::where('user_id', auth()->user()->id)->first();

            if (blank($merchant)) {
                return $this->responseWithSuccess(__('delivery_charge.title'), ['deliveryCharges' => []], 200);
            }

            $deliveryCharges = DeliveryChargeResource::collection($this->deliveryCharges->getAll($merchant->id));

            return $this->responseWithSuccess(__('delivery_charge.title'), ['deliveryCharges' => $deliveryCharges], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('delivery_charge.error_msg'), [], 500);
        }

    }
}
