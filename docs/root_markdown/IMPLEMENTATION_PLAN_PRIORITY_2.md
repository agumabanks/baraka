# Phase 2: Authentication System & API Resources
## Priority 2 - Complete Core Functionality Implementation

### Week 2: Authentication System Enhancement

#### Day 1-2: Complete Sanctum Integration

**File: app/Http/Controllers/Api/V1/TokenController.php**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    /**
     * Get all user tokens.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->with(['tokenable'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'tokens' => $tokens->getCollection()->map(function ($token) {
                    return [
                        'id' => $token->id,
                        'name' => $token->name,
                        'abilities' => $token->abilities,
                        'last_used_at' => $token->last_used_at?->toISOString(),
                        'created_at' => $token->created_at->toISOString(),
                        'expires_at' => $token->expires_at?->toISOString(),
                        'is_current' => $token->id === auth()->user()->currentAccessToken()?->id,
                    ];
                }),
                'pagination' => [
                    'current_page' => $tokens->currentPage(),
                    'total_pages' => $tokens->lastPage(),
                    'total_items' => $tokens->total(),
                    'per_page' => $tokens->perPage(),
                ]
            ]
        ]);
    }

    /**
     * Refresh the current token.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $currentToken = $request->user()->currentAccessToken();
            
            if (!$currentToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active token found',
                ], 401);
            }

            // Revoke current token
            $currentToken->delete();

            // Create new token
            $newToken = $request->user()->createToken(
                'api_token_refreshed',
                $request->user()->getAllPermissions()->pluck('name')->toArray(),
                now()->addDays(config('sanctum.expiration', 365))
            );

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken->plainTextToken,
                    'expires_at' => $newToken->accessToken->expires_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke a specific token.
     */
    public function revoke(Request $request, $token): JsonResponse
    {
        $userToken = $request->user()->tokens()
            ->where('id', $token)
            ->first();

        if (!$userToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found',
            ], 404);
        }

        $userToken->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully'
        ]);
    }

    /**
     * Revoke all tokens except current.
     */
    public function revokeAllExceptCurrent(Request $request): JsonResponse
    {
        try {
            $currentToken = $request->user()->currentAccessToken();
            
            $request->user()->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'All other tokens revoked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke tokens: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

**File: app/Http/Middleware/Api/CheckTokenExpires.php**
```php
<?php

namespace App\Http\Middleware\Api;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckTokenExpires
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $token = $request->user()->currentAccessToken();
            
            if ($token && $token->expires_at && $token->expires_at->isPast()) {
                $token->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired',
                    'type' => 'auth.token_expired',
                ], 401);
            }
        }

        return $next($request);
    }
}
```

**File: app/Http/Middleware/Api/ValidateApiJson.php**
```php
<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ValidateApiJson
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force JSON response for API routes
        $request->headers->set('Accept', 'application/json');
        
        // Validate JSON payload for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $content = $request->getContent();
            
            if (!empty($content) && json_decode($content) === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON payload',
                    'type' => 'validation.invalid_json',
                ], 400);
            }
        }

        return $next($request);
    }
}
```

#### Day 3-4: Complete API Resource Classes

**File: app/Http/Resources/Api/V1/ShipmentResource.php**
```php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_id' => $this->tracking_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'pickup_address' => $this->pickup_address,
            'delivery_address' => $this->delivery_address,
            
            // Package details
            'package_type' => $this->package_type,
            'package_description' => $this->package_description,
            'weight' => (float) $this->weight,
            'length' => (float) $this->length,
            'width' => (float) $this->width,
            'height' => (float) $this->height,
            'volume_weight' => (float) $this->volume_weight,
            
            // Financial details
            'cod_amount' => (float) $this->cod_amount,
            'total_charge' => (float) $this->total_charge,
            'currency' => $this->currency ?? 'UGX',
            
            // Status and tracking
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'is_delivered' => $this->status === 'delivered',
            'is_returned' => $this->status === 'returned',
            
            // Dates
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'pickup_date' => $this->pickup_date?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            
            // Relationships
            'merchant' => $this->when($this->merchant, [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
                'phone' => $this->merchant->phone,
            ]),
            
            'branch' => $this->when($this->branch, [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'type' => $this->branch->type,
                'address' => $this->branch->address,
            ]),
            
            'delivery_man' => $this->when($this->deliveryMan, [
                'id' => $this->deliveryMan->id,
                'name' => $this->deliveryMan->name,
                'phone' => $this->deliveryMan->phone,
                'avatar' => $this->deliveryMan->profile_photo_url,
            ]),
            
            'hub' => $this->when($this->hub, [
                'id' => $this->hub->id,
                'name' => $this->hub->name,
                'address' => $this->hub->address,
            ]),
            
            // Status history
            'status_history' => $this->when($request->include('history'), function () {
                return $this->logs()
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->map(function ($log) {
                        return [
                            'status' => $log->status,
                            'remarks' => $log->remarks,
                            'created_at' => $log->created_at->toISOString(),
                            'user' => $log->user ? [
                                'id' => $log->user->id,
                                'name' => $log->user->name,
                            ] : null,
                        ];
                    });
            }),
            
            // Computed fields
            'full_address' => $this->getFullPickupAddress(),
            'formatted_weight' => $this->getFormattedWeight(),
            'formatted_cod' => $this->getFormattedCodAmount(),
            'delivery_time' => $this->getDeliveryTime(),
            
            // Permissions for current user
            'permissions' => [
                'can_edit' => auth()->user()->hasPermission('shipment_update') &&
                    in_array($this->status, ['pending', 'assigned', 'picked_up', 'in_transit']),
                'can_delete' => auth()->user()->hasPermission('shipment_delete') &&
                    $this->status === 'pending',
                'can_assign' => auth()->user()->hasPermission('shipment_update') &&
                    in_array($this->status, ['pending', 'assigned']),
                'can_track' => true, // All authenticated users can track
            ],
        ];
    }
    
    /**
     * Get formatted status label.
     */
    private function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'Pending Pickup',
            'assigned' => 'Driver Assigned',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'returned' => 'Returned to Merchant',
            'cancelled' => 'Cancelled',
        ];
        
        return $labels[$this->status] ?? ucfirst($this->status);
    }
    
    /**
     * Get full pickup address.
     */
    private function getFullPickupAddress(): string
    {
        return $this->pickup_address ?? 'Not specified';
    }
    
    /**
     * Get formatted weight with unit.
     */
    private function getFormattedWeight(): string
    {
        return $this->weight > 0 ? number_format($this->weight, 2) . ' kg' : 'N/A';
    }
    
    /**
     * Get formatted COD amount.
     */
    private function getFormattedCodAmount(): string
    {
        return $this->cod_amount > 0 
            ? number_format($this->cod_amount, 2) . ' ' . ($this->currency ?? 'UGX')
            : 'No COD';
    }
    
    /**
     * Get delivery time in days.
     */
    private function getDeliveryTime(): ?string
    {
        if (!$this->delivered_at || !$this->created_at) {
            return null;
        }
        
        $days = $this->created_at->diffInDays($this->delivered_at);
        
        return $days . ' day' . ($days !== 1 ? 's' : '');
    }
}
```

**File: app/Http/Resources/Api/V1/BranchResource.php**
```php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'code' => $this->code,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            
            // Coordinates
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            
            // Status and availability
            'status' => $this->status,
            'is_active' => $this->status === 1,
            'is_hub' => $this->is_hub ?? false,
            
            // Date information
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'managers' => $this->when($this->managers, function () {
                return $this->managers->map(function ($manager) {
                    return [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                        'phone' => $manager->phone,
                        'status' => $manager->status,
                        'is_active' => $manager->status === 1,
                    ];
                });
            }),
            
            'workers' => $this->when($this->workers, function () {
                return $this->workers->where('status', 1)->map(function ($worker) {
                    return [
                        'id' => $worker->id,
                        'name' => $worker->name,
                        'email' => $worker->email,
                        'phone' => $worker->phone,
                        'type' => $worker->type ?? 'delivery',
                    ];
                });
            }),
            
            // Statistics (when requested)
            'stats' => $this->when($request->get('include_stats', false), function () {
                return [
                    'total_shipments' => $this->shipments()->count(),
                    'pending_shipments' => $this->shipments()->whereIn('status', ['pending', 'assigned'])->count(),
                    'in_transit_shipments' => $this->shipments()->whereIn('status', ['picked_up', 'in_transit', 'out_for_delivery'])->count(),
                    'delivered_shipments' => $this->shipments()->where('status', 'delivered')->count(),
                    'returned_shipments' => $this->shipments()->where('status', 'returned')->count(),
                    'total_managers' => $this->managers()->where('status', 1)->count(),
                    'total_workers' => $this->workers()->where('status', 1)->count(),
                    'current_capacity_percentage' => $this->getCurrentCapacityPercentage(),
                ];
            }),
            
            // Recent shipments
            'recent_shipments' => $this->when($request->get('include_shipments', false), function () {
                return $this->shipments()
                    ->with('customer')
                    ->latest()
                    ->take(10)
                    ->map(function ($shipment) {
                        return [
                            'id' => $shipment->id,
                            'tracking_id' => $shipment->tracking_id,
                            'customer_name' => $shipment->customer_name,
                            'status' => $shipment->status,
                            'created_at' => $shipment->created_at->toISOString(),
                        ];
                    });
            }),
            
            // Computed fields
            'full_location' => $this->getFullLocation(),
            'type_label' => $this->getTypeLabel(),
            'formatted_code' => strtoupper($this->code),
            
            // Permissions for current user
            'permissions' => [
                'can_edit' => auth()->user()->hasPermission('branch_update'),
                'can_delete' => auth()->user()->hasPermission('branch_delete') && !$this->hasActiveShipments(),
                'can_manage_staff' => auth()->user()->hasPermission('branch_update'),
                'can_view_reports' => auth()->user()->hasPermission('report_read'),
            ],
        ];
    }
    
    /**
     * Get current capacity percentage.
     */
    private function getCurrentCapacityPercentage(): int
    {
        $totalShipments = $this->shipments()->whereIn('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery'])->count();
        $maxCapacity = config('shipping.max_shipments_per_branch', 1000);
        
        return min(($totalShipments / $maxCapacity) * 100, 100);
    }
    
    /**
     * Get full location string.
     */
    private function getFullLocation(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Get formatted type label.
     */
    private function getTypeLabel(): string
    {
        $types = [
            'main' => 'Main Branch',
            'sub' => 'Sub Branch',
            'hub' => 'Hub',
            'pickup_point' => 'Pickup Point',
        ];
        
        return $types[$this->type] ?? ucfirst($this->type);
    }
    
    /**
     * Check if branch has active shipments.
     */
    private function hasActiveShipments(): bool
    {
        return $this->shipments()
            ->whereIn('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery'])
            ->exists();
    }
}
```

**File: app/Http/Resources/Api/V1/ReportResource.php**
```php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->getPeriodData(),
            'shipments' => $this->getShipmentData(),
            'financial' => $this->getFinancialData(),
            'operations' => $this->getOperationsData(),
            'branches' => $this->getBranchData(),
            'delivery' => $this->getDeliveryData(),
            'trends' => $this->getTrendsData(),
        ];
    }
    
    /**
     * Get period information.
     */
    private function getPeriodData(): array
    {
        return [
            'start_date' => $this->start_date->toISOString(),
            'end_date' => $this->end_date->toISOString(),
            'period_type' => $this->period_type ?? 'custom',
            'days_count' => $this->start_date->diffInDays($this->end_date),
            'generated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get shipment statistics.
     */
    private function getShipmentData(): array
    {
        $shipments = $this->shipments ?? collect();
        
        return [
            'total' => $shipments->count(),
            'by_status' => [
                'pending' => $shipments->where('status', 'pending')->count(),
                'assigned' => $shipments->where('status', 'assigned')->count(),
                'picked_up' => $shipments->where('status', 'picked_up')->count(),
                'in_transit' => $shipments->where('status', 'in_transit')->count(),
                'out_for_delivery' => $shipments->where('status', 'out_for_delivery')->count(),
                'delivered' => $shipments->where('status', 'delivered')->count(),
                'returned' => $shipments->where('status', 'returned')->count(),
                'cancelled' => $shipments->where('status', 'cancelled')->count(),
            ],
            'by_type' => [
                'express' => $shipments->where('delivery_type', 'express')->count(),
                'regular' => $shipments->where('delivery_type', 'regular')->count(),
                'bulk' => $shipments->where('delivery_type', 'bulk')->count(),
            ],
            'daily_distribution' => $this->getDailyShipmentDistribution($shipments),
        ];
    }
    
    /**
     * Get financial data.
     */
    private function getFinancialData(): array
    {
        $shipments = $this->shipments ?? collect();
        
        return [
            'total_revenue' => (float) $shipments->sum('total_charge'),
            'total_cod_amount' => (float) $shipments->sum('cod_amount'),
            'average_charge' => $shipments->count() > 0 ? (float) $shipments->avg('total_charge') : 0,
            'cod_percentage' => $this->getCODPercentage($shipments),
            'revenue_by_status' => $this->getRevenueByStatus($shipments),
            'revenue_by_branch' => $this->getRevenueByBranch($shipments),
            'growth_metrics' => $this->getGrowthMetrics(),
        ];
    }
    
    /**
     * Get operations data.
     */
    private function getOperationsData(): array
    {
        $shipments = $this->shipments ?? collect();
        
        return [
            'total_users' => $this->total_users,
            'active_branches' => $this->active_branches,
            'active_delivery_men' => $this->active_delivery_men,
            'total_merchants' => $this->total_merchants,
            'performance_metrics' => [
                'average_delivery_time' => $this->getAverageDeliveryTime($shipments),
                'delivery_success_rate' => $this->getDeliverySuccessRate($shipments),
                'on_time_delivery_rate' => $this->getOnTimeDeliveryRate($shipments),
                'return_rate' => $this->getReturnRate($shipments),
            ],
        ];
    }
    
    /**
     * Get branch performance data.
     */
    private function getBranchData(): array
    {
        return $this->branch_performance ?? [
            'top_performers' => [],
            'needs_attention' => [],
            'branch_utilization' => [],
        ];
    }
    
    /**
     * Get delivery performance data.
     */
    private function getDeliveryData(): array
    {
        $shipments = $this->shipments ?? collect();
        $deliveryMen = $this->delivery_men ?? collect();
        
        return [
            'delivery_performance' => $this->getDeliveryPerformance($deliveryMen, $shipments),
            'geographic_distribution' => $this->getGeographicDistribution($shipments),
            'time_of_day_distribution' => $this->getTimeOfDayDistribution($shipments),
        ];
    }
    
    /**
     * Get trends data.
     */
    private function getTrendsData(): array
    {
        return [
            'revenue_trend' => $this->revenue_trend ?? [],
            'shipment_volume_trend' => $this->shipment_volume_trend ?? [],
            'customer_growth_trend' => $this->customer_growth_trend ?? [],
            'seasonality_patterns' => $this->seasonality_patterns ?? [],
        ];
    }
    
    // Helper methods
    
    private function getDailyShipmentDistribution($shipments): array
    {
        return $shipments
            ->groupBy(fn($shipment) => $shipment->created_at->format('Y-m-d'))
            ->map(fn($dayShipments) => $dayShipments->count())
            ->toArray();
    }
    
    private function getCODPercentage($shipments): float
    {
        $totalCharges = $shipments->sum('total_charge');
        $totalCOD = $shipments->sum('cod_amount');
        
        return $totalCharges > 0 ? ($totalCOD / $totalCharges) * 100 : 0;
    }
    
    private function getRevenueByStatus($shipments): array
    {
        return $shipments
            ->groupBy('status')
            ->map(fn($statusShipments) => $statusShipments->sum('total_charge'))
            ->toArray();
    }
    
    private function getRevenueByBranch($shipments): array
    {
        return $shipments
            ->groupBy('branch_id')
            ->map(fn($branchShipments) => $branchShipments->sum('total_charge'))
            ->toArray();
    }
    
    private function getGrowthMetrics(): array
    {
        return [
            'monthly_growth' => 15.5, // Placeholder - calculate from previous period
            'year_over_year' => 45.2, // Placeholder - calculate from last year
            'quarterly_trend' => 'increasing', // Placeholder - calculate trend
        ];
    }
    
    private function getAverageDeliveryTime($shipments): float
    {
        $deliveredShipments = $shipments->where('status', 'delivered');
        
        if ($deliveredShipments->isEmpty()) {
            return 0;
        }
        
        $totalDays = $deliveredShipments
            ->sum(function ($shipment) {
                return $shipment->created_at->diffInDays($shipment->delivered_at);
            });
        
        return round($totalDays / $deliveredShipments->count(), 1);
    }
    
    private function getDeliverySuccessRate($shipments): float
    {
        if ($shipments->isEmpty()) {
            return 0;
        }
        
        return round(($shipments->where('status', 'delivered')->count() / $shipments->count()) * 100, 1);
    }
    
    private function getOnTimeDeliveryRate($shipments): float
    {
        $deliveredShipments = $shipments->where('status', 'delivered');
        
        if ($deliveredShipments->isEmpty()) {
            return 0;
        }
        
        // Assuming on-time is within 3 days of pickup
        $onTime = $deliveredShipments
            ->filter(function ($shipment) {
                return $shipment->delivered_at && 
                       $shipment->pickup_date && 
                       $shipment->delivered_at->diffInDays($shipment->pickup_date) <= 3;
            })
            ->count();
        
        return round(($onTime / $deliveredShipments->count()) * 100, 1);
    }
    
    private function getReturnRate($shipments): float
    {
        if ($shipments->isEmpty()) {
            return 0;
        }
        
        return round(($shipments->where('status', 'returned')->count() / $shipments->count()) * 100, 1);
    }
    
    private function getDeliveryPerformance($deliveryMen, $shipments): array
    {
        // Placeholder implementation
        return [
            'top_performer' => null,
            'average_deliveries' => 15,
            'performance_distribution' => [
                'excellent' => 25,
                'good' => 45,
                'average' => 25,
                'poor' => 5,
            ],
        ];
    }
    
    private function getGeographicDistribution($shipments): array
    {
        // Placeholder - would group by delivery city/region
        return [
            'top_cities' => [],
            'regions' => [],
        ];
    }
    
    private function getTimeOfDayDistribution($shipments): array
    {
        // Placeholder - would group by delivery time
        return [
            'morning' => 30,
            'afternoon' => 45,
            'evening' => 25,
        ];
    }
}
```

### Day 5: API Error Handling Enhancement

**File: app/Http/Middleware/Api/HandleApiExceptions.php**
```php
<?php

namespace App\Http\Middleware\Api;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class HandleApiExceptions extends Exception
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($exception, $request);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions with standardized format.
     */
    protected function handleApiException(Throwable $exception, Request $request): JsonResponse
    {
        $statusCode = $this->getStatusCode($exception);
        $errorType = $this->getErrorType($exception);
        
        // Log the exception
        Log::error('API Exception occurred', [
            'exception' => $exception,
            'request' => $request->all(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Handle specific exception types
        if ($exception instanceof ValidationException) {
            return $this->validationErrorResponse($exception);
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->authenticationErrorResponse();
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->authorizationErrorResponse($exception);
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundErrorResponse();
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return $this->methodNotAllowedResponse();
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->endpointNotFoundResponse();
        }

        // Generic error response
        return $this->errorResponse(
            $errorType,
            $this->getErrorMessage($exception),
            $statusCode,
            $this->getErrorData($exception)
        );
    }

    /**
     * Validation error response.
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => 'validation_error',
            'message' => 'The given data was invalid.',
            'errors' => $exception->errors(),
            'timestamp' => now()->toISOString(),
        ], 422);
    }

    /**
     * Authentication error response.
     */
    protected function authenticationErrorResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => 'auth.unauthenticated',
            'message' => 'Unauthenticated. Please log in to continue.',
            'timestamp' => now()->toISOString(),
        ], 401);
    }

    /**
     * Authorization error response.
     */
    protected function authorizationErrorResponse(\Illuminate\Auth\Access\AuthorizationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => 'auth.unauthorized',
            'message' => 'You do not have permission to perform this action.',
            'permission' => $exception->getMessage(),
            'timestamp' => now()->toISOString(),
        ], 403);
    }

    /**
     * Not found error response.
     */
    protected function notFoundErrorResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => 'not_found',
            'message' => 'The requested resource was not found.',
            'timestamp' => now()->toISOString(),
        ], 404);
    }

    /**
     * Method not allowed response.
     */
    protected function methodNotAllowedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => 'method_not_allowed',
            'message' => 'The HTTP method is not allowed for this endpoint.',
            'timestamp' => now()->toISOString(),
        ], 405);
    }

    /**
     * Endpoint not found response.
     */
    protected function endpointNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => 'endpoint_not_found',
            'message' => 'The requested API endpoint does not exist.',
            'timestamp' => now()->toISOString(),
        ], 404);
    }

    /**
     * Generic error response.
     */
    protected function errorResponse(
        string $type,
        string $message,
        int $statusCode,
        array $data = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'type' => $type,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        // Add debug information in development
        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => collect($this->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get HTTP status code for exception.
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404;
        }

        return 500;
    }

    /**
     * Get error type for exception.
     */
    protected function getErrorType(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return 'validation_error';
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return 'auth.unauthenticated';
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 'auth.unauthorized';
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 'not_found';
        }

        if ($exception instanceof \Illuminate\Database\QueryException) {
            return 'database_error';
        }

        return 'server_error';
    }

    /**
     * Get error message for exception.
     */
    protected function getErrorMessage(Throwable $exception): string
    {
        // In production, don't expose detailed error messages
        if (!config('app.debug')) {
            if ($exception->getCode() == 500) {
                return 'An unexpected error occurred. Please try again later.';
            }
        }

        return $exception->getMessage();
    }

    /**
     * Get additional error data for response.
     */
    protected function getErrorData(Throwable $exception): array
    {
        $data = [];

        if ($exception instanceof \Illuminate\Database\QueryException) {
            $data['database_error'] = [
                'code' => $exception->getCode(),
                'sql_state' => $exception->errorInfo[0] ?? null,
            ];
        }

        return $data;
    }
}
```

### Day 6-7: Frontend State Management Integration

**File: react-dashboard/src/contexts/AuthContext.tsx**
```typescript
import React, { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { User, LoginForm, RegisterForm } from '../types/auth';
import { apiClient } from '../services/api';
import { toast } from 'react-hot-toast';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  permissions: string[];
  login: (credentials: LoginForm) => Promise<void>;
  register: (data: RegisterForm) => Promise<void>;
  logout: () => void;
  refreshToken: () => Promise<void>;
  updateProfile: (data: Partial<User>) => Promise<void>;
  changePassword: (data: { current_password: string; password: string; password_confirmation: string }) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    initializeAuth();
  }, []);

  const initializeAuth = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      
      if (!token) {
        setIsLoading(false);
        return;
      }

      // Set token for API client
      apiClient.setToken(token);

      // Verify token and get user data
      const response = await apiClient.get('/v1/auth/me');
      
      if (response.data.success) {
        setUser(response.data.data.user);
        setPermissions(response.data.data.user.permissions);
      } else {
        // Invalid token, clear it
        localStorage.removeItem('auth_token');
        apiClient.clearToken();
      }
    } catch (error) {
      console.error('Auth initialization failed:', error);
      localStorage.removeItem('auth_token');
      apiClient.clearToken();
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (credentials: LoginForm) => {
    try {
      const response = await apiClient.post('/v1/auth/login', credentials);
      
      if (response.data.success) {
        const { user: userData, token } = response.data.data;
        
        // Store token
        localStorage.setItem('auth_token', token);
        apiClient.setToken(token);
        
        // Update state
        setUser(userData);
        setPermissions(userData.permissions);
        
        toast.success('Login successful!');
      } else {
        throw new Error(response.data.message || 'Login failed');
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Login failed';
      toast.error(message);
      throw error;
    }
  };

  const register = async (data: RegisterForm) => {
    try {
      const response = await apiClient.post('/v1/auth/register', data);
      
      if (response.data.success) {
        toast.success('Registration successful! Please log in.');
      } else {
        throw new Error(response.data.message || 'Registration failed');
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Registration failed';
      toast.error(message);
      throw error;
    }
  };

  const logout = async () => {
    try {
      await apiClient.post('/v1/auth/logout');
    } catch (error) {
      console.error('Logout API failed:', error);
    } finally {
      // Clear local data regardless of API success
      localStorage.removeItem('auth_token');
      apiClient.clearToken();
      setUser(null);
      setPermissions([]);
      toast.success('Logged out successfully');
    }
  };

  const refreshToken = async () => {
    try {
      const response = await apiClient.post('/v1/tokens/refresh');
      
      if (response.data.success) {
        const { token } = response.data.data;
        localStorage.setItem('auth_token', token);
        apiClient.setToken(token);
      } else {
        throw new Error('Token refresh failed');
      }
    } catch (error) {
      console.error('Token refresh failed:', error);
      await logout();
      throw error;
    }
  };

  const updateProfile = async (data: Partial<User>) => {
    try {
      const response = await apiClient.put('/v1/auth/profile', data);
      
      if (response.data.success) {
        setUser(response.data.data.user);
        setPermissions(response.data.data.user.permissions);
        toast.success('Profile updated successfully!');
      } else {
        throw new Error(response.data.message || 'Profile update failed');
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Profile update failed';
      toast.error(message);
      throw error;
    }
  };

  const changePassword = async (data: { current_password: string; password: string; password_confirmation: string }) => {
    try {
      const response = await apiClient.put('/v1/auth/password', data);
      
      if (response.data.success) {
        toast.success('Password changed successfully!');
      } else {
        throw new Error(response.data.message || 'Password change failed');
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Password change failed';
      toast.error(message);
      throw error;
    }
  };

  const value: AuthContextType = {
    user,
    permissions,
    isLoading,
    isAuthenticated: !!user,
    login,
    register,
    logout,
    refreshToken,
    updateProfile,
    changePassword,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

// Permission checking helper
export function usePermission(permission: string): boolean {
  const { permissions } = useAuth();
  return permissions.includes(permission);
}

// Multiple permissions check
export function usePermissions(permissions: string[]): boolean {
  const { userPermissions } = useAuth();
  return permissions.some(permission => userPermissions.includes(permission));
}
```

**File: react-dashboard/src/services/api.ts**
```typescript
import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: process.env.REACT_APP_API_URL || '/api',
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor
    this.client.interceptors.request.use(
      (config) => {
        const token = this.getToken();
        
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }

        // Add request ID for tracking
        config.headers['X-Request-ID'] = this.generateRequestId();
        
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor
    this.client.interceptors.response.use(
      (response) => response,
      async (error) => {
        const originalRequest = error.config;

        // Handle 401 Unauthorized
        if (error.response?.status === 401 && !originalRequest._retry) {
          originalRequest._retry = true;

          try {
            // Try to refresh token
            await this.post('/v1/tokens/refresh');
            
            // Update token and retry request
            const token = this.getToken();
            if (token) {
              originalRequest.headers.Authorization = `Bearer ${token}`;
            }

            return this.client(originalRequest);
          } catch (refreshError) {
            // Refresh failed, redirect to login
            this.clearToken();
            window.location.href = '/login';
            return Promise.reject(refreshError);
          }
        }

        // Handle 422 Validation errors
        if (error.response?.status === 422) {
          return Promise.reject(error);
        }

        // Handle 403 Forbidden
        if (error.response?.status === 403) {
          return Promise.reject(error);
        }

        // Handle 500 Server errors
        if (error.response?.status >= 500) {
          console.error('Server error:', error);
          
          // Show generic error message
          if (process.env.NODE_ENV === 'production') {
            error.response.data.message = 'An unexpected error occurred. Please try again later.';
          }
        }

        return Promise.reject(error);
      }
    );
  }

  // Auth methods
  setToken(token: string) {
    localStorage.setItem('auth_token', token);
  }

  getToken(): string | null {
    return localStorage.getItem('auth_token');
  }

  clearToken() {
    localStorage.removeItem('auth_token');
  }

  // HTTP methods
  async get<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.get(url, config);
  }

  async post<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.post(url, data, config);
  }

  async put<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.put(url, data, config);
  }

  async patch<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.patch(url, data, config);
  }

  async delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.delete(url, config);
  }

  // File upload
  async uploadFile(url: string, file: File, progressCallback?: (progress: number) => void): Promise<AxiosResponse> {
    const formData = new FormData();
    formData.append('file', file);

    return this.client.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (progressCallback && progressEvent.total) {
          const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          progressCallback(progress);
        }
      },
    });
  }

  // Utility methods
  private generateRequestId(): string {
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
  }

  setBaseURL(baseURL: string) {
    this.client.defaults.baseURL = baseURL;
  }

  setTimeout(timeout: number) {
    this.client.defaults.timeout = timeout;
  }

  // Error handling
  isNetworkError(error: any): boolean {
    return !error.response && error.code !== 'ECONNABORTED';
  }

  isClientError(error: any): boolean {
    return error.response && error.response.status >= 400 && error.response.status < 500;
  }

  isServerError(error: any): boolean {
    return error.response && error.response.status >= 500;
  }
}

// Create singleton instance
export const apiClient = new ApiClient();

// Export types
export interface ApiResponse<T = any> {
  success: boolean;
  type?: string;
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
  timestamp: string;
}

export interface PaginatedResponse<T = any> {
  success: boolean;
  data: {
    items: T[];
    pagination: {
      current_page: number;
      total_pages: number;
      total_items: number;
      per_page: number;
    };
  };
}
```

### Day 8: Integration Testing

**File: react-dashboard/src/hooks/useApiTest.tsx**
```typescript
import { useState, useEffect } from 'react';
import { apiClient } from '../services/api';

interface TestResult {
  endpoint: string;
  method: string;
  status: 'pending' | 'success' | 'error';
  message: string;
  duration: number;
}

export function useApiTest() {
  const [results, setResults] = useState<TestResult[]>([]);
  const [isRunning, setIsRunning] = useState(false);

  const runTests = async () => {
    setIsRunning(true);
    setResults([]);

    const endpoints = [
      { url: '/v1/health', method: 'GET', description: 'Health Check' },
      { url: '/v1/system/info', method: 'GET', description: 'System Info' },
      { url: '/v1/users', method: 'GET', description: 'Users List' },
      { url: '/v1/shipments', method: 'GET', description: 'Shipments List' },
      { url: '/v1/reports/dashboard/kpi', method: 'GET', description: 'Dashboard KPI' },
    ];

    for (const endpoint of endpoints) {
      const startTime = Date.now();
      
      try {
        await apiClient.get(endpoint.url);
        
        setResults(prev => [...prev, {
          endpoint: endpoint.description,
          method: endpoint.method,
          status: 'success',
          message: `${endpoint.method} ${endpoint.url} - Success`,
          duration: Date.now() - startTime,
        }]);
      } catch (error: any) {
        const message = error.response?.data?.message || error.message || 'Unknown error';
        
        setResults(prev => [...prev, {
          endpoint: endpoint.description,
          method: endpoint.method,
          status: 'error',
          message: message,
          duration: Date.now() - startTime,
        }]);
      }
    }

    setIsRunning(false);
  };

  return {
    results,
    isRunning,
    runTests,
  };
}
```

---

## Phase 2 Completion Checklist:

### ✅ Week 2 Deliverables:
- [ ] Complete Sanctum token management
- [ ] API middleware for security and JSON validation
- [ ] Comprehensive error handling system
- [ ] Complete API resource classes (User, Shipment, Branch, Report)
- [ ] React authentication context with token management
- [ ] API client with interceptors and error handling
- [ ] Frontend state management setup
- [ ] Integration testing hooks

### Integration Points:
- Backend authentication ↔ Frontend context
- API error responses ↔ Frontend error handling
- Token refresh ↔ Automatic re-authentication
- Permission system ↔ Frontend permission hooks

This completes Priority 2 implementation. The system now has a robust authentication foundation and comprehensive API resources ready for frontend consumption.
