<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePayment;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Stripe;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Srmklive\PayPal\Services\ExpressCheckout;
class OnlinePaymentController extends Controller
{
    protected $MOPModel, $MOPRmodel, $accountRepo;
    public function __construct(MerchantOnlinePayment $MOPModel,MerchantOnlinePaymentReceived $MOPRmodel,AccountInterface $accountRepo){
        $this->MOPModel = $MOPModel;
        $this->MOPRmodel = $MOPRmodel;
        $this->accountRepo = $accountRepo;
    }
    //start stripe payment gateway
    public function merchantPaymentReceived(){
        $Payments   = $this->MOPRmodel->where('merchant_id',Auth::user()->merchant->id)->orderByDesc('id')->paginate(10);
        return view('backend.merchant_panel.online_payment_received.index',compact('Payments'));
    }
    //payout list
    public function index(){
        $oPayments   = $this->MOPModel->where(['merchant_id'=>Auth::user()->merchant->id])->orderByDesc('id')->paginate(10);
        return view('backend.merchant_panel.onlinepayment.payment_list',compact('oPayments'));
    }
    public function stripe(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.stripe',compact('accounts'));
    }
    public function stripePost(Request $request){
        \Config([
            'services.stripe.secret'        => globalSettings('stripe_secret_key'),
        ]);

        $stripe = Stripe::charges()->create([
            'source' => $request->get('tokenId'),
            'currency' => 'BDT',
            'amount' => $request->get('amount')
        ]);
        $paymnet = DB::table('merchant_online_payments')
        ->updateOrInsert([
            'payment_type'   => PaymentType::STRIPE,
            'amount'         => $request->get('amount'),
            'note'           => 'Payment',
            'transaction_id' => $request->get('tokenId'),
            'merchant_id'    => Auth::user()->merchant->id,
            'account_id'      => $request->account_id
        ]);

        $merchant                   = Merchant::find(Auth::user()->merchant->id);
        $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
        $merchant->save();
        $account                   = Account::find($request->account_id);
        $account->balance          = ($account->balance + $request->get('amount'));
        $account->save();
        return response()->json(['success'=>true],200);
    }

    //Start Paypal Payment Gateway
    public function paypalIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.paypal',compact('accounts'));
    }
    public function paypalpayment(Request $request)
    {
        try {
            $payment = DB::table('merchant_online_payments')
            ->updateOrInsert([
                'payment_type'   => PaymentType::PAYPAL,
                'amount'         => $request->get('amount'),
                'note'           => 'Payment',
                'transaction_id' => $request->get('orderID'),
                'merchant_id'    => Auth::user()->merchant->id,
                'account_id'     => $request->account_id
            ]);
            $merchant                   = Merchant::find(Auth::user()->merchant->id);
            $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
            $merchant->save();

            $account                   = Account::find($request->account_id);
            $account->balance          = ($account->balance + $request->get('amount'));
            $account->save();
            return response()->json(['success' => true, 'data'=>[] ],200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'data'=>[] ],500);
        }

    }

    public function sslcommerzIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.sslcommerz',compact('accounts'));
    }

    public function aamarpayIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.aamarpay',compact('accounts'));
    }

    public function paystackIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.paystack',compact('accounts'));
    }

    public function initializePayment(Request $request)
    {
        try {
            $account = Account::findOrFail($request->account_id);

            if ($account->balance < $request->amount) {
                Toastr::error('Insufficient balance in merchant or account.', __('message.error'));
                return Redirect::back();
            }

            $url = "https://api.paystack.co/transaction/initialize";
            $callback_url = MerchantSettings('paystack_callback_url');

            $fields = [
                "amount" => $request->amount * 100,
                "email" => $account->user->email,
                "orderID" => uniqid(),
                "callback_url" => $callback_url,
                "metadata" => [
                    "account_id" => $request->account_id,
                    "amount" => $request->amount,
                ],
            ];

            $fields_string = http_build_query($fields);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer ". MerchantSettings('paystack_secret_key'),
                "Cache-Control: no-cache",
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \Exception('cURL error: ' . curl_error($ch));
            }

            $result = json_decode($response, true);
            curl_close($ch);

            if (!isset($result['status']) || !$result['status']) {
                throw new \Exception('Paystack error: ' . ($result['message'] ?? 'Unknown error'));
            }

            return redirect($result['data']['authorization_url']);
        } catch (\Exception $e) {
            Toastr::error($e->getMessage(), __('message.error'));
            return redirect()->back()->withInput();
        }
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
            "Authorization: Bearer ". MerchantSettings('paystack_secret_key'),
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
            return redirect()->route('online.payment.index');
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

            $account = Account::find($metadata['account_id']);
            $amount = $metadata['amount'];

            if ($data['status'] !== 'success') {
                DB::rollBack();
                Toastr::error('Payment failed. Please try again.', __('message.error'));
                return redirect()->back();
            }
            $merchant = Merchant::find(auth()->user()->merchant->id);

            $payment = new MerchantOnlinePayment();
            $payment->payment_type = PaymentType::PAYSTACK;
            $payment->amount = $amount;
            $payment->note = 'Payment';
            $payment->transaction_id = $data['reference'] ?? null;
            $payment->account_id = $metadata['account_id'];
            $payment->merchant_id = $merchant->id;
            $payment->save();

            $account->increment('balance', $amount);
            $merchant->decrement('current_balance', $amount);
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
