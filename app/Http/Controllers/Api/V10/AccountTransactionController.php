<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\AccountResource;
use App\Http\Resources\v10\TransactionsResource;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountTransactionController extends Controller
{
    use ApiReturnFormatTrait;

    public function index()
    {

        try {
            $accounts = MerchantPayment::where('merchant_id', auth()->user()->merchant->id)->get();
            $transactions = Payment::where('merchant_id', auth()->user()->merchant->id)->orderByDesc('id')->get();

            return $this->responseWithSuccess(__('menus.account_transaction'), ['accounts' => AccountResource::collection($accounts), 'transactions' => TransactionsResource::collection($transactions)], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('menus.account_transaction'), [], 500);

        }

    }

    public function filter(Request $request)
    {

        try {
            $id = auth()->user()->merchant->id;
            if ($request->date && $request->type == null && $request->account == null) {
                $date = explode('To', $request->date);

                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                }
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->paginate(10);
            } elseif ($request->type && $request->date == null && $request->account == null) {
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->where('status', $request->type)->paginate(10);
            } elseif ($request->account && $request->type == null && $request->date == null) {
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->where('merchant_account', $request->account)->paginate(10);
            } elseif ($request->date && $request->type && $request->account == null) {
                $date = explode('To', $request->date);

                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                }
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('status', $request->type)->paginate(10);
            } elseif ($request->date == null && $request->type && $request->account) {
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->where('status', $request->type)->where('merchant_account', $request->account)->paginate(10);
            } elseif ($request->date && $request->type == null && $request->account) {
                $date = explode('To', $request->date);

                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                }
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('merchant_account', $request->account)->paginate(10);
            } elseif ($request->date && $request->type && $request->account) {
                $date = explode('To', $request->date);

                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                }
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('status', $request->type)->where('merchant_account', $request->account)->paginate(10);
            } else {
                $transactions = Payment::where('merchant_id', $id)->orderByDesc('id')->paginate(10);
            }

            $accounts = MerchantPayment::where('merchant_id', $id)->get();

            return $this->responseWithSuccess(__('menus.account_transaction'), ['accounts' => AccountResource::collection($accounts), 'transactions' => TransactionsResource::collection($transactions)], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('menus.account_transaction'), [], 500);

        }
    }
}
