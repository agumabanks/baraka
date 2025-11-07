<?php

namespace App\Repositories\MerchantPanel\PaymentAccount;

use App\Enums\Merchant_panel\PaymentMethod;
use App\Models\MerchantPayment;
use Illuminate\Support\Facades\Auth;

class PaymentAccountRepository implements PaymentAccountInterface
{
    public function all()
    {
        $user = auth()->user();
        $merchantId = $user?->merchant->id ?? null;

        $query = MerchantPayment::query()->orderByDesc('id');

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->paginate(10);
    }

    public function get($id) {}

    public function store($request)
    {

        try {
            $Account = new MerchantPayment;
            $Account->merchant_id = Auth::user()->merchant->id;
            $Account->payment_method = $request->payment_method;
            if ($request->payment_method == PaymentMethod::bank) {

                $Account->bank_name = $request->bank_name;
                $Account->holder_name = $request->holder_name;
                $Account->account_no = $request->account_no;
                $Account->branch_name = $request->branch_name;
                $Account->routing_no = $request->routing_no;
                $Account->save();

                return true;

            } elseif ($request->payment_method == PaymentMethod::mobile) {

                $Account->holder_name = $request->mobile_holder_name;
                $Account->mobile_company = $request->mobile_company;
                $Account->mobile_no = $request->mobile_no;
                $Account->account_type = $request->account_type;
                $Account->save();

                return true;
            } elseif ($request->payment_method == PaymentMethod::cash) {
                $Account->save();

                return true;
            }

        } catch (\Throwable $th) {
            return false;
        }

    }

    public function edit($id)
    {
        return MerchantPayment::where('id', $id)->first();
    }

    public function update($request)
    {
        try {

            $Account = MerchantPayment::where('id', $request->id)->first();
            $Account->merchant_id = Auth::user()->merchant->id;
            $Account->payment_method = $request->payment_method;
            if ($request->payment_method == PaymentMethod::bank) {
                $Account->bank_name = $request->bank_name;
                $Account->holder_name = $request->holder_name;
                $Account->account_no = $request->account_no;
                $Account->branch_name = $request->branch_name;
                $Account->routing_no = $request->routing_no;
                // mobile remove old data
                $Account->mobile_company = null;
                $Account->mobile_no = null;
                $Account->account_type = null;
                // end mobile
                $Account->save();

                return true;

            } elseif ($request->payment_method == PaymentMethod::mobile) {
                $Account->holder_name = $request->mobile_holder_name;
                $Account->mobile_company = $request->mobile_company;
                $Account->mobile_no = $request->mobile_no;
                $Account->account_type = $request->account_type;
                // remove bank old data
                $Account->bank_name = null;
                $Account->account_no = null;
                $Account->branch_name = null;
                $Account->routing_no = null;
                // end bank old data
                $Account->save();

                return true;
            } elseif ($request->payment_method == PaymentMethod::cash) {
                $Account->save();

                return true;
            }

        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id)
    {
        return MerchantPayment::destroy($id);
    }
}
