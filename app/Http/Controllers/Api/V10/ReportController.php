<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Http\Controllers\Controller;
use App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    use ApiReturnFormatTrait;

    protected $repo;

    protected $data = [];

    public function __construct(TotalSummeryReportInterface $repo)
    {
        $this->repo = $repo;
    }

    public function TotalSummeryStatementReports(Request $request)
    {

        try {
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $from = $request->input('date_from') ?: $request->input('date_to');
                $to = $request->input('date_to') ?: $request->input('date_from');

                if ($from) {
                    $range = trim($from);
                    if ($to) {
                        $range .= ' To '.trim($to);
                    }

                    $request->merge(['parcel_date' => $range]);
                }
            }

            $user = Auth::user();
            $isMerchant = (bool) optional($user)->merchant;

            if ($isMerchant) {
                $merchant = $user->merchant;
                $totalParcels = $this->repo->merchantparcelTotalSummeryReports($request);
                $accounts = $user->accounts ?? collect();
            } else {
                $merchant = null;
                $totalParcels = $this->repo->parcelTotalSummeryReports($request);
                $accounts = $this->repo->accounts($request);
            }

            $parcelsStatus = $totalParcels->countBy('status');
            $parcelStatusWiseCount = [];
            foreach ($parcelsStatus as $key => $count) {
                $parcelStatusWiseCount[__('parcelStatus.'.$key)] = $count;
            }
            $parcelsMerchant = $totalParcels->groupBy('merchant_id');
            $parcels = $totalParcels;
            $parcelsDelivered = $totalParcels->where('status', ParcelStatus::DELIVERED);
            $parcelsPartialDelivered = $totalParcels->where('partial_delivered', 1);

            $parcelsTotal['totalBankOpeningBalance'] = $accounts->sum('opening_balance');
            $parcelsTotal['totalBankBalance'] = $accounts->sum('balance');
            $parcelsTotal['totalPaybleAmount'] = 0;
            $parcelsTotal['totalCashCollection'] = 0;
            $parcelsTotal['totalSellingPrice'] = 0;
            $parcelsTotal['totalDeliveryIncome'] = 0;
            $parcelsTotal['totalDeliveryExpense'] = 0;

            $parcelProfit['total_delivery_charge'] = 0;

            $merchantID = [];
            foreach ($parcelsMerchant as $key => $value) {
                if ($key !== null) {
                    $merchantID[] = $key;
                }
            }
            $merchantTotalPayment = empty($merchantID)
                ? ['paidAmount' => 0, 'pendingAmount' => 0]
                : merchantPayments($merchantID);

            $parcelsTotal['totalCashCollection'] = $parcelsDelivered->sum('cash_collection') + $parcelsPartialDelivered->sum('cash_collection');
            $parcelsTotal['totalPaybleAmount'] = $parcelsDelivered->sum('current_payable') + $parcelsPartialDelivered->sum('current_payable');
            $parcelsTotal['totalSellingPrice'] = $parcelsDelivered->sum('selling_price') + $parcelsPartialDelivered->sum('selling_price');

            foreach ($parcels as $parcel) {
                if (! blank($parcel->deliverymanStatement)) {
                    $parcelProfit['total_delivery_charge'] += $parcel->total_delivery_amount;
                    foreach ($parcel->deliverymanStatement as $deliveryStatement) {
                        if ($deliveryStatement->type == StatementType::INCOME) {
                            $parcelsTotal['totalDeliveryIncome'] += $deliveryStatement->amount;
                        } else {
                            $parcelsTotal['totalDeliveryExpense'] += $deliveryStatement->amount;
                        }
                    }

                }
            }

            $parcelProfit['total_profit'] = $parcelsTotal['totalCashCollection'] - $parcelsTotal['totalSellingPrice'];
            $cashCollectionInfo['totalCashCollection'] = $parcelsTotal['totalCashCollection'];
            $cashCollectionInfo['totalSellingPrice'] = $parcelsTotal['totalSellingPrice'];

            $this->data['currency'] = settings()->currency;

            $this->data['request'] = $request->all();
            $this->data['merchant'] = $merchant;
            $this->data['parcelStatusWiseCount'] = $parcelStatusWiseCount;
            $this->data['profitInfo'] = $parcelProfit;
            $this->data['cashCollectionInfo'] = $cashCollectionInfo;

            $payableToMerchant['total_payable_merchant'] = $parcelsTotal['totalPaybleAmount'];
            $payableToMerchant['total_paid_by_merchant'] = $merchantTotalPayment['paidAmount'];
            $this->data['payableToMerchant'] = $payableToMerchant;

            return $this->responseWithSuccess('Data filtered successfully.', $this->data);

        } catch (\Throwable $th) {
            return $this->responseWithError('Something went wrong.', $th);
        }
    }
}
