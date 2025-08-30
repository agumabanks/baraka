<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Unicodeveloper\Paystack\Paystack;
use Illuminate\Support\Facades\DB;

class AdminPaystackController extends Controller
{
    public function __construct(protected AccountInterface $accountRepo)
    {
    }

    public function index(Request $request){
        $merchant_id      = $request->get('merchant_id');
        $accounts         = $this->accountRepo->getAll();
        return view('backend.payout.paystack',compact('merchant_id', 'accounts'));
    }

    public function initializePayment(Request $request)
    {
        $merchant = Merchant::findOrFail($request->merchant_id);
        $account = Account::findOrFail($request->account_id);

        if ($merchant->current_balance < $request->amount || $account->balance < $request->amount) {
            Toastr::error('Insufficient balance in merchant or account.', __('message.error'));
            return Redirect::back();
        }

        $url = "https://api.paystack.co/transaction/initialize";
        $callback_url = globalSettings('paystack_callback_url');

        $fields = array(
            "amount" => $request->amount * 100,
            "email" => $merchant->user->email,
            "orderID" => uniqid(),
            "callback_url" => $callback_url,
            "metadata" => [
                "account_id" => $request->account_id,
                "merchant_id" => $request->merchant_id,
                "amount" => $request->amount,
            ],
        );

        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer ". globalSettings('paystack_secret_key'),
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $response = curl_exec($ch);
        $result = json_decode($response, true);

        if (!$result['status']) {
            Toastr::error('Paystack error: ' . $result['message'], __('message.error'));
            return redirect()->back();
        }

        return redirect($result['data']['authorization_url']);
    }

    public function verifyPayment(Request $request){
        $curl = curl_init();
        $reference = $request->get('reference');

        if(!$reference) {
            Toastr::error('Reference not found.', __('message.error'));
            return redirect()->back();
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$reference}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ". globalSettings('paystack_secret_key'),
            "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            Toastr::error('cURL Error: ' . $err, __('message.error'));
            return redirect()->back();
        }

        if($this->storePaymentData($response)) {
            return redirect()->route('payout.index');
        } else {
            return redirect()->back();
        }

    }

    protected function storePaymentData($response){
        DB::beginTransaction();

        try {

            $result = json_decode($response, true);
            $data = $result['data'];
            $metadata = $data['metadata'];

            $merchant = Merchant::find($metadata['merchant_id']);
            $account = Account::find($metadata['account_id']);
            $amount = $metadata['amount'];

            if ($data['status'] !== 'success') {
                DB::rollBack();
                Toastr::error('Payment failed. Please try again.', __('message.error'));
                return redirect()->back();
            }

            $payment = new MerchantOnlinePaymentReceived();
            $payment->payment_type = PaymentType::PAYSTACK;
            $payment->amount = $amount;
            $payment->note = 'Payment';
            $payment->transaction_id = $data['reference'] ?? null;
            $payment->account_id = $metadata['account_id'];
            $payment->merchant_id = $metadata['merchant_id'];
            $payment->save();

            $merchant->decrement('current_balance', $amount);
            $account->decrement('balance', $amount);

            DB::commit();
            Toastr::success('Payment successfully completed.', __('message.success'));
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Payment failed: ' . $e->getMessage(), __('message.error'));
            return false;
        }
    }
}
