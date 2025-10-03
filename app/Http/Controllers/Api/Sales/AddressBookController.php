<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\AddressBookResource;
use App\Models\AddressBook;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressBookController extends Controller
{
    private const TYPES = ['shipper', 'consignee', 'payer'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', AddressBook::class);

        $perPage = (int) min(max($request->integer('per_page', 20), 1), 100);

        $query = $this->buildBaseQuery($request);
        $summary = $this->summariseEntries(clone $query);

        $paginator = $query
            ->orderBy('name')
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
                    'type_options' => $this->typeOptions(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', AddressBook::class);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(self::TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'phone_e164' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email'],
            'country' => ['required', 'string', 'size:2'],
            'city' => ['required', 'string', 'max:120'],
            'address_line' => ['required', 'string'],
            'tax_id' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = $validated;
        $payload['country'] = strtoupper($payload['country']);

        $entry = AddressBook::create($payload);
        $entry->load(['customer:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => __('Address book entry created successfully'),
            'data' => [
                'entry' => (new AddressBookResource($entry))->toArray($request),
            ],
        ], 201);
    }

    private function buildBaseQuery(Request $request): Builder
    {
        $user = $request->user();

        $query = AddressBook::query()
            ->with(['customer:id,name,email,hub_id'])
            ->select('id', 'customer_id', 'type', 'name', 'phone_e164', 'email', 'country', 'city', 'address_line', 'tax_id', 'created_at', 'updated_at');

        if (! $user->hasRole(['hq_admin', 'admin', 'super-admin']) && $user->hub_id) {
            $query->whereHas('customer', function ($customer) use ($user) {
                $customer->where('hub_id', $user->hub_id);
            });
        }

        if ($type = $request->string('type')->lower()->value()) {
            if (in_array($type, self::TYPES, true)) {
                $query->where('type', $type);
            }
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function summariseEntries(Builder $query): array
    {
        $counts = (clone $query)->reorder()
            ->select('type')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        $total = array_sum($counts);

        return [
            'totals' => [
                'all' => $total,
                'by_type' => array_map('intval', $counts),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function transformPaginator(LengthAwarePaginator $paginator, Request $request): array
    {
        return $paginator->getCollection()
            ->map(fn ($entry) => (new AddressBookResource($entry))->toArray($request))
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

    private function typeOptions(): array
    {
        return collect(self::TYPES)
            ->map(fn ($type) => ['value' => $type, 'label' => __(ucfirst($type))])
            ->values()
            ->all();
    }
}

