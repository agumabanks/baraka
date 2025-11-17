<?php

namespace App\Http\Controllers\Api\V10\Analytics;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthAlertController extends Controller
{
    /**
     * List analytics/performance alerts for the dashboard.
     */
    public function index(): JsonResponse
    {
        $alerts = DB::table('analytics_alerts')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => (string) $alert->id,
                    'type' => $alert->alert_type ?? 'system',
                    'message' => $alert->description ?? $alert->title,
                    'severity' => $alert->severity ?? 'info',
                    'timestamp' => Carbon::parse($alert->created_at)->toIso8601String(),
                    'resolved' => (bool) $alert->resolved_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Acknowledge (and resolve) an alert.
     */
    public function acknowledge(Request $request, int $alertId): JsonResponse
    {
        $alert = DB::table('analytics_alerts')->where('id', $alertId)->first();

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found',
            ], 404);
        }

        $timestamp = now();

        DB::table('analytics_alerts')
            ->where('id', $alertId)
            ->update([
                'acknowledged' => true,
                'acknowledged_at' => $timestamp,
                'resolved_at' => $alert->resolved_at ?: $timestamp,
                'updated_at' => $timestamp,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'acknowledged' => true,
                'alert_id' => $alertId,
                'resolved_at' => $timestamp->toIso8601String(),
            ],
        ]);
    }
}
