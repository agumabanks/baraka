<?php

namespace App\Services;

use App\Models\HubRoute;
use App\Models\Shipment;
use App\Models\Backend\Hub;
use App\Models\Backend\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * HubRoutingService
 * 
 * Handles inter-hub/branch routing logic including:
 * - Cheapest/fastest path calculation (Dijkstra's algorithm)
 * - Cost-based routing decisions
 * - Hub capacity load balancing
 * - Multi-leg route planning
 */
class HubRoutingService
{
    protected const CACHE_TTL = 3600; // 1 hour
    protected const MAX_HOPS = 5; // Maximum intermediate hubs

    /**
     * Find optimal route between two branches/hubs
     */
    public function findOptimalRoute(
        int $originId,
        int $destinationId,
        string $serviceLevel = 'standard',
        array $options = []
    ): array {
        $cacheKey = "hub_route_{$originId}_{$destinationId}_{$serviceLevel}";
        
        // Check cache first (unless real-time required)
        if (!($options['real_time'] ?? false)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        // Check for direct route first
        $directRoute = $this->findDirectRoute($originId, $destinationId, $serviceLevel);
        
        if ($directRoute) {
            $result = [
                'success' => true,
                'route_type' => 'direct',
                'legs' => [$directRoute],
                'total_distance_km' => $directRoute->distance_km,
                'total_transit_hours' => $directRoute->getAdjustedTransitTime(),
                'total_cost' => $this->calculateRouteCost($directRoute, $options['weight_kg'] ?? 1, $options['volume_cbm'] ?? 0.01),
                'next_departure' => $directRoute->getNextDeparture(),
            ];
            
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        }

        // Find multi-hop route using Dijkstra
        $multiHopRoute = $this->findMultiHopRoute($originId, $destinationId, $serviceLevel, $options);
        
        if ($multiHopRoute) {
            Cache::put($cacheKey, $multiHopRoute, self::CACHE_TTL);
            return $multiHopRoute;
        }

        return [
            'success' => false,
            'message' => 'No route found between the specified locations',
            'origin_id' => $originId,
            'destination_id' => $destinationId,
        ];
    }

    /**
     * Find direct route between two hubs
     */
    protected function findDirectRoute(int $originId, int $destinationId, string $serviceLevel): ?HubRoute
    {
        return HubRoute::active()
            ->where('origin_hub_id', $originId)
            ->where('destination_hub_id', $destinationId)
            ->where('service_level', $serviceLevel)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Find multi-hop route using Dijkstra's algorithm
     */
    protected function findMultiHopRoute(
        int $originId,
        int $destinationId,
        string $serviceLevel,
        array $options = []
    ): ?array {
        $optimizeFor = $options['optimize_for'] ?? 'cost'; // cost, time, distance
        
        // Build graph from hub routes
        $graph = $this->buildRoutingGraph($serviceLevel);
        
        if (empty($graph)) {
            return null;
        }

        // Run Dijkstra's algorithm
        $path = $this->dijkstra($graph, $originId, $destinationId, $optimizeFor);
        
        if (empty($path)) {
            return null;
        }

        // Build route legs
        $legs = $this->buildRouteLegs($path, $serviceLevel);
        
        if (empty($legs)) {
            return null;
        }

        // Calculate totals
        $totalDistance = array_sum(array_column($legs, 'distance_km'));
        $totalTransitHours = array_sum(array_map(fn($leg) => $leg['transit_hours'], $legs));
        $totalCost = array_sum(array_map(
            fn($leg) => $this->calculateRouteCost(
                HubRoute::find($leg['route_id']),
                $options['weight_kg'] ?? 1,
                $options['volume_cbm'] ?? 0.01
            ),
            $legs
        ));

        return [
            'success' => true,
            'route_type' => 'multi_hop',
            'legs' => $legs,
            'total_distance_km' => round($totalDistance, 2),
            'total_transit_hours' => $totalTransitHours,
            'total_cost' => round($totalCost, 2),
            'hop_count' => count($legs),
            'path' => $path,
        ];
    }

    /**
     * Build routing graph from hub routes
     */
    protected function buildRoutingGraph(string $serviceLevel): array
    {
        $routes = HubRoute::active()
            ->where('service_level', $serviceLevel)
            ->get();

        $graph = [];

        foreach ($routes as $route) {
            if (!isset($graph[$route->origin_hub_id])) {
                $graph[$route->origin_hub_id] = [];
            }

            $graph[$route->origin_hub_id][$route->destination_hub_id] = [
                'route_id' => $route->id,
                'distance' => $route->distance_km,
                'time' => $route->getAdjustedTransitTime(),
                'cost' => $route->base_cost,
            ];
        }

        return $graph;
    }

    /**
     * Dijkstra's algorithm implementation
     */
    protected function dijkstra(array $graph, int $start, int $end, string $optimizeFor): array
    {
        $distances = [];
        $previous = [];
        $queue = [];

        // Initialize
        foreach (array_keys($graph) as $node) {
            $distances[$node] = PHP_INT_MAX;
            $previous[$node] = null;
            $queue[$node] = PHP_INT_MAX;
        }

        // Also add destination nodes
        foreach ($graph as $neighbors) {
            foreach (array_keys($neighbors) as $node) {
                if (!isset($distances[$node])) {
                    $distances[$node] = PHP_INT_MAX;
                    $previous[$node] = null;
                    $queue[$node] = PHP_INT_MAX;
                }
            }
        }

        $distances[$start] = 0;
        $queue[$start] = 0;

        while (!empty($queue)) {
            // Get node with minimum distance
            $minNode = array_search(min($queue), $queue);
            
            if ($minNode === $end) {
                break;
            }

            unset($queue[$minNode]);

            if (!isset($graph[$minNode])) {
                continue;
            }

            foreach ($graph[$minNode] as $neighbor => $edge) {
                $weight = match ($optimizeFor) {
                    'time' => $edge['time'],
                    'distance' => $edge['distance'],
                    default => $edge['cost'],
                };

                $alt = $distances[$minNode] + $weight;

                if ($alt < $distances[$neighbor]) {
                    $distances[$neighbor] = $alt;
                    $previous[$neighbor] = $minNode;
                    
                    if (isset($queue[$neighbor])) {
                        $queue[$neighbor] = $alt;
                    }
                }
            }
        }

        // Build path
        $path = [];
        $current = $end;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $previous[$current] ?? null;
        }

        // Verify path starts at origin
        if (empty($path) || $path[0] !== $start) {
            return [];
        }

        return $path;
    }

    /**
     * Build route legs from path
     */
    protected function buildRouteLegs(array $path, string $serviceLevel): array
    {
        $legs = [];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $origin = $path[$i];
            $destination = $path[$i + 1];

            $route = $this->findDirectRoute($origin, $destination, $serviceLevel);

            if (!$route) {
                return []; // Path broken
            }

            $legs[] = [
                'route_id' => $route->id,
                'origin_hub_id' => $origin,
                'destination_hub_id' => $destination,
                'origin_name' => $route->originHub->name ?? "Hub {$origin}",
                'destination_name' => $route->destinationHub->name ?? "Hub {$destination}",
                'distance_km' => $route->distance_km,
                'transit_hours' => $route->getAdjustedTransitTime(),
                'transport_mode' => $route->transport_mode,
                'departure_time' => $route->departure_time,
            ];
        }

        return $legs;
    }

    /**
     * Calculate cost for a route
     */
    public function calculateRouteCost(HubRoute $route, float $weightKg, float $volumeCbm): float
    {
        return $route->calculateCost($weightKg, $volumeCbm);
    }

    /**
     * Get hub capacity utilization
     */
    public function getHubCapacity(int $hubId): array
    {
        $hub = Hub::find($hubId);
        
        if (!$hub) {
            return ['error' => 'Hub not found'];
        }

        // Count current shipments at hub
        $currentShipments = Shipment::where(function ($q) use ($hubId) {
            $q->where('transfer_hub_id', $hubId)
              ->orWhere('origin_branch_id', $hubId)
              ->orWhere('dest_branch_id', $hubId);
        })
        ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
        ->count();

        $currentWeight = Shipment::where(function ($q) use ($hubId) {
            $q->where('transfer_hub_id', $hubId)
              ->orWhere('origin_branch_id', $hubId);
        })
        ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
        ->sum('chargeable_weight_kg');

        // Get hub capacity (use defaults if not set)
        $maxShipments = $hub->max_shipments ?? 1000;
        $maxWeight = $hub->max_weight ?? 50000;

        $shipmentUtilization = ($currentShipments / $maxShipments) * 100;
        $weightUtilization = ($currentWeight / $maxWeight) * 100;

        // Determine status
        $status = 'normal';
        if ($shipmentUtilization > 90 || $weightUtilization > 90) {
            $status = 'critical';
        } elseif ($shipmentUtilization > 75 || $weightUtilization > 75) {
            $status = 'warning';
        }

        return [
            'hub_id' => $hubId,
            'hub_name' => $hub->name,
            'current_shipments' => $currentShipments,
            'max_shipments' => $maxShipments,
            'shipment_utilization' => round($shipmentUtilization, 2),
            'current_weight_kg' => round($currentWeight, 2),
            'max_weight_kg' => $maxWeight,
            'weight_utilization' => round($weightUtilization, 2),
            'status' => $status,
            'available_capacity' => $maxShipments - $currentShipments,
        ];
    }

    /**
     * Find alternative hub with better capacity
     */
    public function findAlternativeHub(int $originHubId, int $destinationHubId, string $serviceLevel = 'standard'): ?array
    {
        // Get nearby hubs
        $originHub = Hub::find($originHubId);
        
        if (!$originHub) {
            return null;
        }

        // Find all routes from origin
        $routes = HubRoute::active()
            ->where('origin_hub_id', $originHubId)
            ->where('service_level', $serviceLevel)
            ->with('destinationHub')
            ->get();

        $alternatives = [];

        foreach ($routes as $route) {
            $hubCapacity = $this->getHubCapacity($route->destination_hub_id);
            
            // Only consider hubs with available capacity
            if ($hubCapacity['status'] !== 'critical' && $hubCapacity['available_capacity'] > 10) {
                // Check if this hub can route to final destination
                $onwardRoute = $this->findOptimalRoute(
                    $route->destination_hub_id,
                    $destinationHubId,
                    $serviceLevel
                );
                
                if ($onwardRoute['success']) {
                    $alternatives[] = [
                        'intermediate_hub_id' => $route->destination_hub_id,
                        'intermediate_hub_name' => $route->destinationHub->name,
                        'first_leg_distance' => $route->distance_km,
                        'first_leg_time' => $route->getAdjustedTransitTime(),
                        'total_distance' => $route->distance_km + $onwardRoute['total_distance_km'],
                        'total_time' => $route->getAdjustedTransitTime() + $onwardRoute['total_transit_hours'],
                        'hub_utilization' => $hubCapacity['shipment_utilization'],
                        'available_capacity' => $hubCapacity['available_capacity'],
                    ];
                }
            }
        }

        // Sort by total time, prefer hubs with more capacity
        usort($alternatives, function ($a, $b) {
            // Primary sort: total time
            $timeDiff = $a['total_time'] - $b['total_time'];
            if (abs($timeDiff) > 2) {
                return $timeDiff;
            }
            // Secondary sort: available capacity
            return $b['available_capacity'] - $a['available_capacity'];
        });

        return $alternatives[0] ?? null;
    }

    /**
     * Redistribute shipments to balance hub loads
     */
    public function rebalanceHubLoads(array $hubIds): array
    {
        $results = [];
        $capacities = [];

        // Get current capacity for all hubs
        foreach ($hubIds as $hubId) {
            $capacities[$hubId] = $this->getHubCapacity($hubId);
        }

        // Find overloaded hubs
        $overloaded = array_filter($capacities, fn($c) => $c['status'] === 'critical');
        $underutilized = array_filter($capacities, fn($c) => $c['status'] === 'normal' && $c['shipment_utilization'] < 50);

        foreach ($overloaded as $hubId => $capacity) {
            // Find shipments that can be rerouted
            $shipments = Shipment::where('transfer_hub_id', $hubId)
                ->where('status', 'in_transit')
                ->orderBy('expected_delivery_date', 'desc') // Move least urgent first
                ->limit(10)
                ->get();

            foreach ($shipments as $shipment) {
                foreach ($underutilized as $altHubId => $altCapacity) {
                    // Check if alternative hub can reach destination
                    $route = $this->findOptimalRoute(
                        $altHubId,
                        $shipment->dest_branch_id,
                        $shipment->service_level ?? 'standard'
                    );

                    if ($route['success'] && $route['total_transit_hours'] < 48) {
                        $results[] = [
                            'shipment_id' => $shipment->id,
                            'tracking_number' => $shipment->tracking_number,
                            'from_hub' => $hubId,
                            'to_hub' => $altHubId,
                            'action' => 'reroute_suggested',
                        ];
                        break;
                    }
                }
            }
        }

        return [
            'overloaded_hubs' => count($overloaded),
            'underutilized_hubs' => count($underutilized),
            'suggested_reroutes' => $results,
        ];
    }

    /**
     * Get all routes for a hub (inbound and outbound)
     */
    public function getHubRoutes(int $hubId): array
    {
        $outbound = HubRoute::active()
            ->where('origin_hub_id', $hubId)
            ->with('destinationHub')
            ->get()
            ->map(fn($r) => [
                'direction' => 'outbound',
                'route_id' => $r->id,
                'destination_id' => $r->destination_hub_id,
                'destination_name' => $r->destinationHub->name ?? 'Unknown',
                'distance_km' => $r->distance_km,
                'transit_hours' => $r->transit_time_hours,
                'service_level' => $r->service_level,
                'transport_mode' => $r->transport_mode,
            ]);

        $inbound = HubRoute::active()
            ->where('destination_hub_id', $hubId)
            ->with('originHub')
            ->get()
            ->map(fn($r) => [
                'direction' => 'inbound',
                'route_id' => $r->id,
                'origin_id' => $r->origin_hub_id,
                'origin_name' => $r->originHub->name ?? 'Unknown',
                'distance_km' => $r->distance_km,
                'transit_hours' => $r->transit_time_hours,
                'service_level' => $r->service_level,
                'transport_mode' => $r->transport_mode,
            ]);

        return [
            'hub_id' => $hubId,
            'outbound_routes' => $outbound->toArray(),
            'inbound_routes' => $inbound->toArray(),
            'total_connections' => $outbound->count() + $inbound->count(),
        ];
    }

    /**
     * Clear routing cache
     */
    public function clearCache(): void
    {
        Cache::forget('hub_routes_graph');
        // Pattern-based cache clearing would need Redis tags
    }
}
