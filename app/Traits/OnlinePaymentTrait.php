<?php

namespace App\Traits;

use App\Enums\BooleanStatus;
use App\Enums\PayoutSetup;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Http\Controllers\Backend\PaymobController;
use App\Models\Backend\OnlinePayment;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
use App\Repositories\Wallet\WalletRepository;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

trait OnlinePaymentTrait
{
    
    public function onlinePayment($request)
    {
        try {

            $requestData  = new Request();
            $requestData['name']            =  $request->name;
            $requestData['email']           =  $request->email;
            $requestData['phone']           =  $request->phone;
            $requestData['parcel_id']       =  $request->parcel_id;
            $requestData['wallet_id']       =  $request->wallet_id;
            $requestData['source']          =  $request->source;
            $requestData['merchant_id']     =  $request->merchant_id;
            $requestData['amount']          =  $request->amount;
            $requestData['payment_method']  =  $request->payment_method;
            $payment = $this->payment($requestData);
            
            switch ($payment->source) {
                case 'parcel': 
                    $parcel = Parcel::find($payment->parcel_id);
                    $parcel->online_payment_id      = $payment->id; 
                    $parcel->online_payment_method  = $request->payment_method;
                    $parcel->save(); 
                break;
                case 'wallet': 
                    $wallet = Wallet::find($payment->wallet_id);
                    $wallet->online_payment_id      = $payment->id;  
                    $wallet->save(); 
                break;
            }
            
            $paymentData                = new Request();
            $paymentData['payment_id']  = @$payment->id;
            
            switch ($request->payment_method) {
                case PayoutSetup::PAYMOB:
                    return redirect()->route('merchant.panel.paymob.pay', $paymentData->all());
                break;
            }

            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function ReOnlinePayment($request)
    {
        try {
 
            $paymentData                = new Request();

            $payment = OnlinePayment::find($request->payment_id); 
          
            switch ($payment->source) {
                case 'parcel': 
                    $parcel                     =  Parcel::find($payment->parcel_id); 
                    $request['payment_method']  =  $parcel->online_payment_method;
                break;
                case 'wallet': 
                    $wallet                     =  Wallet::find($payment->wallet_id); 
                    if($wallet->payment_method == WalletPaymentMethod::PAYMOB):
                        $request['payment_method']  = PayoutSetup::PAYMOB;
                    endif;
                break;
            }
            
            $paymentData['payment_id']  = @$payment->id;
            
            switch ($request->payment_method) {
                case PayoutSetup::PAYMOB:
                    return redirect()->route('merchant.panel.paymob.pay', $paymentData->all());
                break;
            }
          
            
            return false;
        } catch (\Throwable $th) { 
            return false;
        }
    }
 
 
    public function payment($request)
    { 

        $payerDetails = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone
        ];

        $payment = OnlinePayment::create([
            'parcel_id'      =>  $request->parcel_id,
            'wallet_id'      =>  $request->wallet_id,
            'source'         =>  $request->source,
            'payer_details'  =>  $payerDetails,
            'merchant_id'    =>  $request->merchant_id,
            'transaction_id' =>  $request->transaction_id,
            'amount'         =>  $request->amount, 
            'payment_method' => __('PayoutSetup.' . $request->payment_method)
        ]);
        return $payment;

    }

    public function payment_response($payment_data, $status, $payment_method_id = null)
    {

        $payment = OnlinePayment::find($payment_data->id);
        $parcel = Parcel::find($payment->parcel_id);
        try {
            
            $payment->status                   = $status;
            // $payment->payment_method_id     = $payment_method_id;
            $payment->save();

            switch ($payment->source) {
                case 'parcel':
                    $parcel->online_payment_method  = $payment_method_id;
                    if($status == 'success'):
                        $parcel->is_paid  = BooleanStatus::YES;
                    endif;
                    $parcel->save();
                break; 
                case 'wallet':
                    $wallet = Wallet::find($payment->wallet_id);
                    if($status == 'success'):
                        request()->merge([
                            'from_payment_gateway'  =>true
                        ]);
                        (new WalletRepository)->approved($wallet->id); 
                    endif;
                    $wallet->save();
                break;  
            }

            return redirect()->route('payment.callback', ['status' => $status,'source' => $payment->source,'initiate_returned_paid_by' => @$parcel->initiate_returned_paid_by]);
        } catch (\Throwable $th) {
            return redirect()->route('payment.callback', ['status' => $status,'source' => $payment->source,'initiate_returned_paid_by' => @$parcel->initiate_returned_paid_by]);
        }
    }

}
