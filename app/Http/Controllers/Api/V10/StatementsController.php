<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\StatementsResource;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatementsController extends Controller
{
    use ApiReturnFormatTrait;

    public function index()
    {
        try {
            $user = auth()->user();
            $merchantId = $user?->merchant->id ?? null;

            $statementsQuery = MerchantStatement::with(['merchant', 'parcel'])->orderByDesc('id');

            if ($merchantId) {
                $statementsQuery->where('merchant_id', $merchantId);
            }

            $limit = (int) request('limit', 100);
            if ($limit > 0) {
                $statementsQuery->limit($limit);
            }

            $statements = $statementsQuery->get();

            return $this->responseWithSuccess(__('statements.title'), [
                'statements' => StatementsResource::collection($statements),
            ], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('statements.title'), [], 500);

        }
    }

    public function filter(Request $request)
    {

        try {
            $user = auth()->user();
            $merchantId = $user?->merchant->id ?? $request->integer('merchant_id');

            $statementsQuery = MerchantStatement::with(['merchant', 'parcel'])->orderByDesc('id');

            if ($merchantId) {
                $statementsQuery->where('merchant_id', $merchantId);
            }

            if ($request->filled('date')) {
                $date = explode('To', $request->date);
                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $statementsQuery->whereBetween('created_at', [$from, $to]);
                }
            }

            if ($request->filled('type')) {
                $statementsQuery->where('type', $request->type);
            }

            if ($request->filled('parcel_tracking_id')) {
                $parcel = Parcel::where('tracking_id', $request->parcel_tracking_id)->first();
                if ($parcel) {
                    $statementsQuery->where('parcel_id', $parcel->id);
                } else {
                    $statementsQuery->whereRaw('1 = 0');
                }
            }

            $statements = $statementsQuery->get();

            return $this->responseWithSuccess(__('statements.title'), [
                'statements' => StatementsResource::collection($statements),
            ], 200);

        } catch (\Exception $exception) {

            return $this->responseWithError(__('statements.title'), [], 500);

        }

    }
}
