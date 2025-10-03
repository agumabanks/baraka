<?php

namespace App\Http\Controllers\Api\Sales;

use App\Enums\Status as UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\CustomerResource;
use App\Models\AddressBook;
use App\Models\Backend\Hub;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation as Quote;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private const DORMANT_THRESHOLD_DAYS = 60;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = (int) min(max($request->integer('per_page', 15), 1), 100);
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $baseQuery = $this->buildBaseQuery($request, $monthStart, $now);
        $summary = $this->summariseCustomers(clone $baseQuery, $now);

        if ($engagement = $request->string('engagement')->lower()->value()) {
            $this->applyEngagementFilter($baseQuery, $engagement, $now);
        }

        $paginator = $baseQuery
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $items = $this->transformPaginator($paginator, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => $this->paginationMeta($paginator),
                'summary' => $summary,
                'filters' => [
                    'engagement_options' => $this->engagementOptions(),
                    'status_options' => [
                        ['value' => 'active', 'label' => __('status.'.UserStatus::ACTIVE)],
                        ['value' => 'inactive', 'label' => __('status.'.UserStatus::INACTIVE)],
                    ],
                    'hub_options' => $this->resolveHubOptions($request),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'hub_id' => ['nullable', 'integer', 'exists:hubs,id'],
            'address' => ['nullable', 'string'],
        ]);

        $mobile = Arr::get($validated, 'mobile') ?: Arr::get($validated, 'phone');

        if ($mobile && User::where('mobile', $mobile)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('The phone number has already been taken.'),
                'errors' => ['mobile' => [__('The phone number has already been taken.')]],
            ], 422);
        }

        $customer = new User();
        $customer->name = $validated['name'];
        $customer->email = $validated['email'];
        $customer->password = Hash::make($validated['password'] ?? Str::random(12));
        $customer->hub_id = $validated['hub_id'] ?? null;
        if ($mobile) {
            $customer->mobile = $mobile;
        }
        if (! empty($validated['address'])) {
            $customer->address = $validated['address'];
        }
        $customer->status = UserStatus::ACTIVE;
        $customer->save();

        $customer->load('hub');

        return response()->json([
            'success' => true,
            'message' => __('Customer created successfully'),
            'data' => [
                'customer' => (new CustomerResource($customer))->toArray($request),
            ],
        ], 201);
    }

    public function show(Request $request, User $customer)
    {
        $this->authorize('view', $customer);

        $customer->load('hub');

        $addresses = AddressBook::query()
            ->where('customer_id', $customer->id)
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'type', 'name', 'phone_e164', 'email', 'country', 'city', 'address_line', 'tax_id', 'created_at'])
            ->map(fn ($address) => [
                'id' => $address->id,
                'type' => $address->type,
                'name' => $address->name,
                'phone' => $address->phone_e164,
                'email' => $address->email,
                'country' => $address->country,
                'city' => $address->city,
                'address_line' => $address->address_line,
                'tax_id' => $address->tax_id,
                'created_at' => optional($address->created_at)->toIso8601String(),
            ]);

        $shipmentsQuery = Shipment::query()->where('customer_id', $customer->id);

        $shipmentSummary = [
            'total' => (clone $shipmentsQuery)->count(),
            'total_value' => (float) ((clone $shipmentsQuery)->sum('price_amount')),
            'currency' => optional(settings())->currency ?? 'USD',
            'by_status' => (clone $shipmentsQuery)
                ->select('current_status', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('current_status')
                ->get()
                ->mapWithKeys(fn ($row) => [(string) $row->current_status => (int) $row->aggregate])
                ->all(),
        ];

        $recentShipmentsCollection = (clone $shipmentsQuery)
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'service_level', 'current_status', 'price_amount', 'currency', 'created_at'])
            ->map(fn ($shipment) => [
                'id' => $shipment->id,
                'service_level' => $shipment->service_level,
                'status' => (string) $shipment->current_status,
                'value' => $shipment->price_amount !== null ? (float) $shipment->price_amount : null,
                'currency' => $shipment->currency ?? ($shipmentSummary['currency'] ?? 'USD'),
                'created_at' => optional($shipment->created_at)->toIso8601String(),
            ]);
        $recentShipments = $recentShipmentsCollection->values()->all();

        $invoiceQuery = Invoice::query()->whereHas('shipment', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        });
        $billingCurrency = optional((clone $invoiceQuery)->latest('created_at')->first())->currency ?? ($shipmentSummary['currency'] ?? 'USD');

        $billingSummary = [
            'open_amount' => (float) ((clone $invoiceQuery)->where('status', '!=', 'PAID')->sum('total_amount')),
            'paid_amount' => (float) ((clone $invoiceQuery)->where('status', 'PAID')->sum('total_amount')),
            'overdue_count' => (clone $invoiceQuery)->where('status', '!=', 'PAID')->whereDate('due_date', '<', now())->count(),
            'currency' => $billingCurrency,
            'recent' => (clone $invoiceQuery)
                ->latest('created_at')
                ->take(5)
                ->get(['id', 'invoice_number', 'status', 'total_amount', 'currency', 'due_date', 'paid_at', 'created_at'])
                ->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'total_amount' => (float) $invoice->total_amount,
                    'currency' => $invoice->currency ?? $billingCurrency,
                    'due_date' => optional($invoice->due_date)->toDateString(),
                    'paid_at' => optional($invoice->paid_at)->toIso8601String(),
                    'created_at' => optional($invoice->created_at)->toIso8601String(),
                ]),
        ];

        $paymentsSummary = [
            'paid_invoices_total' => $billingSummary['paid_amount'],
            'open_invoices_total' => $billingSummary['open_amount'],
            'currency' => $billingSummary['currency'],
            'recent' => $billingSummary['recent']->filter(fn ($invoice) => $invoice['paid_at'])->take(5)->values(),
        ];

        $quotationQuery = Quote::query()->where('customer_id', $customer->id);
        $quotations = [
            'total' => (clone $quotationQuery)->count(),
            'accepted' => (clone $quotationQuery)->where('status', 'accepted')->count(),
            'recent' => (clone $quotationQuery)
                ->latest('created_at')
                ->take(5)
                ->get(['id', 'service_type', 'destination_country', 'total_amount', 'currency', 'status', 'valid_until', 'created_at'])
                ->map(fn ($quote) => [
                    'id' => $quote->id,
                    'service_type' => $quote->service_type,
                    'destination_country' => $quote->destination_country,
                    'total_amount' => (float) $quote->total_amount,
                    'currency' => $quote->currency,
                    'status' => $quote->status,
                    'valid_until' => optional($quote->valid_until)->toDateString(),
                    'created_at' => optional($quote->created_at)->toIso8601String(),
                ]),
        ];

        $contractQuery = Contract::query()->where('customer_id', $customer->id);
        $contracts = [
            'total' => (clone $contractQuery)->count(),
            'active' => (clone $contractQuery)->where('status', 'active')->count(),
            'recent' => (clone $contractQuery)
                ->latest('start_date')
                ->take(5)
                ->get(['id', 'name', 'status', 'start_date', 'end_date', 'notes'])
                ->map(fn ($contract) => [
                    'id' => $contract->id,
                    'name' => $contract->name,
                    'status' => $contract->status,
                    'start_date' => optional($contract->start_date)->toDateString(),
                    'end_date' => optional($contract->end_date)->toDateString(),
                    'notes' => $contract->notes,
                ]),
        ];

        $preferences = [
            'preferred_hub' => $customer->hub?->name,
            'primary_contact' => $addresses->firstWhere('type', 'shipper')['name'] ?? $customer->name,
            'communication_channels' => array_values(array_filter([
                $customer->email ? 'Email' : null,
                $customer->phone ? 'Phone' : null,
                $addresses->isNotEmpty() ? 'Mailing Address' : null,
            ])),
            'default_address' => $addresses->first(),
        ];

        $feedback = [
            'quotation_conversion_rate' => $quotations['total'] > 0
                ? round(($quotations['accepted'] / $quotations['total']) * 100, 1)
                : 0,
            'open_invoices' => $billingSummary['open_amount'],
            'open_shipments' => $shipmentSummary['by_status']['OPEN'] ?? 0,
        ];

        $history = $recentShipmentsCollection
            ->map(fn ($shipment) => [
                'type' => 'shipment',
                'reference' => $shipment['id'],
                'status' => $shipment['status'],
                'timestamp' => $shipment['created_at'],
            ])
            ->merge($billingSummary['recent']->map(fn ($invoice) => [
                'type' => 'invoice',
                'reference' => $invoice['number'],
                'status' => $invoice['status'],
                'timestamp' => $invoice['created_at'],
            ]))
            ->merge($quotations['recent']->map(fn ($quote) => [
                'type' => 'quotation',
                'reference' => $quote['id'],
                'status' => $quote['status'],
                'timestamp' => $quote['created_at'],
            ]))
            ->sortByDesc('timestamp')
            ->values();

        $billingSummary['recent'] = $billingSummary['recent']->values()->all();
        $paymentsSummary['recent'] = $paymentsSummary['recent']->values()->all();
        $quotations['recent'] = $quotations['recent']->values()->all();
        $contracts['recent'] = $contracts['recent']->values()->all();
        $history = $history->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => (new CustomerResource($customer))->toArray($request),
                'addresses' => $addresses,
                'shipments' => [
                    'summary' => $shipmentSummary,
                    'recent' => $recentShipments,
                ],
                'billing' => $billingSummary,
                'payments' => $paymentsSummary,
                'quotations' => $quotations,
                'contracts' => $contracts,
                'preferences' => $preferences,
                'feedback' => $feedback,
                'history' => $history,
            ],
        ]);
    }

    public function update(Request $request, User $customer)
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($customer->id)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'hub_id' => ['nullable', 'integer', 'exists:hubs,id'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $mobile = Arr::get($validated, 'mobile') ?: Arr::get($validated, 'phone');

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'hub_id' => $validated['hub_id'] ?? null,
        ];

        if (! empty($validated['address'])) {
            $payload['address'] = $validated['address'];
        }

        if ($mobile) {
            $payload['mobile'] = $mobile;
        }

        if (isset($validated['status'])) {
            $payload['status'] = $validated['status'] === 'active' ? UserStatus::ACTIVE : UserStatus::INACTIVE;
        }

        $customer->fill($payload)->save();

        $customer->refresh()->load('hub');

        return response()->json([
            'success' => true,
            'message' => __('Customer updated successfully'),
            'data' => [
                'customer' => (new CustomerResource($customer))->toArray($request),
            ],
        ]);
    }

    public function destroy(Request $request, User $customer)
    {
        $this->authorize('delete', $customer);

        try {
            $customer->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Unable to delete customer at this time.'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Customer deleted successfully'),
        ]);
    }

    public function meta(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        return response()->json([
            'success' => true,
            'data' => [
                'hub_options' => $this->resolveHubOptions($request),
                'status_options' => [
                    ['value' => 'active', 'label' => __('status.'.UserStatus::ACTIVE)],
                    ['value' => 'inactive', 'label' => __('status.'.UserStatus::INACTIVE)],
                ],
                'engagement_options' => $this->engagementOptions(),
            ],
        ]);
    }

    private function buildBaseQuery(Request $request, Carbon $monthStart, Carbon $now): Builder
    {
        $user = $request->user();

        $query = User::query()
            ->with(['hub:id,name'])
            ->select('id', 'name', 'email', 'mobile', 'phone_e164', 'address', 'hub_id', 'status', 'created_at', 'updated_at')
            ->withCount([
                'shipments as shipments_this_month' => function ($shipments) use ($monthStart, $now) {
                    $shipments->whereBetween('created_at', [$monthStart, $now]);
                },
                'shipments as shipments_total' => function ($shipments) {
                    $shipments->selectRaw('count(*)');
                },
            ])
            ->withMax('shipments as last_shipment_at', 'created_at');

        if (! $user->hasRole(['hq_admin', 'admin', 'super-admin'])) {
            $query->where(function ($scoped) use ($user) {
                $scoped->whereHas('shipments', function ($shipments) use ($user) {
                    $shipments->where('origin_branch_id', $user->hub_id)
                        ->orWhere('dest_branch_id', $user->hub_id);
                });

                if ($user->hub_id) {
                    $scoped->orWhere('hub_id', $user->hub_id);
                }
            });
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($filters) use ($search) {
                $filters->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone_e164', 'like', "%{$search}%");
            });
        }

        if ($hubId = $request->integer('hub_id')) {
            $query->where('hub_id', $hubId);
        }

        if (! is_null($request->input('status'))) {
            $statusFilter = $request->string('status')->lower()->value();
            if ($statusFilter === 'active') {
                $query->where('status', UserStatus::ACTIVE);
            } elseif ($statusFilter === 'inactive') {
                $query->where('status', UserStatus::INACTIVE);
            }
        }

        return $query;
    }

    private function applyEngagementFilter(Builder $query, string $engagement, Carbon $now): void
    {
        $threshold = $now->copy()->subDays(self::DORMANT_THRESHOLD_DAYS);

        if ($engagement === 'active') {
            $query->having('shipments_this_month', '>', 0);
            return;
        }

        if ($engagement === 'at_risk') {
            $query->having('shipments_this_month', '=', 0)
                ->havingRaw('(last_shipment_at IS NOT NULL AND last_shipment_at >= ?)', [$threshold]);

            return;
        }

        if ($engagement === 'dormant') {
            $query->having('shipments_this_month', '=', 0)
                ->havingRaw('(last_shipment_at IS NULL OR last_shipment_at < ?)', [$threshold]);
        }
    }

    private function summariseCustomers(Builder $query, Carbon $now): array
    {
        $threshold = $now->copy()->subDays(self::DORMANT_THRESHOLD_DAYS);

        $customers = (clone $query)->reorder()->get();

        $total = $customers->count();
        $active = $customers->filter(fn ($customer) => (int) ($customer->shipments_this_month ?? 0) > 0)->count();
        $atRisk = $customers->filter(function ($customer) use ($threshold) {
            $ships = (int) ($customer->shipments_this_month ?? 0);
            if ($ships > 0) {
                return false;
            }

            $last = $customer->last_shipment_at ? Carbon::parse($customer->last_shipment_at) : null;
            return $last !== null && $last->greaterThanOrEqualTo($threshold);
        })->count();
        $dormant = $customers->filter(function ($customer) use ($threshold) {
            $ships = (int) ($customer->shipments_this_month ?? 0);
            if ($ships > 0) {
                return false;
            }

            $last = $customer->last_shipment_at ? Carbon::parse($customer->last_shipment_at) : null;
            return $last === null || $last->lessThan($threshold);
        })->count();

        $shipmentsThisMonth = (int) $customers->sum(fn ($customer) => (int) ($customer->shipments_this_month ?? 0));

        $customerIds = $customers->pluck('id')->filter();
        $lifetimeShipments = $customerIds->isEmpty()
            ? 0
            : Shipment::query()->whereIn('customer_id', $customerIds)->count();

        return [
            'totals' => [
                'customers' => $total,
                'active' => $active,
                'at_risk' => $atRisk,
                'dormant' => $dormant,
            ],
            'shipments' => [
                'this_month' => (int) $shipmentsThisMonth,
                'lifetime' => $lifetimeShipments,
            ],
            'generated_at' => $now->toIso8601String(),
        ];
    }

    private function transformPaginator(LengthAwarePaginator $paginator, Request $request): array
    {
        return $paginator->getCollection()
            ->map(fn ($customer) => (new CustomerResource($customer))->toArray($request))
            ->all();
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    private function resolveHubOptions(Request $request): array
    {
        $user = $request->user();

        $query = Hub::query()
            ->select('id', 'name')
            ->orderBy('name');

        if (! $user->hasRole(['hq_admin', 'admin', 'super-admin'])) {
            if ($user->hub_id) {
                $query->where('id', $user->hub_id);
            } else {
                $query->limit(0);
            }
        }

        return $query->get()->map(fn ($hub) => [
            'value' => (string) $hub->id,
            'label' => $hub->name,
        ])->all();
    }

    private function engagementOptions(): array
    {
        return [
            ['value' => 'active', 'label' => __('Active')],
            ['value' => 'at_risk', 'label' => __('At Risk')],
            ['value' => 'dormant', 'label' => __('Dormant')],
        ];
    }
}
