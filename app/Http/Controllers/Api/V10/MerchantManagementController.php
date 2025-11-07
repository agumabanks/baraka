<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Models\MerchantPayment;
use App\Models\MerchantShops;
use App\Models\Parcel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $activeStatuses = [
            ParcelStatus::PENDING,
            ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::PICKUP_RE_SCHEDULE,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN,
            ParcelStatus::RECEIVED_WAREHOUSE,
            ParcelStatus::RECEIVED_BY_HUB,
            ParcelStatus::TRANSFER_TO_HUB,
            ParcelStatus::DELIVERY_MAN_ASSIGN,
            ParcelStatus::DELIVERY_RE_SCHEDULE,
            ParcelStatus::RETURN_WAREHOUSE,
            ParcelStatus::RETURN_TO_COURIER,
        ];

        $deliveredStatuses = [
            ParcelStatus::DELIVERED,
            ParcelStatus::DELIVER,
            ParcelStatus::PARTIAL_DELIVERED,
        ];

        $query = Merchant::query()
            ->with(['user:id,name,email,mobile,phone_e164', 'merchantShops:id,merchant_id,name,contact_no,address'])
            ->withCount([
                'merchantShops as active_shops_count' => function ($builder) {
                    $builder->where('status', Status::ACTIVE);
                },
                'parcels as total_shipments_count',
                'parcels as active_shipments_count' => function ($builder) use ($activeStatuses) {
                    $builder->whereIn('status', $activeStatuses);
                },
                'parcels as delivered_shipments_count' => function ($builder) use ($deliveredStatuses) {
                    $builder->whereIn('status', $deliveredStatuses);
                },
            ])
            ->withSum([
                'parcels as cod_open_balance' => function ($builder) use ($deliveredStatuses) {
                    $builder->whereNotIn('status', $deliveredStatuses);
                },
            ], 'cod_amount')
            ->withSum([
                'parcels as cod_collected' => function ($builder) use ($deliveredStatuses) {
                    $builder->whereIn('status', $deliveredStatuses);
                },
            ], 'cod_amount');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('business_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $merchants = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn (Merchant $merchant) => $this->toListPayload($merchant));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $merchants->items(),
                'meta' => [
                    'current_page' => $merchants->currentPage(),
                    'last_page' => $merchants->lastPage(),
                    'per_page' => $merchants->perPage(),
                    'total' => $merchants->total(),
                ],
                'filters' => [
                    'statuses' => [Status::ACTIVE, Status::INACTIVE],
                ],
            ],
        ]);
    }

    public function show(Merchant $merchant): JsonResponse
    {
        $deliveredStatuses = [
            ParcelStatus::DELIVERED,
            ParcelStatus::DELIVER,
            ParcelStatus::PARTIAL_DELIVERED,
        ];

        $merchant->load([
            'user:id,name,email,mobile,phone_e164',
            'merchantShops:id,merchant_id,name,contact_no,address,default_shop',
        ]);

        $recentParcels = $merchant->parcels()
            ->with(['merchantShop:id,name,merchant_id', 'hub'])
            ->latest('created_at')
            ->take(8)
            ->get();

        $paymentAccounts = MerchantPayment::where('merchant_id', $merchant->id)
            ->select(['id', 'payment_method', 'bank_name', 'account_no', 'mobile_company', 'mobile_no'])
            ->get();

        $codOutstanding = $merchant->parcels()
            ->whereNotIn('status', $deliveredStatuses)
            ->sum('cod_amount');

        $codCollected = $merchant->parcels()
            ->whereIn('status', $deliveredStatuses)
            ->sum('cod_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'merchant' => $this->toDetailPayload($merchant, $recentParcels, $paymentAccounts, [
                    'cod_outstanding' => $codOutstanding,
                    'cod_collected' => $codCollected,
                ]),
            ],
        ]);
    }

    protected function toListPayload(Merchant $merchant): array
    {
        $primaryContact = $merchant->user;
        $phoneNumber = $this->resolvePhone($primaryContact);

        return [
            'id' => $merchant->id,
            'business_name' => $merchant->business_name ?? $merchant->title,
            'current_balance' => (float) $merchant->current_balance,
            'status' => $merchant->status,
            'primary_contact' => $primaryContact ? [
                'name' => $primaryContact->name,
                'email' => $primaryContact->email,
                'phone' => $phoneNumber,
            ] : null,
            'metrics' => [
                'active_shipments' => (int) ($merchant->active_shipments_count ?? 0),
                'delivered_shipments' => (int) ($merchant->delivered_shipments_count ?? 0),
                'total_shipments' => (int) ($merchant->total_shipments_count ?? 0),
                'cod_open_balance' => (float) ($merchant->cod_open_balance ?? 0),
                'cod_collected' => (float) ($merchant->cod_collected ?? 0),
                'active_shops' => (int) ($merchant->active_shops_count ?? 0),
            ],
            'shops' => $merchant->merchantShops->map(function (MerchantShops $shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'contact_no' => $shop->contact_no,
                    'address' => $shop->address,
                ];
            })->values(),
        ];
    }

    protected function toDetailPayload(Merchant $merchant, $recentParcels, $paymentAccounts, array $codSummary): array
    {
        $listPayload = $this->toListPayload($merchant);

        $recentParcelsFormatted = $recentParcels->map(function (Parcel $parcel) {
            return [
                'id' => $parcel->id,
                'tracking_id' => $parcel->tracking_id,
                'status' => $parcel->status,
                'cash_collection' => $parcel->cash_collection,
                'cod_amount' => $parcel->cod_amount,
                'delivery_charge' => $parcel->delivery_charge,
                'total_delivery_amount' => $parcel->total_delivery_amount,
                'created_at' => $parcel->created_at,
                'merchant_shop' => $parcel->merchantShop?->name,
            ];
        })->values();

        return array_merge($listPayload, [
            'finance' => [
                'current_balance' => (float) $merchant->current_balance,
                'cod_outstanding' => (float) ($codSummary['cod_outstanding'] ?? 0),
                'cod_collected' => (float) ($codSummary['cod_collected'] ?? 0),
            ],
            'payment_accounts' => $paymentAccounts->map(function (MerchantPayment $payment) {
                return [
                    'id' => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'bank_name' => $payment->bank_name,
                    'account_no' => $payment->account_no,
                    'mobile_company' => $payment->mobile_company,
                    'mobile_no' => $payment->mobile_no,
                ];
            })->values(),
            'recent_parcels' => $recentParcelsFormatted,
        ]);
    }

    protected function resolvePhone(?\App\Models\User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $user->mobile
            ?? $user->phone_e164
            ?? null;
    }
}

