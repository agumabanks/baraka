<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\RecordScanEventRequest;
use App\Http\Resources\v10\ScanEventResource;
use App\Services\Logistics\ScanEventService;
use Illuminate\Http\JsonResponse;

class ScanEventController extends Controller
{
    public function __construct(private ScanEventService $scanEventService)
    {
    }

    public function store(RecordScanEventRequest $request): JsonResponse
    {
        $event = $this->scanEventService->record($request->validated());

        return new JsonResponse(
            new ScanEventResource($event),
            JsonResponse::HTTP_CREATED
        );
    }
}
