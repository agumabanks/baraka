<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\QuotationResource;
use App\Models\Quotation;
use App\Services\RatingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    private const STATUSES = ['draft', 'sent', 'accepted', 'expired'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Quotation::class);

        $perPage = (int) min(max($request->integer('per_page', 15), 1), 100);

        $query = $this->buildBaseQuery($request);
        $summary = $this->summariseQuotations(clone $query);

        $paginator = $query
            ->orderByDesc('created_at')
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
                    'status_options' => $this->statusOptions(),
                ],
            ],
        ]);
    }

    public function store(Request $request, RatingService $rating)
    {
        $this->authorize('create', Quotation::class);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'destination_country' => ['required', 'string', 'size:2'],
            'service_type' => ['required', 'string', 'max:255'],
            'pieces' => ['required', 'integer', 'min:1'],
            'weight_kg' => ['required', 'numeric', 'min:0'],
            'volume_cm3' => ['nullable', 'integer', 'min:1'],
            'dim_factor' => ['nullable', 'integer', 'min:1000'],
            'base_charge' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);

        $payload = Arr::only($validated, [
            'customer_id',
            'destination_country',
            'service_type',
            'pieces',
            'weight_kg',
            'volume_cm3',
            'dim_factor',
            'base_charge',
            'currency',
            'valid_until',
            'status',
        ]);

        $user = $request->user();
        $payload['origin_branch_id'] = $user->hub_id;
        $payload['created_by_id'] = $user->id;
        $payload['currency'] = strtoupper($payload['currency'] ?? (optional(settings())->currency ?? 'USD'));
        $payload['dim_factor'] = $payload['dim_factor'] ?? 5000;
        $payload['status'] = $payload['status'] ?? 'draft';

        $volume = $payload['volume_cm3'] ?? null;
        $dimWeight = $volume ? $rating->dimWeightKg((int) $volume, (int) $payload['dim_factor']) : 0;
        $billable = max((float) $payload['weight_kg'], (float) $dimWeight);

        $pricing = $rating->priceWithSurcharges((float) $payload['base_charge'], $billable, Carbon::now());
        $payload['surcharges_json'] = $pricing['applied'] ?? [];
        $payload['total_amount'] = $pricing['total'] ?? (float) $payload['base_charge'];

        $quotation = Quotation::create($payload);
        $quotation->load(['customer:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => __('Quotation created successfully'),
            'data' => [
                'quotation' => (new QuotationResource($quotation))->toArray($request),
            ],
        ], 201);
    }

    private function buildBaseQuery(Request $request): Builder
    {
        $user = $request->user();

        $query = Quotation::query()
            ->with(['customer:id,name,email'])
            ->select(
                'id',
                'customer_id',
                'origin_branch_id',
                'destination_country',
                'service_type',
                'pieces',
                'weight_kg',
                'volume_cm3',
                'dim_factor',
                'base_charge',
                'total_amount',
                'currency',
                'status',
                'valid_until',
                'created_at',
                'updated_at'
            );

        if (! $user->hasRole(['hq_admin', 'admin', 'super-admin']) && $user->hub_id) {
            $query->where('origin_branch_id', $user->hub_id);
        }

        if ($status = $request->string('status')->lower()->value()) {
            if (in_array($status, self::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('id', (int) $search)
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('destination_country', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    private function summariseQuotations(Builder $query): array
    {
        $counts = (clone $query)->reorder()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $total = array_sum($counts);
        $totalValue = (clone $query)->reorder()->sum('total_amount');
        $averageValue = $total > 0 ? round($totalValue / $total, 2) : 0.0;

        $latestExpiry = (clone $query)->reorder()
            ->whereNotNull('valid_until')
            ->orderByDesc('valid_until')
            ->value('valid_until');

        return [
            'totals' => [
                'all' => $total,
                'by_status' => array_map('intval', $counts),
            ],
            'value' => [
                'total' => (float) $totalValue,
                'average' => (float) $averageValue,
                'currency' => optional(settings())->currency ?? 'USD',
            ],
            'latest_valid_until' => $latestExpiry ? Carbon::parse($latestExpiry)->toDateString() : null,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function transformPaginator(LengthAwarePaginator $paginator, Request $request): array
    {
        return $paginator->getCollection()
            ->map(fn ($quotation) => (new QuotationResource($quotation))->toArray($request))
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

    private function statusOptions(): array
    {
        return collect(self::STATUSES)
            ->map(fn ($status) => ['value' => $status, 'label' => __(ucfirst(str_replace('_', ' ', $status)))])
            ->values()
            ->all();
    }
}

