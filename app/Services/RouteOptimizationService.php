<?php

namespace App\Services;

use App\Models\Route;
use App\Models\Stop;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\DeliveryMan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Route Optimization Service
 * 
 * Provides intelligent route planning and optimization using various algorithms:
 * - Nearest Neighbor Algorithm
 * - Genetic Algorithm for large routes
 * - Time Window Optimization
 * - Vehicle Capacity Constraints
 * - Traffic Pattern Consideration
 * - Multi-drop Optimization
 */
class RouteOptimizationService
{
    /**
     * Optimize a route for a given set of shipments
     *
     * @param array $shipmentIds
     * @param int|null $vehicleId
     * @param int|null $driverId
     * @param array $options
     * @return array
     */
    public function optimizeRoute(array $shipmentIds, ?int $vehicleId = null, ?int $driverId = null, array $options = []): array
    {
        $shipments = Shipment::whereIn('id', $shipmentIds)->get();
        
        if ($shipments->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No shipments found for optimization'
            ];
        }

        // Get vehicle constraints if provided
        $vehicleConstraints = $vehicleId ? $this->getVehicleConstraints($vehicleId) : null;
        
        // Get driver constraints if provided
        $driverConstraints = $driverId ? $this->getDriverConstraints($driverId) : null;

        // Determine optimization strategy
        $strategy = $options['strategy'] ?? 'nearest_neighbor';
        
        $optimizedSequence = match($strategy) {
            'genetic' => $this->geneticAlgorithm($shipments, $vehicleConstraints),
            'time_window' => $this->timeWindowOptimization($shipments, $vehicleConstraints),
            'balanced' => $this->balancedOptimization($shipments, $vehicleConstraints),
            default => $this->nearestNeighborOptimization($shipments, $vehicleConstraints)
        };

        // Calculate route metrics
        $metrics = $this->calculateRouteMetrics($optimizedSequence, $vehicleConstraints);

        // Check for constraint violations
        $violations = $this->checkConstraintViolations($optimizedSequence, $vehicleConstraints, $driverConstraints);

        return [
            'success' => empty($violations),
            'optimized_sequence' => $optimizedSequence,
            'metrics' => $metrics,
            'violations' => $violations,
            'strategy_used' => $strategy,
            'improvement' => $this->calculateImprovement($shipments, $optimizedSequence)
        ];
    }

    /**
     * Nearest Neighbor Algorithm - Fast and simple
     * Good for small to medium routes (< 50 stops)
     */
    protected function nearestNeighborOptimization(Collection $shipments, ?array $vehicleConstraints): array
    {
        if ($shipments->isEmpty()) {
            return [];
        }

        $unvisited = $shipments->keyBy('id')->toArray();
        $route = [];
        
        // Start from depot or first shipment
        $current = array_shift($unvisited);
        $route[] = [
            'shipment_id' => $current['id'],
            'sequence' => 1,
            'latitude' => $current['delivery_latitude'] ?? $current['receiver_lat'],
            'longitude' => $current['delivery_longitude'] ?? $current['receiver_long'],
            'estimated_arrival' => now()->addMinutes(15)
        ];

        $sequence = 2;
        
        // Find nearest neighbor for each stop
        while (!empty($unvisited)) {
            $nearestId = $this->findNearestShipment(
                $current['delivery_latitude'] ?? $current['receiver_lat'],
                $current['delivery_longitude'] ?? $current['receiver_long'],
                $unvisited
            );
            
            $current = $unvisited[$nearestId];
            unset($unvisited[$nearestId]);
            
            // Calculate estimated arrival time
            $prevStop = end($route);
            $travelTime = $this->calculateTravelTime(
                $prevStop['latitude'],
                $prevStop['longitude'],
                $current['delivery_latitude'] ?? $current['receiver_lat'],
                $current['delivery_longitude'] ?? $current['receiver_long']
            );
            
            $route[] = [
                'shipment_id' => $current['id'],
                'sequence' => $sequence++,
                'latitude' => $current['delivery_latitude'] ?? $current['receiver_lat'],
                'longitude' => $current['delivery_longitude'] ?? $current['receiver_long'],
                'estimated_arrival' => Carbon::parse($prevStop['estimated_arrival'])
                    ->addMinutes($travelTime + 5), // +5 min for stop time
                'distance_from_prev' => $this->calculateDistance(
                    $prevStop['latitude'],
                    $prevStop['longitude'],
                    $current['delivery_latitude'] ?? $current['receiver_lat'],
                    $current['delivery_longitude'] ?? $current['receiver_long']
                )
            ];

            // Check capacity constraints
            if ($vehicleConstraints && $this->wouldExceedCapacity($route, $vehicleConstraints)) {
                // Split route if capacity exceeded
                break;
            }
        }

        return $route;
    }

    /**
     * Genetic Algorithm - For complex multi-stop routes
     * Best for large routes (> 50 stops)
     */
    protected function geneticAlgorithm(Collection $shipments, ?array $vehicleConstraints): array
    {
        $populationSize = 50;
        $generations = 100;
        $mutationRate = 0.1;
        $eliteSize = 5;

        // Initialize population
        $population = $this->initializePopulation($shipments, $populationSize);
        
        for ($gen = 0; $gen < $generations; $gen++) {
            // Evaluate fitness
            $fitness = $this->evaluatePopulation($population, $vehicleConstraints);
            
            // Select elite
            $elite = $this->selectElite($population, $fitness, $eliteSize);
            
            // Create new generation
            $newPopulation = $elite;
            
            while (count($newPopulation) < $populationSize) {
                // Selection
                $parent1 = $this->tournamentSelection($population, $fitness);
                $parent2 = $this->tournamentSelection($population, $fitness);
                
                // Crossover
                $child = $this->orderCrossover($parent1, $parent2);
                
                // Mutation
                if (rand(0, 100) / 100 < $mutationRate) {
                    $child = $this->mutate($child);
                }
                
                $newPopulation[] = $child;
            }
            
            $population = $newPopulation;
        }

        // Return best solution
        $fitness = $this->evaluatePopulation($population, $vehicleConstraints);
        $bestIndex = array_search(max($fitness), $fitness);
        
        return $this->populationToRoute($population[$bestIndex], $shipments);
    }

    /**
     * Time Window Optimization - Prioritize delivery time windows
     */
    protected function timeWindowOptimization(Collection $shipments, ?array $vehicleConstraints): array
    {
        // Sort by time window urgency
        $sorted = $shipments->sortBy(function($shipment) {
            return $shipment->delivery_time_from ?? '23:59';
        });

        // Apply nearest neighbor with time constraints
        return $this->nearestNeighborOptimization($sorted, $vehicleConstraints);
    }

    /**
     * Balanced Optimization - Balance distance and time windows
     */
    protected function balancedOptimization(Collection $shipments, ?array $vehicleConstraints): array
    {
        // Cluster shipments by geographic proximity
        $clusters = $this->clusterShipments($shipments, 5);
        
        $route = [];
        $sequence = 1;
        
        foreach ($clusters as $cluster) {
            // Optimize each cluster
            $clusterRoute = $this->nearestNeighborOptimization(collect($cluster), $vehicleConstraints);
            
            // Renumber sequences
            foreach ($clusterRoute as $stop) {
                $stop['sequence'] = $sequence++;
                $route[] = $stop;
            }
        }
        
        return $route;
    }

    /**
     * Find nearest shipment using Haversine formula
     */
    protected function findNearestShipment(float $lat, float $lon, array $shipments): int
    {
        $minDistance = PHP_FLOAT_MAX;
        $nearestId = null;

        foreach ($shipments as $id => $shipment) {
            $distance = $this->calculateDistance(
                $lat,
                $lon,
                $shipment['delivery_latitude'] ?? $shipment['receiver_lat'],
                $shipment['delivery_longitude'] ?? $shipment['receiver_long']
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestId = $id;
            }
        }

        return $nearestId;
    }

    /**
     * Calculate distance using Haversine formula (in kilometers)
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate travel time in minutes (with traffic factor)
     */
    protected function calculateTravelTime(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $distance = $this->calculateDistance($lat1, $lon1, $lat2, $lon2);
        
        // Average speed: 30 km/h in city, 60 km/h highway
        $avgSpeed = 30; // km/h
        
        // Apply traffic factor (1.0 = no traffic, 2.0 = heavy traffic)
        $trafficFactor = $this->getTrafficFactor();
        
        $timeHours = ($distance / $avgSpeed) * $trafficFactor;
        
        return (int) ceil($timeHours * 60); // Convert to minutes
    }

    /**
     * Get traffic factor based on time of day
     */
    protected function getTrafficFactor(): float
    {
        $hour = now()->hour;
        
        // Rush hours: 7-9 AM and 5-7 PM
        if (($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 19)) {
            return 1.8;
        }
        
        // Normal hours
        if ($hour >= 10 && $hour <= 16) {
            return 1.0;
        }
        
        // Evening/Night
        return 0.8;
    }

    /**
     * Calculate route metrics
     */
    protected function calculateRouteMetrics(array $route, ?array $vehicleConstraints): array
    {
        if (empty($route)) {
            return [
                'total_distance' => 0,
                'total_time' => 0,
                'total_stops' => 0,
                'estimated_fuel_cost' => 0
            ];
        }

        $totalDistance = 0;
        $totalTime = 0;

        for ($i = 1; $i < count($route); $i++) {
            $totalDistance += $route[$i]['distance_from_prev'] ?? 0;
        }

        $firstStop = reset($route);
        $lastStop = end($route);
        $totalTime = Carbon::parse($lastStop['estimated_arrival'])
            ->diffInMinutes(Carbon::parse($firstStop['estimated_arrival']));

        // Fuel consumption: avg 10 km/liter
        $fuelConsumption = $totalDistance / 10;
        $fuelPrice = 1.5; // USD per liter
        $estimatedFuelCost = $fuelConsumption * $fuelPrice;

        return [
            'total_distance' => round($totalDistance, 2),
            'total_time' => $totalTime,
            'total_stops' => count($route),
            'estimated_fuel_cost' => round($estimatedFuelCost, 2),
            'avg_distance_per_stop' => round($totalDistance / count($route), 2),
            'estimated_completion' => $lastStop['estimated_arrival']
        ];
    }

    /**
     * Get vehicle constraints
     */
    protected function getVehicleConstraints(int $vehicleId): array
    {
        $vehicle = Vehicle::find($vehicleId);
        
        if (!$vehicle) {
            return [
                'max_capacity_weight' => 1000, // kg
                'max_capacity_volume' => 10, // m³
                'max_distance' => 300 // km
            ];
        }

        return [
            'max_capacity_weight' => $vehicle->capacity ?? 1000,
            'max_capacity_volume' => $vehicle->volume ?? 10,
            'max_distance' => $vehicle->max_distance ?? 300,
            'fuel_type' => $vehicle->fuel_type ?? 'diesel'
        ];
    }

    /**
     * Get driver constraints
     */
    protected function getDriverConstraints(int $driverId): array
    {
        $driver = DeliveryMan::find($driverId);
        
        return [
            'max_working_hours' => 10,
            'max_stops_per_day' => 50,
            'skills' => $driver->skills ?? [],
            'certifications' => $driver->certifications ?? []
        ];
    }

    /**
     * Check if route would exceed capacity
     */
    protected function wouldExceedCapacity(array $route, array $constraints): bool
    {
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($route as $stop) {
            $shipment = Shipment::find($stop['shipment_id']);
            if ($shipment) {
                $totalWeight += $shipment->weight ?? 0;
                $totalVolume += $shipment->volume ?? 0;
            }
        }

        return $totalWeight > $constraints['max_capacity_weight'] ||
               $totalVolume > $constraints['max_capacity_volume'];
    }

    /**
     * Check for constraint violations
     */
    protected function checkConstraintViolations(array $route, ?array $vehicleConstraints, ?array $driverConstraints): array
    {
        $violations = [];

        if ($vehicleConstraints) {
            $metrics = $this->calculateRouteMetrics($route, $vehicleConstraints);
            
            if ($metrics['total_distance'] > $vehicleConstraints['max_distance']) {
                $violations[] = [
                    'type' => 'distance_exceeded',
                    'message' => 'Route distance exceeds vehicle maximum',
                    'limit' => $vehicleConstraints['max_distance'],
                    'actual' => $metrics['total_distance']
                ];
            }
        }

        if ($driverConstraints) {
            $metrics = $this->calculateRouteMetrics($route, $vehicleConstraints);
            
            if ($metrics['total_time'] / 60 > $driverConstraints['max_working_hours']) {
                $violations[] = [
                    'type' => 'working_hours_exceeded',
                    'message' => 'Route time exceeds driver working hours',
                    'limit' => $driverConstraints['max_working_hours'],
                    'actual' => round($metrics['total_time'] / 60, 2)
                ];
            }

            if (count($route) > $driverConstraints['max_stops_per_day']) {
                $violations[] = [
                    'type' => 'stops_exceeded',
                    'message' => 'Route stops exceed driver daily limit',
                    'limit' => $driverConstraints['max_stops_per_day'],
                    'actual' => count($route)
                ];
            }
        }

        return $violations;
    }

    /**
     * Calculate improvement percentage
     */
    protected function calculateImprovement(Collection $originalShipments, array $optimizedRoute): array
    {
        // Calculate original route distance (simple sequence)
        $originalDistance = 0;
        $shipmentArray = $originalShipments->toArray();
        
        for ($i = 1; $i < count($shipmentArray); $i++) {
            $prev = $shipmentArray[$i - 1];
            $curr = $shipmentArray[$i];
            
            $originalDistance += $this->calculateDistance(
                $prev['delivery_latitude'] ?? $prev['receiver_lat'],
                $prev['delivery_longitude'] ?? $prev['receiver_long'],
                $curr['delivery_latitude'] ?? $curr['receiver_lat'],
                $curr['delivery_longitude'] ?? $curr['receiver_long']
            );
        }

        // Calculate optimized distance
        $optimizedDistance = 0;
        foreach ($optimizedRoute as $stop) {
            $optimizedDistance += $stop['distance_from_prev'] ?? 0;
        }

        $improvement = $originalDistance > 0 
            ? (($originalDistance - $optimizedDistance) / $originalDistance) * 100 
            : 0;

        return [
            'original_distance' => round($originalDistance, 2),
            'optimized_distance' => round($optimizedDistance, 2),
            'distance_saved' => round($originalDistance - $optimizedDistance, 2),
            'improvement_percentage' => round($improvement, 2),
            'estimated_cost_savings' => round(($originalDistance - $optimizedDistance) / 10 * 1.5, 2)
        ];
    }

    /**
     * Cluster shipments by geographic proximity (K-means)
     */
    protected function clusterShipments(Collection $shipments, int $k): array
    {
        $data = $shipments->map(function($s) {
            return [
                'id' => $s->id,
                'lat' => $s->delivery_latitude ?? $s->receiver_lat,
                'lon' => $s->delivery_longitude ?? $s->receiver_long
            ];
        })->toArray();

        // Simple K-means clustering
        $centroids = array_slice($data, 0, $k);
        $clusters = array_fill(0, $k, []);
        
        $maxIterations = 10;
        for ($iter = 0; $iter < $maxIterations; $iter++) {
            // Assign to nearest centroid
            $clusters = array_fill(0, $k, []);
            
            foreach ($data as $point) {
                $minDist = PHP_FLOAT_MAX;
                $cluster = 0;
                
                foreach ($centroids as $idx => $centroid) {
                    $dist = $this->calculateDistance(
                        $point['lat'],
                        $point['lon'],
                        $centroid['lat'],
                        $centroid['lon']
                    );
                    
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $cluster = $idx;
                    }
                }
                
                $clusters[$cluster][] = $point;
            }
            
            // Update centroids
            foreach ($clusters as $idx => $cluster) {
                if (!empty($cluster)) {
                    $centroids[$idx] = [
                        'lat' => array_sum(array_column($cluster, 'lat')) / count($cluster),
                        'lon' => array_sum(array_column($cluster, 'lon')) / count($cluster)
                    ];
                }
            }
        }

        return $clusters;
    }

    /**
     * Helper methods for Genetic Algorithm
     */
    protected function initializePopulation(Collection $shipments, int $size): array
    {
        $population = [];
        $shipmentIds = $shipments->pluck('id')->toArray();
        
        for ($i = 0; $i < $size; $i++) {
            $individual = $shipmentIds;
            shuffle($individual);
            $population[] = $individual;
        }
        
        return $population;
    }

    protected function evaluatePopulation(array $population, ?array $vehicleConstraints): array
    {
        return array_map(function($individual) use ($vehicleConstraints) {
            // Fitness = 1 / total_distance (lower distance = higher fitness)
            $route = $this->sequenceToRoute($individual);
            $metrics = $this->calculateRouteMetrics($route, $vehicleConstraints);
            return $metrics['total_distance'] > 0 ? 1000 / $metrics['total_distance'] : 0;
        }, $population);
    }

    protected function sequenceToRoute(array $sequence): array
    {
        $route = [];
        foreach ($sequence as $idx => $shipmentId) {
            $shipment = Shipment::find($shipmentId);
            if ($shipment) {
                $route[] = [
                    'shipment_id' => $shipmentId,
                    'sequence' => $idx + 1,
                    'latitude' => $shipment->delivery_latitude ?? $shipment->receiver_lat,
                    'longitude' => $shipment->delivery_longitude ?? $shipment->receiver_long
                ];
            }
        }
        
        // Calculate distances
        for ($i = 1; $i < count($route); $i++) {
            $route[$i]['distance_from_prev'] = $this->calculateDistance(
                $route[$i-1]['latitude'],
                $route[$i-1]['longitude'],
                $route[$i]['latitude'],
                $route[$i]['longitude']
            );
        }
        
        return $route;
    }

    protected function selectElite(array $population, array $fitness, int $size): array
    {
        arsort($fitness);
        $eliteIndices = array_slice(array_keys($fitness), 0, $size);
        return array_intersect_key($population, array_flip($eliteIndices));
    }

    protected function tournamentSelection(array $population, array $fitness, int $tournamentSize = 5): array
    {
        $tournament = array_rand($fitness, min($tournamentSize, count($fitness)));
        $best = is_array($tournament) ? $tournament[0] : $tournament;
        $bestFitness = $fitness[$best];
        
        foreach ((array)$tournament as $idx) {
            if ($fitness[$idx] > $bestFitness) {
                $best = $idx;
                $bestFitness = $fitness[$idx];
            }
        }
        
        return $population[$best];
    }

    protected function orderCrossover(array $parent1, array $parent2): array
    {
        $size = count($parent1);
        $start = rand(0, $size - 2);
        $end = rand($start + 1, $size - 1);
        
        $child = array_fill(0, $size, null);
        
        // Copy segment from parent1
        for ($i = $start; $i <= $end; $i++) {
            $child[$i] = $parent1[$i];
        }
        
        // Fill remaining from parent2
        $currentPos = ($end + 1) % $size;
        $parent2Pos = ($end + 1) % $size;
        
        while (in_array(null, $child, true)) {
            if (!in_array($parent2[$parent2Pos], $child, true)) {
                $child[$currentPos] = $parent2[$parent2Pos];
                $currentPos = ($currentPos + 1) % $size;
            }
            $parent2Pos = ($parent2Pos + 1) % $size;
        }
        
        return $child;
    }

    protected function mutate(array $individual): array
    {
        $size = count($individual);
        $pos1 = rand(0, $size - 1);
        $pos2 = rand(0, $size - 1);
        
        // Swap mutation
        $temp = $individual[$pos1];
        $individual[$pos1] = $individual[$pos2];
        $individual[$pos2] = $temp;
        
        return $individual;
    }

    protected function populationToRoute(array $individual, Collection $shipments): array
    {
        return $this->sequenceToRoute($individual);
    }
}
