<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\ContractResource;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    private const STATUSES = ['active', 'suspended', 'ended'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $perPage = (int) min(max($request->integer('per_page', 15), 1), 100);

        $query = $this->buildBaseQuery($request);
        $summary = $this->summariseContracts(clone $query);

        $paginator = $query
            ->orderByDesc('start_date')
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

    public function store(Request $request)
    {
        $this->authorize('create', Contract::class);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'rate_card_id' => ['nullable', 'integer', 'exists:rate_cards,id'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
            'sla' => ['nullable', 'array'],
        ]);

        $payload = Arr::only($validated, [
            'customer_id',
            'name',
            'start_date',
            'end_date',
            'rate_card_id',
            'status',
            'notes',
        ]);

        $payload['status'] = $payload['status'] ?? 'active';
        if (! empty($validated['sla'])) {
            $payload['sla_json'] = $validated['sla'];
        }

        $contract = Contract::create($payload);
        $contract->load(['customer:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => __('Contract created successfully'),
            'data' => [
                'contract' => (new ContractResource($contract))->toArray($request),
            ],
        ], 201);
    }

    private function buildBaseQuery(Request $request): Builder
    {
        $user = $request->user();

        $query = Contract::query()
            ->with(['customer:id,name,email,hub_id'])
            ->select('id', 'customer_id', 'name', 'start_date', 'end_date', 'rate_card_id', 'status', 'notes', 'sla_json', 'created_at', 'updated_at');

        if (! $user->hasRole(['hq_admin', 'admin', 'super-admin']) && $user->hub_id) {
            $query->whereHas('customer', function ($customer) use ($user) {
                $customer->where('hub_id', $user->hub_id);
            });
        }

        if ($status = $request->string('status')->lower()->value()) {
            if (in_array($status, self::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($from = $request->date('from')) {
            $query->whereDate('start_date', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('end_date', '<=', $to);
        }

        return $query;
    }

    private function summariseContracts(Builder $query): array
    {
        $counts = (clone $query)->reorder()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $total = array_sum($counts);
        $expiringSoon = (clone $query)->reorder()
            ->where('status', 'active')
            ->whereBetween('end_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->count();

        $averageDuration = (clone $query)->reorder()
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->value('avg_duration');

        return [
            'totals' => [
                'all' => $total,
                'by_status' => array_map('intval', $counts),
            ],
            'expiring_soon' => $expiringSoon,
            'average_duration_days' => $averageDuration ? round($averageDuration, 1) : 0,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function transformPaginator(LengthAwarePaginator $paginator, Request $request): array
    {
        return $paginator->getCollection()
            ->map(fn ($contract) => (new ContractResource($contract))->toArray($request))
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
            ->map(fn ($status) => ['value' => $status, 'label' => __(ucfirst($status))])
            ->values()
            ->all();
    }
}

