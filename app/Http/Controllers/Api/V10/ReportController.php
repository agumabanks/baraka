<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ParcelStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StatementType;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

            if ($this->shouldUseShipmentFallback($totalParcels)) {
                $summary = $this->buildShipmentSummary($request, $user);

                return $this->responseWithSuccess('Data filtered successfully.', $summary);
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

    private function shouldUseShipmentFallback(Collection $parcels): bool
    {
        if ($parcels->isNotEmpty()) {
            return false;
        }

        if (! Schema::hasTable('shipments')) {
            return false;
        }

        return Shipment::query()->exists();
    }

    private function buildShipmentSummary(Request $request, $user): array
    {
        $query = Shipment::query();

        if ($range = $this->resolveDateRange($request)) {
            [$from, $to] = $range;
            $query->whereBetween('created_at', [$from, $to]);
        }

        if ($request->filled('hub_id')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('origin_branch_id', $request->hub_id)
                    ->orWhere('dest_branch_id', $request->hub_id);
            });
        }

        $shipments = $query->get();

        $statusCounts = $shipments->isNotEmpty()
            ? $shipments->groupBy(function ($shipment) {
                return $this->normaliseShipmentStatusValue($shipment->current_status ?? $shipment->status ?? null);
            })
                ->mapWithKeys(function ($group, $status) {
                    return [$this->formatShipmentStatusLabel($status) => $group->count()];
                })
                ->sortDesc()
                ->toArray()
            : [];

        $delivered = $shipments->filter(fn ($shipment) => $this->matchesShipmentStatus($shipment, ShipmentStatus::DELIVERED));
        $totalRevenue = $shipments->sum(fn ($shipment) => (float) ($shipment->price_amount ?? 0));
        $codCollections = $delivered->sum(fn ($shipment) => (float) Arr::get($shipment->metadata ?? [], 'cod_amount', 0));
        $settledAmount = $delivered->sum(fn ($shipment) => (float) Arr::get($shipment->metadata ?? [], 'settled_amount', 0));
        $operationalCost = $shipments->sum(fn ($shipment) => (float) Arr::get($shipment->metadata ?? [], 'operational_cost', 0));
        $sellingPrice = $delivered->sum(fn ($shipment) => (float) ($shipment->price_amount ?? 0));
        $cashCollectionTotal = $codCollections > 0 ? $codCollections : $totalRevenue;
        $currency = optional(settings())->currency ?? config('app.currency', 'UGX');

        return [
            'currency' => $currency,
            'request' => $request->all(),
            'merchant' => optional($user)->merchant,
            'parcelStatusWiseCount' => $statusCounts,
            'profitInfo' => [
                'total_delivery_charge' => $totalRevenue,
                'total_delivery_income' => $totalRevenue,
                'total_delivery_expense' => $operationalCost,
                'total_profit' => $totalRevenue - $operationalCost,
            ],
            'cashCollectionInfo' => [
                'totalCashCollection' => $cashCollectionTotal,
                'totalSellingPrice' => $sellingPrice,
            ],
            'payableToMerchant' => [
                'total_payable_merchant' => $codCollections,
                'total_paid_by_merchant' => $settledAmount,
            ],
        ];
    }

    private function resolveDateRange(Request $request): ?array
    {
        $from = $request->input('date_from');
        $to = $request->input('date_to');

        if (! $from && ! $to) {
            return null;
        }

        $start = Carbon::parse($from ?? $to)->startOfDay();
        $end = Carbon::parse($to ?? $from)->endOfDay();

        if ($start->greaterThan($end)) {
            return [$end, $start];
        }

        return [$start, $end];
    }

    private function formatShipmentStatusLabel(?string $status): string
    {
        $enum = ShipmentStatus::tryFrom($status);

        return $enum ? $enum->label() : Str::headline(strtolower($status ?? 'unknown'));
    }

    private function matchesShipmentStatus(Shipment $shipment, ShipmentStatus $status): bool
    {
        $current = $this->normaliseShipmentStatusValue($shipment->current_status ?? null);
        $legacy = $this->normaliseShipmentStatusValue($shipment->status ?? null);

        return $current === $status->value || $legacy === $status->value;
    }

    private function normaliseShipmentStatusValue($status): string
    {
        if ($status instanceof ShipmentStatus) {
            return $status->value;
        }

        if (is_string($status) && $status !== '') {
            return strtoupper($status);
        }

        if (is_numeric($status)) {
            return (string) $status;
        }

        return 'UNKNOWN';
    }
}
