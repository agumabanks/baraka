<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Parcel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder as ScoutBuilder;

class SearchService
{
    protected string $driver;

    public function __construct()
    {
        $this->driver = config('search.driver', 'postgres_fts');
    }

    /**
     * Search across shipments, parcels, and customers
     */
    public function search(string $query, array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $results = collect();

        // Search shipments
        $shipmentResults = $this->searchShipments($query, $filters, $user);
        $results = $results->merge($shipmentResults->map(fn($item) => [
            'type' => 'shipment',
            'model' => $item,
            'title' => $item->tracking_number ?? 'Shipment',
            'subtitle' => $item->customer->name ?? 'Unknown Customer',
            'url' => route('admin.shipments.show', $item->id),
        ]));

        // Search parcels
        $parcelResults = $this->searchParcels($query, $filters, $user);
        $results = $results->merge($parcelResults->map(fn($item) => [
            'type' => 'parcel',
            'model' => $item,
            'title' => $item->sscc ?? $item->tracking_id,
            'subtitle' => $item->customer_name ?? 'Unknown',
            'url' => route('admin.parcels.show', $item->id),
        ]));

        // Search customers
        $customerResults = $this->searchCustomers($query, $filters, $user);
        $results = $results->merge($customerResults->map(fn($item) => [
            'type' => 'customer',
            'model' => $item,
            'title' => $item->name,
            'subtitle' => $item->email,
            'url' => route('admin.customers.show', $item->id),
        ]));

        // Sort by relevance and paginate
        $sorted = $results->sortByDesc(function ($item) {
            return $this->calculateRelevanceScore($item, $query);
        });

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return new LengthAwarePaginator(
            $sorted->forPage($page, $perPage),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * Search shipments using configured driver
     */
    protected function searchShipments(string $query, array $filters, ?User $user): Collection
    {
        $queryBuilder = Shipment::with(['customer', 'originBranch', 'destBranch']);

        // Apply ABAC filtering
        if ($user && !$user->hasRole('hq_admin')) {
            $queryBuilder->where(function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                  ->orWhere('dest_branch_id', $user->hub_id);
            });
        }

        if ($this->driver === 'postgres_fts') {
            return $this->postgresFtsSearch($queryBuilder, $query, 'shipments');
        } else {
            return $this->scoutSearch($queryBuilder, $query, Shipment::class);
        }
    }

    /**
     * Search parcels using configured driver
     */
    protected function searchParcels(string $query, array $filters, ?User $user): Collection
    {
        $queryBuilder = Parcel::with(['shipment.customer']);

        // Apply ABAC filtering through shipment
        if ($user && !$user->hasRole('hq_admin')) {
            $queryBuilder->whereHas('shipment', function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                  ->orWhere('dest_branch_id', $user->hub_id);
            });
        }

        if ($this->driver === 'postgres_fts') {
            return $this->postgresFtsSearch($queryBuilder, $query, 'parcels');
        } else {
            return $this->scoutSearch($queryBuilder, $query, Parcel::class);
        }
    }

    /**
     * Search customers using configured driver
     */
    protected function searchCustomers(string $query, array $filters, ?User $user): Collection
    {
        $queryBuilder = User::where('user_type', 'customer');

        // Apply ABAC filtering - customers linked to user's shipments
        if ($user && !$user->hasRole('hq_admin')) {
            $queryBuilder->whereHas('shipments', function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                  ->orWhere('dest_branch_id', $user->hub_id);
            })->orWhere('hub_id', $user->hub_id); // Created by user's branch
        }

        if ($this->driver === 'postgres_fts') {
            return $this->postgresFtsSearch($queryBuilder, $query, 'users');
        } else {
            return $this->scoutSearch($queryBuilder, $query, User::class);
        }
    }

    /**
     * Postgres Full-Text Search implementation
     */
    protected function postgresFtsSearch($queryBuilder, string $query, string $table): Collection
    {
        // Create tsvector from searchable fields
        $searchVector = match($table) {
            'shipments' => "concat_ws(' ', tracking_number, customer_name, customer_phone, customer_email)",
            'parcels' => "concat_ws(' ', sscc, tracking_id, customer_name, customer_phone)",
            'users' => "concat_ws(' ', name, email, phone)",
            default => "concat_ws(' ', name, email)"
        };

        return $queryBuilder
            ->whereRaw("to_tsvector('english', {$searchVector}) @@ websearch_to_tsquery('english', ?)", [$query])
            ->orderByRaw("ts_rank(to_tsvector('english', {$searchVector}), websearch_to_tsquery('english', ?)) DESC", [$query])
            ->limit(50)
            ->get();
    }

    /**
     * Laravel Scout search implementation
     */
    protected function scoutSearch($queryBuilder, string $query, string $modelClass): Collection
    {
        if (!in_array(\Laravel\Scout\Searchable::class, class_uses_recursive($modelClass))) {
            // Fallback to basic search if Scout not configured
            return $queryBuilder
                ->where(function ($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                      ->orWhere('email', 'ILIKE', "%{$query}%")
                      ->orWhere('phone', 'ILIKE', "%{$query}%");
                })
                ->limit(50)
                ->get();
        }

        return $modelClass::search($query)->take(50)->get();
    }

    /**
     * Calculate relevance score for sorting
     */
    protected function calculateRelevanceScore(array $item, string $query): float
    {
        $score = 0;
        $query = strtolower($query);
        $title = strtolower($item['title']);
        $subtitle = strtolower($item['subtitle']);

        // Exact matches get highest score
        if ($title === $query) $score += 100;
        if ($subtitle === $query) $score += 80;

        // Starts with query
        if (str_starts_with($title, $query)) $score += 50;
        if (str_starts_with($subtitle, $query)) $score += 40;

        // Contains query
        if (str_contains($title, $query)) $score += 30;
        if (str_contains($subtitle, $query)) $score += 20;

        // Type priority
        $typePriority = ['shipment' => 10, 'parcel' => 5, 'customer' => 1];
        $score += $typePriority[$item['type']] ?? 0;

        return $score;
    }

    /**
     * Switch search driver
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }
}