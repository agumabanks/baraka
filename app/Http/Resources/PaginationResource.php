<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginationResource extends JsonResource
{
    public function __construct(LengthAwarePaginator $paginator)
    {
        parent::__construct($paginator);
    }

    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->resource;

        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
