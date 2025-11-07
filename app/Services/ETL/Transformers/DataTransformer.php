<?php

namespace App\Services\ETL\Transformers;

class DataTransformer
{
    public function transform(array $data, array $pipelineConfig): array
    {
        $transformed = $data;

        // Apply data cleansing
        if (isset($pipelineConfig['transformations']['data_cleansing'])) {
            $transformed = $this->applyDataCleansing($transformed, $pipelineConfig['transformations']['data_cleansing']);
        }

        // Apply business rules
        if (isset($pipelineConfig['transformations']['business_rules'])) {
            $transformed = $this->applyBusinessRules($transformed, $pipelineConfig['transformations']['business_rules']);
        }

        // Apply geographical enrichment
        if (isset($pipelineConfig['transformations']['geographical_enrichment'])) {
            $transformed = $this->applyGeographicalEnrichment($transformed, $pipelineConfig['transformations']['geographical_enrichment']);
        }

        // Add metadata
        $transformed = $this->addMetadata($transformed);

        return $transformed;
    }

    protected function applyDataCleansing(array $data, array $config): array
    {
        // Trim specified fields
        if (isset($config['trim_fields'])) {
            foreach ($config['trim_fields'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = trim($data[$field]);
                }
            }
        }

        // Standardize status
        if (isset($config['standardize_status']) && isset($data['status'])) {
            $data['status'] = $this->standardizeStatus($data['status']);
        }

        // Validate coordinates
        if (isset($config['validate_coordinates']) && $config['validate_coordinates']) {
            $data = $this->validateAndCleanCoordinates($data);
        }

        // Handle null values
        if (isset($config['handle_nulls'])) {
            foreach ($config['handle_nulls'] as $field => $defaultValue) {
                if (!isset($data[$field]) || $data[$field] === null) {
                    $data[$field] = $defaultValue;
                }
            }
        }

        return $data;
    }

    protected function applyBusinessRules(array $data, array $config): array
    {
        // Calculate delivery time
        if (isset($config['calculate_delivery_time'])) {
            $data = $this->calculateDeliveryTime($data);
        }

        // Enrich with branch data
        if (isset($config['enrich_with_branch_data'])) {
            $data = $this->enrichWithBranchData($data);
        }

        // Calculate financial metrics
        if (isset($config['calculate_financial_metrics'])) {
            $data = $this->calculateFinancialMetrics($data);
        }

        // Apply client pricing
        if (isset($config['apply_client_pricing'])) {
            $data = $this->applyClientPricing($data);
        }

        return $data;
    }

    protected function applyGeographicalEnrichment(array $data, array $config): array
    {
        // Calculate distance
        if (isset($config['calculate_distance'])) {
            $data = $this->calculateDistance($data);
        }

        // Determine service area
        if (isset($config['determine_service_area'])) {
            $data = $this->determineServiceArea($data);
        }

        return $data;
    }

    protected function standardizeStatus(string $status): string
    {
        $statusMapping = [
            'created' => 'CREATED',
            'confirmed' => 'CONFIRMED',
            'assigned' => 'ASSIGNED',
            'picked_up' => 'PICKED_UP',
            'in_transit' => 'IN_TRANSIT',
            'out_for_delivery' => 'OUT_FOR_DELIVERY',
            'delivered' => 'DELIVERED',
            'cancelled' => 'CANCELLED',
            'returned' => 'RETURNED',
            'exception' => 'EXCEPTION',
        ];

        $normalizedStatus = strtolower(str_replace(' ', '_', $status));
        return $statusMapping[$normalizedStatus] ?? strtoupper($status);
    }

    protected function validateAndCleanCoordinates(array $data): array
    {
        $latFields = ['latitude', 'origin_latitude', 'dest_latitude'];
        $lngFields = ['longitude', 'origin_longitude', 'dest_longitude'];

        foreach ($latFields as $field) {
            if (isset($data[$field])) {
                $lat = floatval($data[$field]);
                if ($lat < -90 || $lat > 90) {
                    $data[$field] = null;
                } else {
                    $data[$field] = $lat;
                }
            }
        }

        foreach ($lngFields as $field) {
            if (isset($data[$field])) {
                $lng = floatval($data[$field]);
                if ($lng < -180 || $lng > 180) {
                    $data[$field] = null;
                } else {
                    $data[$field] = $lng;
                }
            }
        }

        return $data;
    }

    protected function calculateDeliveryTime(array $data): array
    {
        // Calculate delivery duration in minutes
        if (isset($data['delivered_at']) && isset($data['picked_up_at'])) {
            $pickupTime = strtotime($data['picked_up_at']);
            $deliveryTime = strtotime($data['delivered_at']);
            
            if ($pickupTime && $deliveryTime && $deliveryTime > $pickupTime) {
                $data['delivery_duration_minutes'] = ($deliveryTime - $pickupTime) / 60;
                $data['delivery_duration_hours'] = ($deliveryTime - $pickupTime) / 3600;
            }
        }

        // Calculate scheduled delivery duration
        if (isset($data['expected_delivery_date']) && isset($data['picked_up_at'])) {
            $pickupTime = strtotime($data['picked_up_at']);
            $scheduledDelivery = strtotime($data['expected_delivery_date']);
            
            if ($pickupTime && $scheduledDelivery && $scheduledDelivery > $pickupTime) {
                $data['scheduled_delivery_duration_minutes'] = ($scheduledDelivery - $pickupTime) / 60;
            }
        }

        return $data;
    }

    protected function enrichWithBranchData(array $data): array
    {
        // Get branch information for origin and destination
        if (isset($data['origin_branch_id'])) {
            $originBranch = \DB::table('dim_branch')
                ->where('branch_id', $data['origin_branch_id'])
                ->first();
            
            if ($originBranch) {
                $data['origin_branch_name'] = $originBranch->branch_name;
                $data['origin_branch_type'] = $originBranch->branch_type;
                $data['origin_latitude'] = $originBranch->latitude;
                $data['origin_longitude'] = $originBranch->longitude;
            }
        }

        if (isset($data['dest_branch_id'])) {
            $destBranch = \DB::table('dim_branch')
                ->where('branch_id', $data['dest_branch_id'])
                ->first();
            
            if ($destBranch) {
                $data['dest_branch_name'] = $destBranch->branch_name;
                $data['dest_branch_type'] = $destBranch->branch_type;
                $data['dest_latitude'] = $destBranch->latitude;
                $data['dest_longitude'] = $destBranch->longitude;
            }
        }

        return $data;
    }

    protected function calculateFinancialMetrics(array $data): array
    {
        // Calculate margin and margin percentage
        if (isset($data['revenue']) && isset($data['total_cost'])) {
            $data['margin'] = $data['revenue'] - $data['total_cost'];
            
            if ($data['revenue'] > 0) {
                $data['margin_percentage'] = ($data['margin'] / $data['revenue']) * 100;
            }
        }

        // Calculate total cost if components are available
        if (!isset($data['total_cost']) && isset($data['shipping_charge'])) {
            $data['total_cost'] = $data['shipping_charge'];
            
            if (isset($data['fuel_surcharge'])) {
                $data['total_cost'] += $data['fuel_surcharge'];
            }
            
            if (isset($data['insurance_cost'])) {
                $data['total_cost'] += $data['insurance_cost'];
            }
        }

        return $data;
    }

    protected function applyClientPricing(array $data): array
    {
        if (!isset($data['client_id'])) {
            return $data;
        }

        // Get client pricing rules
        $clientPricing = \DB::table('dim_client')
            ->where('client_id', $data['client_id'])
            ->where('is_active', true)
            ->first();

        if ($clientPricing) {
            // Apply service level pricing
            if (isset($data['service_type']) && $clientPricing->service_level_agreement) {
                $pricingMultiplier = $this->getServiceLevelMultiplier($clientPricing->service_level_agreement);
                if ($pricingMultiplier > 1 && isset($data['base_shipping_charge'])) {
                    $data['shipping_charge'] = $data['base_shipping_charge'] * $pricingMultiplier;
                }
            }
        }

        return $data;
    }

    protected function calculateDistance(array $data): array
    {
        // Calculate distance between origin and destination using coordinates
        if (isset($data['origin_latitude'], $data['origin_longitude'], 
                  $data['dest_latitude'], $data['dest_longitude'])) {
            
            $distance = $this->haversineDistance(
                $data['origin_latitude'], $data['origin_longitude'],
                $data['dest_latitude'], $data['dest_longitude']
            );
            
            $data['distance_km'] = round($distance, 2);
        }

        return $data;
    }

    protected function determineServiceArea(array $data): array
    {
        // Determine service area based on distance and branch capabilities
        if (isset($data['distance_km']) && isset($data['origin_branch_id'])) {
            $originBranch = \DB::table('dim_branch')
                ->where('branch_id', $data['origin_branch_id'])
                ->first();

            if ($originBranch && $originBranch->service_capabilities) {
                $capabilities = json_decode($originBranch->service_capabilities, true);
                $maxDistance = $capabilities['max_delivery_distance_km'] ?? 100;
                
                $data['service_area'] = $data['distance_km'] <= $maxDistance ? 'LOCAL' : 'REGIONAL';
            }
        }

        return $data;
    }

    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    protected function getServiceLevelMultiplier(string $serviceLevel): float
    {
        $multipliers = [
            'EXPRESS' => 1.5,
            'STANDARD' => 1.0,
            'ECONOMY' => 0.8,
        ];

        return $multipliers[$serviceLevel] ?? 1.0;
    }

    protected function addMetadata(array $data): array
    {
        // Add date keys for time dimension
        if (isset($data['created_at'])) {
            $data['created_date_key'] = date('Ymd', strtotime($data['created_at']));
        }

        if (isset($data['delivered_at'])) {
            $data['delivery_date_key'] = date('Ymd', strtotime($data['delivered_at']));
        }

        if (isset($data['expected_delivery_date'])) {
            $data['scheduled_delivery_date_key'] = date('Ymd', strtotime($data['expected_delivery_date']));
        }

        if (isset($data['picked_up_at'])) {
            $data['pickup_date_key'] = date('Ymd', strtotime($data['picked_up_at']));
        }

        // Add dimension keys
        if (isset($data['client_id'])) {
            $clientKey = \DB::table('dim_client')
                ->where('client_id', $data['client_id'])
                ->where('is_active', true)
                ->value('client_key');
            
            $data['client_key'] = $clientKey;
        }

        if (isset($data['origin_branch_id'])) {
            $branchKey = \DB::table('dim_branch')
                ->where('branch_id', $data['origin_branch_id'])
                ->where('is_active', true)
                ->value('branch_key');
            
            $data['origin_branch_key'] = $branchKey;
        }

        if (isset($data['dest_branch_id'])) {
            $branchKey = \DB::table('dim_branch')
                ->where('branch_id', $data['dest_branch_id'])
                ->where('is_active', true)
                ->value('branch_key');
            
            $data['dest_branch_key'] = $branchKey;
        }

        if (isset($data['customer_id'])) {
            $customerKey = \DB::table('dim_customer')
                ->where('customer_id', $data['customer_id'])
                ->where('is_active', true)
                ->value('customer_key');
            
            $data['customer_key'] = $customerKey;
        }

        return $data;
    }
}