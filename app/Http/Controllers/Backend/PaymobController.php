<?php

namespace App\Http\Controllers\Backend;

use App\Enums\BooleanStatus;
use App\Enums\PayoutSetup;
use App\Http\Controllers\Controller;
use App\Models\Backend\OnlinePayment;
use App\Traits\OnlinePaymentTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class PaymobController extends Controller
{
    use OnlinePaymentTrait;

    // default responses
    const GATEWAYS_DEFAULT_200 = [
        'response_code' => 'gateways_default_200',
        'message' => 'successfully loaded',
    ];

    const GATEWAYS_DEFAULT_204 = [
        'response_code' => 'gateways_default_204',
        'message' => 'information not found',
    ];

    const GATEWAYS_DEFAULT_400 = [
        'response_code' => 'gateways_default_400',
        'message' => 'invalid or missing information',
    ];

    const GATEWAYS_DEFAULT_404 = [
        'response_code' => 'gateways_default_404',
        'message' => 'resource not found',
    ];

    const GATEWAYS_DEFAULT_UPDATE_200 = [
        'response_code' => 'gateways_default_update_200',
        'message' => 'successfully updated',
    ];

    private OnlinePayment $payment;

    public function __construct(OnlinePayment $payment)
    {
        $this->payment = $payment;
    }

    public function response_formatter($constant, $content = null, $errors = []): array
    {
        $constant = (array) $constant;
        $constant['content'] = $content;
        $constant['errors'] = $errors;

        return $constant;
    }

    public function error_processor($validator): array
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => $error[0]];
        }

        return $errors;
    }

    protected function cURL($url, $json)
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = [];
        $headers[] = 'Content-Type: application/json';

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);

        return json_decode($output);
    }

    protected function GETcURL($url)
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = [];
        $headers[] = 'Content-Type: application/json';

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);

        return json_decode($output);
    }

    public function credit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'payment_id' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(self::GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => BooleanStatus::NO])->first();

        if (! isset($data)) {
            return response()->json($this->response_formatter(self::GATEWAYS_DEFAULT_204), 200);
        }

        $payer = $data->payer_details;

        try {
            $token = $this->getToken();
            $order = $this->createOrder($token, $data, $payer);
            $paymentToken = $this->getPaymentToken($order, $token, $data, $payer);
            $data->order_id = $order->id;
            $data->save();

        } catch (\Exception $exception) {
            return response()->json($this->response_formatter(self::GATEWAYS_DEFAULT_404), 200);
        }

        return Redirect::away('https://accept.paymobsolutions.com/api/acceptance/iframes/'.globalSettings('paymob_iframe_id').'?payment_token='.$paymentToken);
    }

    public function getToken()
    {
        $response = $this->cURL(
            'https://accept.paymob.com/api/auth/tokens',
            ['api_key' => globalSettings('paymob_api_key')]
        );

        return $response->token;
    }

    public function createOrder($token, $payment_data, $payer)
    {
        $items[] = [
            'name' => $payer['name'],
            'amount_cents' => round($payment_data->amount * 100),
            'description' => 'payment ID :'.$payment_data->id,
            'quantity' => 1,
        ];

        $data = [
            'auth_token' => $token,
            'delivery_needed' => 'false',
            'amount_cents' => round($payment_data->amount * 100),
            'currency' => $payment_data->currency_code ?? 'EGP',
            'items' => $items,
        ];
        $response = $this->cURL(
            'https://accept.paymob.com/api/ecommerce/orders',
            $data
        );

        return $response;
    }

    public function getPaymentToken($order, $token, $payment_data, $payer)
    {

        $value = $payment_data->amount;
        $billingData = [
            'apartment' => 'N/A',
            'email' => $payer['email'] ?? 'N/A',
            'floor' => 'N/A',
            'first_name' => $payer['name'] ?? 'N/A',
            'street' => 'N/A',
            'building' => 'N/A',
            'phone_number' => $payer['phone'] ?? 'N/A',
            'shipping_method' => 'PKG',
            'postal_code' => 'N/A',
            'city' => 'N/A',
            'country' => 'N/A',
            'last_name' => $payer['name'] ?? 'N/A',
            'state' => 'N/A',
        ];

        $data = [
            'auth_token' => $token,
            'amount_cents' => round($value * 100),
            'expiration' => 3600,
            'order_id' => @$order->id,
            'billing_data' => $billingData,
            'currency' => $payment_data->currency_code ?? 'EGP',
            'integration_id' => globalSettings('paymob_integration_id'),
        ];

        $response = $this->cURL(
            'https://accept.paymob.com/api/acceptance/payment_keys',
            $data
        );

        return $response->token;
    }

    public function callback(Request $request)
    {
        $data = $request->all();

        ksort($data);
        $hmac = $data['hmac'];
        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];
        $connectedString = '';
        foreach ($data as $key => $element) {
            if (in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = globalSettings('paymob_hmac');
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac && $data['success'] === 'true') {

            $this->payment::where(['order_id' => $data['order']])->update([
                'payment_method' => __('PayoutSetup.'.PayoutSetup::PAYMOB),
                'is_paid' => BooleanStatus::YES,
                'transaction_id' => $data['order'],
            ]);

            $payment_data = $this->payment::where(['order_id' => $data['order']])->first();

            if (isset($payment_data)) {
                return $this->payment_response($payment_data, 'success', PayoutSetup::PAYMOB);
            }

        }
        $payment_data = $this->payment::where(['order_id' => $data['order']])->first();

        return $this->payment_response($payment_data, 'fail', PayoutSetup::PAYMOB);
    }
}
