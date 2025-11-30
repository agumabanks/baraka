<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * EnhancedRouteOptimizationService
 * 
 * Advanced route optimization with:
 * - 2-opt local search improvement
 * - 3-opt local search for complex routes
 * - Simulated Annealing for large-scale optimization
 * - Traffic API integration (Google Maps)
 * - Dynamic re-routing capabilities
 * - Time window constraints
 * - Multi-vehicle route planning
 */
class EnhancedRouteOptimizationService extends RouteOptimizationService
{
    protected const SIMULATED_ANNEALING_INITIAL_TEMP = 10000;
    protected const SIMULATED_ANNEALING_COOLING_RATE = 0.9995;
    protected const SIMULATED_ANNEALING_MIN_TEMP = 1;

    /**
     * Enhanced optimization with local search improvements
     */
    public function optimizeRouteEnhanced(
        array $shipmentIds,
        ?int $vehicleId = null,
        ?int $driverId = null,
        array $options = []
    ): array {
        $shipments = Shipment::whereIn('id', $shipmentIds)->get();
        
        if ($shipments->isEmpty()) {
            return ['success' => false, 'message' => 'No shipments found'];
        }

        $vehicleConstraints = $vehicleId ? $this->getVehicleConstraints($vehicleId) : null;
        $driverConstraints = $driverId ? $this->getDriverConstraints($driverId) : null;

        $strategy = $options['strategy'] ?? 'auto';
        $shipmentCount = $shipments->count();

        // Auto-select strategy based on problem size
        if ($strategy === 'auto') {
            $strategy = match (true) {
                $shipmentCount <= 15 => '2opt',
                $shipmentCount <= 50 => '3opt',
                $shipmentCount <= 100 => 'genetic_2opt',
                default => 'simulated_annealing',
            };
        }

        $optimizedSequence = match ($strategy) {
            '2opt' => $this->twoOptOptimization($shipments, $vehicleConstraints),
            '3opt' => $this->threeOptOptimization($shipments, $vehicleConstraints),
            'genetic_2opt' => $this->geneticWith2Opt($shipments, $vehicleConstraints),
            'simulated_annealing' => $this->simulatedAnnealing($shipments, $vehicleConstraints),
            default => $this->nearestNeighborOptimization($shipments, $vehicleConstraints),
        };

        // Apply traffic adjustments if API key available
        if (config('services.google_maps.api_key') && ($options['use_traffic'] ?? true)) {
            $optimizedSequence = $this->applyTrafficAdjustments($optimizedSequence);
        }

        $metrics = $this->calculateRouteMetrics($optimizedSequence, $vehicleConstraints);
        $violations = $this->checkConstraintViolations($optimizedSequence, $vehicleConstraints, $driverConstraints);
        $improvement = $this->calculateImprovement($shipments, $optimizedSequence);

        return [
            'success' => empty($violations),
            'optimized_sequence' => $optimizedSequence,
            'metrics' => $metrics,
            'violations' => $violations,
            'strategy_used' => $strategy,
            'improvement' => $improvement,
            'optimization_time_ms' => round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2),
        ];
    }

    /**
     * 2-opt local search improvement
     * Reverses segments to reduce total distance
     */
    public function twoOptOptimization(Collection $shipments, ?array $vehicleConstraints): array
    {
        // Start with nearest neighbor solution
        $route = $this->nearestNeighborOptimization($shipments, $vehicleConstraints);
        
        if (count($route) < 4) {
            return $route;
        }

        $improved = true;
        $iterations = 0;
        $maxIterations = 1000;

        while ($improved && $iterations < $maxIterations) {
            $improved = false;
            $iterations++;

            for ($i = 0; $i < count($route) - 2; $i++) {
                for ($j = $i + 2; $j < count($route); $j++) {
                    // Calculate current distance
                    $currentDistance = $this->segmentDistance($route, $i, $j);
                    
                    // Try reversing segment
                    $newRoute = $this->reverseSegment($route, $i + 1, $j);
                    $newDistance = $this->segmentDistance($newRoute, $i, $j);

                    if ($newDistance < $currentDistance) {
                        $route = $newRoute;
                        $improved = true;
                        break 2;
                    }
                }
            }
        }

        return $this->renumberSequence($route);
    }

    /**
     * 3-opt local search improvement
     * More powerful but computationally expensive
     */
    public function threeOptOptimization(Collection $shipments, ?array $vehicleConstraints): array
    {
        // Start with 2-opt solution
        $route = $this->twoOptOptimization($shipments, $vehicleConstraints);
        
        if (count($route) < 6) {
            return $route;
        }

        $improved = true;
        $iterations = 0;
        $maxIterations = 500;
        $n = count($route);

        while ($improved && $iterations < $maxIterations) {
            $improved = false;
            $iterations++;

            for ($i = 0; $i < $n - 4; $i++) {
                for ($j = $i + 2; $j < $n - 2; $j++) {
                    for ($k = $j + 2; $k < $n; $k++) {
                        $bestMove = $this->find3OptBestMove($route, $i, $j, $k);
                        
                        if ($bestMove !== null) {
                            $route = $this->apply3OptMove($route, $i, $j, $k, $bestMove);
                            $improved = true;
                            break 3;
                        }
                    }
                }
            }
        }

        return $this->renumberSequence($route);
    }

    /**
     * Find best 3-opt move
     */
    protected function find3OptBestMove(array $route, int $i, int $j, int $k): ?int
    {
        $n = count($route);
        
        // Current distance
        $d0 = $this->edgeDistance($route, $i, $i + 1) +
              $this->edgeDistance($route, $j, $j + 1) +
              $this->edgeDistance($route, $k, ($k + 1) % $n);

        // Try all 3-opt reconnections
        $moves = [
            // d1: reverse segment [i+1, j]
            $this->calculate3OptDistance($route, $i, $j, $k, 1),
            // d2: reverse segment [j+1, k]
            $this->calculate3OptDistance($route, $i, $j, $k, 2),
            // d3: reverse both segments
            $this->calculate3OptDistance($route, $i, $j, $k, 3),
            // d4-d7: other reconnection patterns
            $this->calculate3OptDistance($route, $i, $j, $k, 4),
        ];

        $bestImprovement = 0;
        $bestMove = null;

        foreach ($moves as $moveId => $distance) {
            $improvement = $d0 - $distance;
            if ($improvement > $bestImprovement) {
                $bestImprovement = $improvement;
                $bestMove = $moveId + 1;
            }
        }

        return $bestMove;
    }

    /**
     * Calculate 3-opt move distance
     */
    protected function calculate3OptDistance(array $route, int $i, int $j, int $k, int $moveType): float
    {
        $n = count($route);
        
        return match ($moveType) {
            1 => $this->edgeDistance($route, $i, $j) +
                 $this->edgeDistance($route, $i + 1, $j + 1) +
                 $this->edgeDistance($route, $k, ($k + 1) % $n),
            2 => $this->edgeDistance($route, $i, $i + 1) +
                 $this->edgeDistance($route, $j, $k) +
                 $this->edgeDistance($route, $j + 1, ($k + 1) % $n),
            3 => $this->edgeDistance($route, $i, $j) +
                 $this->edgeDistance($route, $i + 1, $k) +
                 $this->edgeDistance($route, $j + 1, ($k + 1) % $n),
            default => $this->edgeDistance($route, $i, $j + 1) +
                      $this->edgeDistance($route, $k, $i + 1) +
                      $this->edgeDistance($route, $j, ($k + 1) % $n),
        };
    }

    /**
     * Apply 3-opt move to route
     */
    protected function apply3OptMove(array $route, int $i, int $j, int $k, int $moveType): array
    {
        $segment1 = array_slice($route, 0, $i + 1);
        $segment2 = array_slice($route, $i + 1, $j - $i);
        $segment3 = array_slice($route, $j + 1, $k - $j);
        $segment4 = array_slice($route, $k + 1);

        return match ($moveType) {
            1 => array_merge($segment1, array_reverse($segment2), $segment3, $segment4),
            2 => array_merge($segment1, $segment2, array_reverse($segment3), $segment4),
            3 => array_merge($segment1, array_reverse($segment2), array_reverse($segment3), $segment4),
            default => array_merge($segment1, $segment3, $segment2, $segment4),
        };
    }

    /**
     * Simulated Annealing for large-scale optimization
     */
    public function simulatedAnnealing(Collection $shipments, ?array $vehicleConstraints): array
    {
        // Start with nearest neighbor solution
        $currentRoute = $this->nearestNeighborOptimization($shipments, $vehicleConstraints);
        $bestRoute = $currentRoute;
        $currentCost = $this->calculateTotalDistance($currentRoute);
        $bestCost = $currentCost;

        $temperature = self::SIMULATED_ANNEALING_INITIAL_TEMP;
        $coolingRate = self::SIMULATED_ANNEALING_COOLING_RATE;

        $iterations = 0;
        $maxIterations = 10000;

        while ($temperature > self::SIMULATED_ANNEALING_MIN_TEMP && $iterations < $maxIterations) {
            $iterations++;

            // Generate neighbor solution (random 2-opt move)
            $neighborRoute = $this->generateNeighbor($currentRoute);
            $neighborCost = $this->calculateTotalDistance($neighborRoute);

            $delta = $neighborCost - $currentCost;

            // Accept if better, or with probability based on temperature
            if ($delta < 0 || exp(-$delta / $temperature) > mt_rand() / mt_getrandmax()) {
                $currentRoute = $neighborRoute;
                $currentCost = $neighborCost;

                if ($currentCost < $bestCost) {
                    $bestRoute = $currentRoute;
                    $bestCost = $currentCost;
                }
            }

            $temperature *= $coolingRate;
        }

        return $this->renumberSequence($bestRoute);
    }

    /**
     * Generate neighbor solution for simulated annealing
     */
    protected function generateNeighbor(array $route): array
    {
        $n = count($route);
        
        if ($n < 4) {
            return $route;
        }

        // Random 2-opt move
        $i = rand(0, $n - 3);
        $j = rand($i + 2, $n - 1);

        return $this->reverseSegment($route, $i + 1, $j);
    }

    /**
     * Genetic Algorithm enhanced with 2-opt
     */
    public function geneticWith2Opt(Collection $shipments, ?array $vehicleConstraints): array
    {
        // Run genetic algorithm
        $geneticRoute = $this->geneticAlgorithm($shipments, $vehicleConstraints);
        
        // Convert to collection and improve with 2-opt
        $tempCollection = collect(array_map(
            fn($stop) => Shipment::find($stop['shipment_id']),
            $geneticRoute
        ))->filter();

        return $this->twoOptOptimization($tempCollection, $vehicleConstraints);
    }

    /**
     * Apply traffic adjustments using Google Maps API
     */
    protected function applyTrafficAdjustments(array $route): array
    {
        $apiKey = config('services.google_maps.api_key');
        
        if (!$apiKey || count($route) < 2) {
            return $route;
        }

        // Build waypoints string
        $waypoints = [];
        foreach ($route as $stop) {
            $waypoints[] = "{$stop['latitude']},{$stop['longitude']}";
        }

        // Get traffic data from Google
        $cacheKey = 'traffic_' . md5(implode('|', $waypoints));
        
        $trafficData = Cache::remember($cacheKey, 300, function () use ($waypoints, $apiKey) {
            try {
                $origin = array_shift($waypoints);
                $destination = array_pop($waypoints);
                
                $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin' => $origin,
                    'destination' => $destination,
                    'waypoints' => implode('|', $waypoints),
                    'departure_time' => 'now',
                    'traffic_model' => 'best_guess',
                    'key' => $apiKey,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get traffic data', ['error' => $e->getMessage()]);
            }

            return null;
        });

        if (!$trafficData || !isset($trafficData['routes'][0]['legs'])) {
            return $route;
        }

        // Apply traffic-adjusted durations
        $legs = $trafficData['routes'][0]['legs'];
        
        foreach ($route as $idx => &$stop) {
            if ($idx === 0) {
                continue;
            }

            $legIndex = $idx - 1;
            
            if (isset($legs[$legIndex])) {
                $leg = $legs[$legIndex];
                $durationInTraffic = $leg['duration_in_traffic']['value'] ?? $leg['duration']['value'];
                
                $stop['traffic_adjusted_time'] = ceil($durationInTraffic / 60);
                $stop['traffic_delay'] = max(0, $stop['traffic_adjusted_time'] - ($stop['estimated_travel_time'] ?? 0));
                
                // Update estimated arrival
                $prevStop = $route[$idx - 1];
                $stop['estimated_arrival'] = Carbon::parse($prevStop['estimated_arrival'])
                    ->addMinutes($stop['traffic_adjusted_time'] + 5); // +5 for stop time
            }
        }

        return $route;
    }

    /**
     * Dynamic re-routing when circumstances change
     */
    public function dynamicReroute(
        array $currentRoute,
        array $changes,
        ?array $vehicleConstraints = null
    ): array {
        $modifications = [];

        // Handle different types of changes
        if (isset($changes['add_shipments'])) {
            $currentRoute = $this->insertShipments($currentRoute, $changes['add_shipments']);
            $modifications[] = 'added_shipments';
        }

        if (isset($changes['remove_shipments'])) {
            $currentRoute = $this->removeShipments($currentRoute, $changes['remove_shipments']);
            $modifications[] = 'removed_shipments';
        }

        if (isset($changes['traffic_delay'])) {
            $currentRoute = $this->adjustForDelay($currentRoute, $changes['traffic_delay']);
            $modifications[] = 'traffic_adjusted';
        }

        if (isset($changes['vehicle_breakdown'])) {
            return $this->handleVehicleBreakdown($currentRoute, $changes['vehicle_breakdown']);
        }

        // Re-optimize the modified route
        $shipmentIds = array_column($currentRoute, 'shipment_id');
        $shipments = Shipment::whereIn('id', $shipmentIds)->get();

        // Quick 2-opt improvement
        $optimizedRoute = $this->twoOptOptimization($shipments, $vehicleConstraints);

        return [
            'success' => true,
            'route' => $optimizedRoute,
            'modifications' => $modifications,
            'metrics' => $this->calculateRouteMetrics($optimizedRoute, $vehicleConstraints),
        ];
    }

    /**
     * Insert new shipments into existing route
     */
    protected function insertShipments(array $route, array $newShipmentIds): array
    {
        $newShipments = Shipment::whereIn('id', $newShipmentIds)->get();

        foreach ($newShipments as $shipment) {
            // Find best insertion point (minimum distance increase)
            $bestPosition = 0;
            $minIncrease = PHP_FLOAT_MAX;

            $newStop = [
                'shipment_id' => $shipment->id,
                'latitude' => $shipment->delivery_latitude ?? $shipment->receiver_lat ?? 0,
                'longitude' => $shipment->delivery_longitude ?? $shipment->receiver_long ?? 0,
            ];

            for ($i = 0; $i <= count($route); $i++) {
                $increase = $this->calculateInsertionCost($route, $newStop, $i);
                
                if ($increase < $minIncrease) {
                    $minIncrease = $increase;
                    $bestPosition = $i;
                }
            }

            // Insert at best position
            array_splice($route, $bestPosition, 0, [$newStop]);
        }

        return $this->renumberSequence($route);
    }

    /**
     * Calculate cost of inserting a stop at a position
     */
    protected function calculateInsertionCost(array $route, array $newStop, int $position): float
    {
        if (empty($route)) {
            return 0;
        }

        $cost = 0;

        // Cost of edge to new stop
        if ($position > 0) {
            $prev = $route[$position - 1];
            $cost += $this->calculateDistance(
                $prev['latitude'],
                $prev['longitude'],
                $newStop['latitude'],
                $newStop['longitude']
            );
        }

        // Cost of edge from new stop
        if ($position < count($route)) {
            $next = $route[$position];
            $cost += $this->calculateDistance(
                $newStop['latitude'],
                $newStop['longitude'],
                $next['latitude'],
                $next['longitude']
            );
        }

        // Subtract removed edge (if inserting between two existing stops)
        if ($position > 0 && $position < count($route)) {
            $cost -= $this->calculateDistance(
                $route[$position - 1]['latitude'],
                $route[$position - 1]['longitude'],
                $route[$position]['latitude'],
                $route[$position]['longitude']
            );
        }

        return $cost;
    }

    /**
     * Remove shipments from route
     */
    protected function removeShipments(array $route, array $shipmentIds): array
    {
        return array_values(array_filter($route, function ($stop) use ($shipmentIds) {
            return !in_array($stop['shipment_id'], $shipmentIds);
        }));
    }

    /**
     * Adjust route for traffic delay
     */
    protected function adjustForDelay(array $route, array $delayInfo): array
    {
        $delayMinutes = $delayInfo['minutes'] ?? 0;
        $fromIndex = $delayInfo['from_index'] ?? 0;

        foreach ($route as $idx => &$stop) {
            if ($idx > $fromIndex && isset($stop['estimated_arrival'])) {
                $stop['estimated_arrival'] = Carbon::parse($stop['estimated_arrival'])
                    ->addMinutes($delayMinutes);
                $stop['delay_adjusted'] = true;
            }
        }

        return $route;
    }

    /**
     * Handle vehicle breakdown - suggest alternative
     */
    protected function handleVehicleBreakdown(array $route, array $breakdownInfo): array
    {
        $breakdownIndex = $breakdownInfo['at_stop_index'] ?? 0;
        
        // Split route: completed stops vs remaining
        $completedStops = array_slice($route, 0, $breakdownIndex);
        $remainingStops = array_slice($route, $breakdownIndex);

        return [
            'success' => false,
            'requires_reassignment' => true,
            'completed_stops' => $completedStops,
            'remaining_stops' => $remainingStops,
            'remaining_shipment_ids' => array_column($remainingStops, 'shipment_id'),
            'message' => 'Vehicle breakdown - remaining shipments need reassignment',
        ];
    }

    /**
     * Helper: Reverse a segment of the route
     */
    protected function reverseSegment(array $route, int $start, int $end): array
    {
        $reversed = array_reverse(array_slice($route, $start, $end - $start + 1));
        
        return array_merge(
            array_slice($route, 0, $start),
            $reversed,
            array_slice($route, $end + 1)
        );
    }

    /**
     * Helper: Calculate segment distance
     */
    protected function segmentDistance(array $route, int $i, int $j): float
    {
        $distance = 0;
        
        for ($k = $i; $k < $j; $k++) {
            $distance += $this->edgeDistance($route, $k, $k + 1);
        }

        return $distance;
    }

    /**
     * Helper: Calculate edge distance between two stops
     */
    protected function edgeDistance(array $route, int $i, int $j): float
    {
        if (!isset($route[$i]) || !isset($route[$j])) {
            return 0;
        }

        return $this->calculateDistance(
            $route[$i]['latitude'] ?? 0,
            $route[$i]['longitude'] ?? 0,
            $route[$j]['latitude'] ?? 0,
            $route[$j]['longitude'] ?? 0
        );
    }

    /**
     * Helper: Calculate total route distance
     */
    protected function calculateTotalDistance(array $route): float
    {
        $total = 0;
        
        for ($i = 1; $i < count($route); $i++) {
            $total += $this->edgeDistance($route, $i - 1, $i);
        }

        return $total;
    }

    /**
     * Helper: Renumber sequence in route
     */
    protected function renumberSequence(array $route): array
    {
        $sequence = 1;
        
        foreach ($route as &$stop) {
            $stop['sequence'] = $sequence++;
        }

        // Recalculate distances and times
        for ($i = 1; $i < count($route); $i++) {
            $route[$i]['distance_from_prev'] = $this->edgeDistance($route, $i - 1, $i);
            
            if (isset($route[$i - 1]['estimated_arrival'])) {
                $travelTime = $this->calculateTravelTime(
                    $route[$i - 1]['latitude'] ?? 0,
                    $route[$i - 1]['longitude'] ?? 0,
                    $route[$i]['latitude'] ?? 0,
                    $route[$i]['longitude'] ?? 0
                );
                
                $route[$i]['estimated_arrival'] = Carbon::parse($route[$i - 1]['estimated_arrival'])
                    ->addMinutes($travelTime + 5);
            }
        }

        return $route;
    }
}
