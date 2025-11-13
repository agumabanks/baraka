<?php

namespace App\Services;

use App\Models\Backend\Asset;
use App\Models\Backend\Vehicle;
use App\Models\Backend\Maintenance;
use App\Models\Backend\Fuel;
use App\Models\Backend\Accident;
use App\Models\Backend\Branch;
use App\Events\AssetMaintenanceAlertEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AssetManagementService
{
    /**
     * Get asset status overview
     */
    public function getAssetStatus(Branch $branch = null): array
    {
        $assetsQuery = Asset::with(['vehicle', 'branch']);
        $vehiclesQuery = Vehicle::with('asset');

        if ($branch) {
            $assetsQuery->where('branch_id', $branch->id);
            $vehiclesQuery->whereHas('asset', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            });
        }

        $assets = $assetsQuery->get();
        $vehicles = $vehiclesQuery->get();

        $assetStatus = $this->categorizeAssetsByStatus($assets);
        $vehicleStatus = $this->categorizeVehiclesByStatus($vehicles);

        return [
            'branch' => $branch?->name,
            'summary' => [
                'total_assets' => $assets->count(),
                'total_vehicles' => $vehicles->count(),
                'operational_assets' => $assetStatus['operational']->count(),
                'operational_vehicles' => $vehicleStatus['operational']->count(),
                'maintenance_due' => $assetStatus['maintenance_due']->count(),
                'out_of_service' => $assetStatus['out_of_service']->count(),
            ],
            'assets_by_status' => $assetStatus,
            'vehicles_by_status' => $vehicleStatus,
            'utilization_metrics' => $this->getUtilizationMetrics($assets, $vehicles),
        ];
    }

    /**
     * Get vehicle utilization metrics
     */
    public function getVehicleUtilization(Vehicle $vehicle, Carbon $startDate, Carbon $endDate): array
    {
        // Get fuel consumption data
        $fuelRecords = Fuel::where('asset_id', $vehicle->asset_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Get maintenance records
        $maintenanceRecords = Maintenance::where('asset_id', $vehicle->asset_id)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->get();

        // Get accident records
        $accidentRecords = Accident::where('asset_id', $vehicle->asset_id)
            ->whereBetween('date_of_accident', [$startDate, $endDate])
            ->get();

        $totalFuel = $fuelRecords->sum('amount');
        $totalFuelCost = $fuelRecords->sum(function ($fuel) {
            return $fuel->amount * ($fuel->invoice_of_fuel ?? 0);
        });

        $maintenanceCost = $maintenanceRecords->sum(function ($maintenance) {
            return $maintenance->invoice_of_the_purchases ?? 0;
        });

        $accidentCost = $accidentRecords->sum('cost_of_repair');

        // Calculate fuel efficiency (km per liter)
        $fuelEfficiency = $this->calculateFuelEfficiency($vehicle, $fuelRecords);

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_no' => $vehicle->plate_no,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'fuel_consumption' => [
                'total_liters' => round($totalFuel, 2),
                'total_cost' => round($totalFuelCost, 2),
                'average_cost_per_liter' => $totalFuel > 0 ? round($totalFuelCost / $totalFuel, 2) : 0,
                'efficiency_km_per_liter' => $fuelEfficiency,
            ],
            'maintenance' => [
                'total_records' => $maintenanceRecords->count(),
                'total_cost' => round($maintenanceCost, 2),
                'average_cost_per_maintenance' => $maintenanceRecords->count() > 0 ?
                    round($maintenanceCost / $maintenanceRecords->count(), 2) : 0,
            ],
            'accidents' => [
                'total_incidents' => $accidentRecords->count(),
                'total_cost' => round($accidentCost, 2),
                'average_cost_per_accident' => $accidentRecords->count() > 0 ?
                    round($accidentCost / $accidentRecords->count(), 2) : 0,
            ],
            'total_operating_cost' => round($totalFuelCost + $maintenanceCost + $accidentCost, 2),
            'cost_breakdown' => [
                'fuel_percentage' => round((($totalFuelCost + $maintenanceCost + $accidentCost) > 0) ?
                    ($totalFuelCost / ($totalFuelCost + $maintenanceCost + $accidentCost)) * 100 : 0, 1),
                'maintenance_percentage' => round((($totalFuelCost + $maintenanceCost + $accidentCost) > 0) ?
                    ($maintenanceCost / ($totalFuelCost + $maintenanceCost + $accidentCost)) * 100 : 0, 1),
                'accident_percentage' => round((($totalFuelCost + $maintenanceCost + $accidentCost) > 0) ?
                    ($accidentCost / ($totalFuelCost + $maintenanceCost + $accidentCost)) * 100 : 0, 1),
            ],
        ];
    }

    /**
     * Get maintenance schedule
     */
    public function getMaintenanceSchedule(Carbon $startDate, Carbon $endDate, Branch $branch = null): Collection
    {
        $query = Maintenance::with(['asset.vehicle'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orWhere(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            });

        if ($branch) {
            $query->whereHas('asset', function ($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            });
        }

        return $query->orderBy('start_date')
                    ->get()
                    ->map(function ($maintenance) {
                        return [
                            'id' => $maintenance->id,
                            'asset_id' => $maintenance->asset_id,
                            'asset_name' => $maintenance->asset->name ?? 'Unknown',
                            'vehicle_plate' => $maintenance->asset->vehicle->plate_no ?? null,
                            'start_date' => $maintenance->start_date?->toDateString(),
                            'end_date' => $maintenance->end_date?->toDateString(),
                            'repair_details' => $maintenance->repair_details,
                            'spare_parts_details' => $maintenance->spare_parts_purchased_details,
                            'invoice_amount' => $maintenance->invoice_of_the_purchases,
                            'status' => $this->getMaintenanceStatus($maintenance),
                            'duration_days' => $maintenance->start_date && $maintenance->end_date ?
                                $maintenance->start_date->diffInDays($maintenance->end_date) : null,
                        ];
                    });
    }

    /**
     * Get fuel consumption data
     */
    public function getFuelConsumption(Vehicle $vehicle, Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $fuelRecords = Fuel::where('asset_id', $vehicle->asset_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $dailyConsumption = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            $dayFuel = $fuelRecords->filter(function ($fuel) use ($dateString) {
                return $fuel->created_at->toDateString() === $dateString;
            });

            $dailyConsumption[] = [
                'date' => $dateString,
                'liters' => round($dayFuel->sum('amount'), 2),
                'cost' => round($dayFuel->sum(function ($fuel) {
                    return $fuel->amount * ($fuel->invoice_of_fuel ?? 0);
                }), 2),
                'transactions' => $dayFuel->count(),
            ];

            $currentDate->addDay();
        }

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_no' => $vehicle->plate_no,
                'model' => $vehicle->model,
            ],
            'month' => $month->format('Y-m'),
            'summary' => [
                'total_liters' => round($fuelRecords->sum('amount'), 2),
                'total_cost' => round($fuelRecords->sum(function ($fuel) {
                    return $fuel->amount * ($fuel->invoice_of_fuel ?? 0);
                }), 2),
                'average_daily_liters' => round($fuelRecords->sum('amount') / $month->daysInMonth, 2),
                'transactions_count' => $fuelRecords->count(),
            ],
            'daily_consumption' => $dailyConsumption,
            'fuel_type_breakdown' => $fuelRecords->groupBy('fuel_type')->map(function ($fuels) {
                return [
                    'liters' => round($fuels->sum('amount'), 2),
                    'cost' => round($fuels->sum(function ($fuel) {
                        return $fuel->amount * ($fuel->invoice_of_fuel ?? 0);
                    }), 2),
                    'transactions' => $fuels->count(),
                ];
            }),
        ];
    }

    /**
     * Get asset metrics
     */
    public function getAssetMetrics(Branch $branch = null): array
    {
        $assetsQuery = Asset::query();
        $vehiclesQuery = Vehicle::query();

        if ($branch) {
            $assetsQuery->where('branch_id', $branch->id);
            $vehiclesQuery->whereHas('asset', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            });
        }

        $assets = $assetsQuery->get();
        $vehicles = $vehiclesQuery->get();

        // Calculate asset utilization
        $utilizationData = $this->calculateAssetUtilization($assets);

        // Get maintenance metrics
        $maintenanceMetrics = $this->calculateMaintenanceMetrics($assets);

        // Get fuel efficiency metrics
        $fuelMetrics = $this->calculateFuelEfficiencyMetrics($vehicles);

        return [
            'branch' => $branch?->name,
            'asset_utilization' => $utilizationData,
            'maintenance_metrics' => $maintenanceMetrics,
            'fuel_efficiency' => $fuelMetrics,
            'health_score' => $this->calculateAssetHealthScore($utilizationData, $maintenanceMetrics),
            'recommendations' => $this->generateAssetRecommendations($utilizationData, $maintenanceMetrics, $fuelMetrics),
        ];
    }

    /**
     * Get available vehicles for assignment
     */
    public function getAvailableVehicles(Branch $branch, Carbon $date): Collection
    {
        $assetsHaveStatusColumn = $this->assetStatusColumnExists();

        return Vehicle::whereHas('asset', function ($query) use ($branch, $assetsHaveStatusColumn) {
                $query->where('branch_id', $branch->id);

                if ($assetsHaveStatusColumn) {
                    $query->where('status', 'active');
                }
            })
            ->whereDoesntHave('asset.maintenances', function ($query) use ($date) {
                $query->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
            })
            ->whereDoesntHave('asset.accidents', function ($query) use ($date) {
                // Exclude vehicles with recent accidents (last 30 days)
                $query->where('date_of_accident', '>=', $date->copy()->subDays(30));
            })
            ->with(['asset'])
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'plate_no' => $vehicle->plate_no,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'brand' => $vehicle->brand,
                    'asset_id' => $vehicle->asset_id,
                    'asset_name' => $vehicle->asset->name ?? 'Unknown',
                    'status' => $this->getVehicleStatus($vehicle),
                    'next_maintenance' => $this->getNextMaintenanceDate($vehicle),
                ];
            });
    }

    /**
     * Check for maintenance alerts and send notifications
     */
    public function checkMaintenanceAlerts(): array
    {
        $alerts = [];
        $assetsHaveStatusColumn = $this->assetStatusColumnExists();

        // Check upcoming maintenance (next 7 days)
        $upcomingQuery = Maintenance::with(['asset.vehicle'])
            ->where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(7));

        if ($assetsHaveStatusColumn) {
            $upcomingQuery->whereDoesntHave('asset', function ($query) {
                $query->where('status', 'maintenance');
            });
        }

        $upcomingMaintenance = $upcomingQuery->get();

        foreach ($upcomingMaintenance as $maintenance) {
            $alerts[] = [
                'type' => 'maintenance_upcoming',
                'asset_id' => $maintenance->asset_id,
                'asset_name' => $maintenance->asset->name ?? 'Unknown',
                'vehicle_plate' => $maintenance->asset->vehicle->plate_no ?? null,
                'maintenance_date' => $maintenance->start_date->toDateString(),
                'details' => $maintenance->repair_details,
            ];
        }

        // Check overdue maintenance
        $overdueQuery = Maintenance::with(['asset.vehicle'])
            ->where('end_date', '<', now());

        if ($assetsHaveStatusColumn) {
            $overdueQuery->whereHas('asset', function ($query) {
                $query->where('status', 'active');
            });
        }

        $overdueMaintenance = $overdueQuery->get();

        foreach ($overdueMaintenance as $maintenance) {
            $alerts[] = [
                'type' => 'maintenance_overdue',
                'asset_id' => $maintenance->asset_id,
                'asset_name' => $maintenance->asset->name ?? 'Unknown',
                'vehicle_plate' => $maintenance->asset->vehicle->plate_no ?? null,
                'overdue_days' => now()->diffInDays($maintenance->end_date),
                'details' => $maintenance->repair_details,
            ];
        }

        // Send alerts for critical maintenance issues
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'maintenance_overdue') {
                broadcast(new AssetMaintenanceAlertEvent($alert))->toOthers();
            }
        }

        return $alerts;
    }

    /**
     * Categorize assets by status
     */
    private function categorizeAssetsByStatus(Collection $assets): array
    {
        return [
            'operational' => $assets->filter(function ($asset) {
                return $asset->status === 'active' &&
                       !$this->hasActiveMaintenance($asset) &&
                       !$this->hasRecentAccident($asset);
            }),
            'maintenance_due' => $assets->filter(function ($asset) {
                return $this->hasUpcomingMaintenance($asset);
            }),
            'in_maintenance' => $assets->filter(function ($asset) {
                return $this->hasActiveMaintenance($asset);
            }),
            'out_of_service' => $assets->filter(function ($asset) {
                return $asset->status !== 'active' || $this->hasRecentAccident($asset);
            }),
        ];
    }

    /**
     * Categorize vehicles by status
     */
    private function categorizeVehiclesByStatus(Collection $vehicles): array
    {
        return [
            'operational' => $vehicles->filter(function ($vehicle) {
                return $this->getVehicleStatus($vehicle) === 'operational';
            }),
            'maintenance' => $vehicles->filter(function ($vehicle) {
                return $this->getVehicleStatus($vehicle) === 'maintenance';
            }),
            'out_of_service' => $vehicles->filter(function ($vehicle) {
                return $this->getVehicleStatus($vehicle) === 'out_of_service';
            }),
        ];
    }

    /**
     * Get utilization metrics
     */
    private function getUtilizationMetrics(Collection $assets, Collection $vehicles): array
    {
        $totalAssets = $assets->count();
        $totalVehicles = $vehicles->count();

        $operationalAssets = $assets->where('status', 'active')->count();
        $operationalVehicles = $vehicles->filter(function ($vehicle) {
            return $this->getVehicleStatus($vehicle) === 'operational';
        })->count();

        return [
            'asset_utilization_rate' => $totalAssets > 0 ? round(($operationalAssets / $totalAssets) * 100, 1) : 0,
            'vehicle_utilization_rate' => $totalVehicles > 0 ? round(($operationalVehicles / $totalVehicles) * 100, 1) : 0,
            'average_maintenance_duration' => $this->calculateAverageMaintenanceDuration($assets),
            'fuel_efficiency_trend' => $this->calculateFuelEfficiencyTrend($vehicles),
        ];
    }

    /**
     * Calculate fuel efficiency
     */
    private function calculateFuelEfficiency(Vehicle $vehicle, Collection $fuelRecords): float
    {
        // This is a simplified calculation - in reality, you'd need distance data
        // For now, return a mock efficiency based on fuel consumption patterns
        if ($fuelRecords->isEmpty()) {
            return 0;
        }

        $totalFuel = $fuelRecords->sum('amount');
        $daysActive = $fuelRecords->groupBy(function ($fuel) {
            return $fuel->created_at->toDateString();
        })->count();

        // Assume average daily distance of 100km for calculation
        $estimatedDistance = $daysActive * 100;

        return $estimatedDistance > 0 ? round($estimatedDistance / $totalFuel, 1) : 0;
    }

    /**
     * Get maintenance status
     */
    private function getMaintenanceStatus(Maintenance $maintenance): string
    {
        $now = now();

        if ($maintenance->end_date && $maintenance->end_date < $now) {
            return 'completed';
        } elseif ($maintenance->start_date && $maintenance->start_date <= $now) {
            return 'in_progress';
        } else {
            return 'scheduled';
        }
    }

    /**
     * Get vehicle status
     */
    private function getVehicleStatus(Vehicle $vehicle): string
    {
        $asset = $vehicle->asset;

        if (!$asset || $asset->status !== 'active') {
            return 'out_of_service';
        }

        if ($this->hasActiveMaintenance($asset)) {
            return 'maintenance';
        }

        if ($this->hasRecentAccident($asset)) {
            return 'out_of_service';
        }

        return 'operational';
    }

    /**
     * Get next maintenance date
     */
    private function getNextMaintenanceDate(Vehicle $vehicle): ?string
    {
        $nextMaintenance = Maintenance::where('asset_id', $vehicle->asset_id)
            ->where('start_date', '>', now())
            ->orderBy('start_date')
            ->first();

        return $nextMaintenance?->start_date?->toDateString();
    }

    /**
     * Check if asset has active maintenance
     */
    private function hasActiveMaintenance(Asset $asset): bool
    {
        return Maintenance::where('asset_id', $asset->id)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Check if asset has upcoming maintenance
     */
    private function hasUpcomingMaintenance(Asset $asset): bool
    {
        return Maintenance::where('asset_id', $asset->id)
            ->where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(30))
            ->exists();
    }

    /**
     * Check if asset has recent accident
     */
    private function hasRecentAccident(Asset $asset): bool
    {
        return Accident::where('asset_id', $asset->id)
            ->where('date_of_accident', '>=', now()->subDays(30))
            ->exists();
    }

    /**
     * Calculate asset utilization
     */
    private function calculateAssetUtilization(Collection $assets): array
    {
        $totalAssets = $assets->count();
        $activeAssets = $assets->where('status', 'active')->count();
        $maintenanceAssets = $assets->filter(function ($asset) {
            return $this->hasActiveMaintenance($asset);
        })->count();

        return [
            'total_assets' => $totalAssets,
            'active_assets' => $activeAssets,
            'maintenance_assets' => $maintenanceAssets,
            'utilization_rate' => $totalAssets > 0 ? round(($activeAssets / $totalAssets) * 100, 1) : 0,
        ];
    }

    /**
     * Calculate maintenance metrics
     */
    private function calculateMaintenanceMetrics(Collection $assets): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $recentMaintenance = Maintenance::whereIn('asset_id', $assets->pluck('id'))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get();

        $totalCost = $recentMaintenance->sum('invoice_of_the_purchases');
        $averageDuration = $recentMaintenance->filter(function ($maintenance) {
            return $maintenance->start_date && $maintenance->end_date;
        })->avg(function ($maintenance) {
            return $maintenance->start_date->diffInDays($maintenance->end_date);
        });

        return [
            'maintenance_count_30_days' => $recentMaintenance->count(),
            'total_maintenance_cost_30_days' => round($totalCost, 2),
            'average_maintenance_duration_days' => round($averageDuration ?? 0, 1),
            'maintenance_cost_per_asset' => $assets->count() > 0 ?
                round($totalCost / $assets->count(), 2) : 0,
        ];
    }

    /**
     * Calculate fuel efficiency metrics
     */
    private function calculateFuelEfficiencyMetrics(Collection $vehicles): array
    {
        if ($vehicles->isEmpty()) {
            return [
                'average_fuel_efficiency' => 0,
                'total_fuel_consumption_30_days' => 0,
                'fuel_cost_per_km' => 0,
            ];
        }

        $thirtyDaysAgo = now()->subDays(30);

        $fuelRecords = Fuel::whereIn('asset_id', $vehicles->pluck('asset_id'))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get();

        $totalFuel = $fuelRecords->sum('amount');
        $totalCost = $fuelRecords->sum(function ($fuel) {
            return $fuel->amount * ($fuel->invoice_of_fuel ?? 0);
        });

        // Simplified efficiency calculation
        $estimatedTotalDistance = $fuelRecords->groupBy(function ($fuel) {
            return $fuel->created_at->toDateString();
        })->count() * 100; // Assume 100km per active day

        return [
            'average_fuel_efficiency' => $totalFuel > 0 ? round($estimatedTotalDistance / $totalFuel, 1) : 0,
            'total_fuel_consumption_30_days' => round($totalFuel, 2),
            'fuel_cost_per_km' => $estimatedTotalDistance > 0 ? round($totalCost / $estimatedTotalDistance, 2) : 0,
        ];
    }

    /**
     * Calculate average maintenance duration
     */
    private function calculateAverageMaintenanceDuration(Collection $assets): float
    {
        $maintenanceRecords = Maintenance::whereIn('asset_id', $assets->pluck('id'))
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        if ($maintenanceRecords->isEmpty()) {
            return 0;
        }

        return round($maintenanceRecords->avg(function ($maintenance) {
            return $maintenance->start_date->diffInDays($maintenance->end_date);
        }), 1);
    }

    /**
     * Calculate fuel efficiency trend
     */
    private function calculateFuelEfficiencyTrend(Collection $vehicles): string
    {
        // Simplified trend calculation - in reality, you'd compare multiple periods
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $currentFuel = Fuel::whereIn('asset_id', $vehicles->pluck('asset_id'))
            ->whereBetween('created_at', [$currentMonth, now()])
            ->sum('amount');

        $lastMonthFuel = Fuel::whereIn('asset_id', $vehicles->pluck('asset_id'))
            ->whereBetween('created_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->sum('amount');

        if ($lastMonthFuel == 0) {
            return 'stable';
        }

        $change = (($currentFuel - $lastMonthFuel) / $lastMonthFuel) * 100;

        if ($change > 5) {
            return 'increasing';
        } elseif ($change < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate asset health score
     */
    private function calculateAssetHealthScore(array $utilization, array $maintenance): float
    {
        $utilizationScore = $utilization['utilization_rate'];
        $maintenanceFrequencyScore = min(100, 100 - ($maintenance['maintenance_count_30_days'] * 5));

        return round(($utilizationScore + $maintenanceFrequencyScore) / 2, 1);
    }

    /**
     * Generate asset recommendations
     */
    private function generateAssetRecommendations(array $utilization, array $maintenance, array $fuel): array
    {
        $recommendations = [];

        if ($utilization['utilization_rate'] < 70) {
            $recommendations[] = 'Consider optimizing asset utilization through better scheduling';
        }

        if ($maintenance['maintenance_count_30_days'] > 10) {
            $recommendations[] = 'High maintenance frequency detected - review asset maintenance schedules';
        }

        if ($fuel['fuel_cost_per_km'] > 0.5) {
            $recommendations[] = 'Fuel costs are high - consider fuel efficiency improvements';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Asset performance is optimal - continue current maintenance schedule';
        }

        return $recommendations;
    }

    private function assetStatusColumnExists(): bool
    {
        static $hasColumn;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('assets', 'status');
        }

        return $hasColumn;
    }
}
