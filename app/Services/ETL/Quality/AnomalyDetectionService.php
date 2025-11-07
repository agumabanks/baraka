<?php

namespace App\Services\ETL\Quality;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionService
{
    protected $severityThreshold = 0.8;
    protected $statisticalMethods = ['z_score', 'iqr', 'isolation_forest', 'pattern_matching'];
    
    public function detectAnomalies(string $tableName, array $data, string $batchId): array
    {
        $anomalies = [];
        
        foreach ($this->statisticalMethods as $method) {
            try {
                $methodAnomalies = $this->{"detectWith" . ucfirst($method)}($tableName, $data, $batchId);
                $anomalies = array_merge($anomalies, $methodAnomalies);
            } catch (\Exception $e) {
                Log::error("Anomaly detection method failed: {$method}", [
                    'table' => $tableName,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Detect business rule anomalies
        $businessAnomalies = $this->detectBusinessRuleAnomalies($tableName, $data, $batchId);
        $anomalies = array_merge($anomalies, $businessAnomalies);
        
        // Store anomalies in database
        $this->storeAnomalies($anomalies);
        
        Log::info("Anomaly detection completed", [
            'table' => $tableName,
            'total_records' => count($data),
            'anomalies_found' => count($anomalies),
            'batch_id' => $batchId
        ]);
        
        return $anomalies;
    }
    
    protected function detectWithZScore(string $tableName, array $data, string $batchId): array
    {
        $anomalies = [];
        $numericFields = $this->getNumericFields($tableName);
        
        foreach ($numericFields as $field) {
            $values = array_column($data, $field);
            $values = array_filter($values, 'is_numeric');
            
            if (count($values) < 10) continue;
            
            $mean = array_sum($values) / count($values);
            $stdDev = $this->calculateStandardDeviation($values, $mean);
            
            if ($stdDev === 0) continue;
            
            foreach ($data as $index => $record) {
                if (!isset($record[$field]) || !is_numeric($record[$field])) continue;
                
                $zScore = abs(($record[$field] - $mean) / $stdDev);
                
                if ($zScore > 3) { // 3-sigma rule
                    $anomalies[] = [
                        'table_name' => $tableName,
                        'record_id' => $index,
                        'anomaly_type' => 'statistical',
                        'anomaly_category' => 'z_score_outlier',
                        'description' => "Z-score outlier detected for field '{$field}'",
                        'severity_score' => min(1.0, $zScore / 4.0), // Normalize to 0-1
                        'detection_method' => 'z_score',
                        'anomaly_data' => [
                            'field' => $field,
                            'value' => $record[$field],
                            'z_score' => $zScore,
                            'expected_range' => [$mean - 3*$stdDev, $mean + 3*$stdDev],
                            'mean' => $mean,
                            'std_dev' => $stdDev
                        ],
                        'batch_id' => $batchId,
                        'status' => 'DETECTED'
                    ];
                }
            }
        }
        
        return $anomalies;
    }
    
    protected function detectWithIqr(string $tableName, array $data, string $batchId): array
    {
        $anomalies = [];
        $numericFields = $this->getNumericFields($tableName);
        
        foreach ($numericFields as $field) {
            $values = array_column($data, $field);
            $values = array_filter($values, 'is_numeric');
            sort($values);
            
            if (count($values) < 10) continue;
            
            $q1 = $this->calculatePercentile($values, 0.25);
            $q3 = $this->calculatePercentile($values, 0.75);
            $iqr = $q3 - $q1;
            
            $lowerFence = $q1 - 1.5 * $iqr;
            $upperFence = $q3 + 1.5 * $iqr;
            
            foreach ($data as $index => $record) {
                if (!isset($record[$field]) || !is_numeric($record[$field])) continue;
                
                $value = $record[$field];
                
                if ($value < $lowerFence || $value > $upperFence) {
                    $severity = $value < ($q1 - 3*$iqr) || $value > ($q3 + 3*$iqr) ? 'HIGH' : 'MEDIUM';
                    
                    $anomalies[] = [
                        'table_name' => $tableName,
                        'record_id' => $index,
                        'anomaly_type' => 'statistical',
                        'anomaly_category' => 'iqr_outlier',
                        'description' => "IQR outlier detected for field '{$field}'",
                        'severity_score' => $severity === 'HIGH' ? 0.9 : 0.6,
                        'detection_method' => 'iqr',
                        'anomaly_data' => [
                            'field' => $field,
                            'value' => $value,
                            'q1' => $q1,
                            'q3' => $q3,
                            'iqr' => $iqr,
                            'lower_fence' => $lowerFence,
                            'upper_fence' => $upperFence,
                            'is_extreme' => $severity === 'HIGH'
                        ],
                        'batch_id' => $batchId,
                        'status' => 'DETECTED'
                    ];
                }
            }
        }
        
        return $anomalies;
    }
    
    protected function detectWithIsolationForest(string $tableName, array $data, string $batchId): array
    {
        // Simplified isolation forest implementation
        // In production, consider using a machine learning library
        
        $anomalies = [];
        $numericFields = $this->getNumericFields($tableName);
        
        if (count($numericFields) < 2) return $anomalies;
        
        // Create feature vectors
        $featureVectors = [];
        foreach ($data as $index => $record) {
            $vector = [];
            foreach ($numericFields as $field) {
                $vector[] = is_numeric($record[$field] ?? null) ? (float)$record[$field] : 0.0;
            }
            $featureVectors[$index] = $vector;
        }
        
        // Calculate isolation scores (simplified)
        $scores = $this->calculateIsolationScores($featureVectors);
        
        foreach ($scores as $index => $score) {
            if ($score < 0.3) { // Low scores indicate anomalies
                $anomalies[] = [
                    'table_name' => $tableName,
                    'record_id' => $index,
                    'anomaly_type' => 'pattern',
                    'anomaly_category' => 'isolation_forest',
                    'description' => 'Isolation forest detected unusual data pattern',
                    'severity_score' => 1.0 - $score, // Invert score
                    'detection_method' => 'isolation_forest',
                    'anomaly_data' => [
                        'isolation_score' => $score,
                        'feature_vector' => $featureVectors[$index]
                    ],
                    'batch_id' => $batchId,
                    'status' => 'DETECTED'
                ];
            }
        }
        
        return $anomalies;
    }
    
    protected function detectWithPatternMatching(string $tableName, array $data, string $batchId): array
    {
        $anomalies = [];
        
        // Detect unusual patterns in text fields
        $textFields = $this->getTextFields($tableName);
        
        foreach ($textFields as $field) {
            foreach ($data as $index => $record) {
                if (isset($record[$field]) && is_string($record[$field])) {
                    $text = $record[$field];
                    
                    // Check for unusual characters
                    if ($this->containsUnusualCharacters($text)) {
                        $anomalies[] = [
                            'table_name' => $tableName,
                            'record_id' => $index,
                            'anomaly_type' => 'pattern',
                            'anomaly_category' => 'unusual_characters',
                            'description' => "Unusual characters detected in field '{$field}'",
                            'severity_score' => 0.4,
                            'detection_method' => 'pattern_matching',
                            'anomaly_data' => [
                                'field' => $field,
                                'text' => $text,
                                'unusual_chars' => $this->findUnusualCharacters($text)
                            ],
                            'batch_id' => $batchId,
                            'status' => 'DETECTED'
                        ];
                    }
                    
                    // Check for unusual length
                    $avgLength = $this->getAverageFieldLength($tableName, $field);
                    if (strlen($text) > $avgLength * 3 || strlen($text) < $avgLength * 0.1) {
                        $anomalies[] = [
                            'table_name' => $tableName,
                            'record_id' => $index,
                            'anomaly_type' => 'pattern',
                            'anomaly_category' => 'unusual_length',
                            'description' => "Unusual length detected in field '{$field}'",
                            'severity_score' => 0.3,
                            'detection_method' => 'pattern_matching',
                            'anomaly_data' => [
                                'field' => $field,
                                'length' => strlen($text),
                                'average_length' => $avgLength
                            ],
                            'batch_id' => $batchId,
                            'status' => 'DETECTED'
                        ];
                    }
                }
            }
        }
        
        return $anomalies;
    }
    
    protected function detectBusinessRuleAnomalies(string $tableName, array $data, string $batchId): array
    {
        $anomalies = [];
        
        // Business rules for shipments data
        if ($tableName === 'fact_shipments') {
            $anomalies = array_merge($anomalies, $this->detectShipmentAnomalies($data, $batchId));
        }
        
        // Add more business rule detections as needed
        if ($tableName === 'fact_financial_transactions') {
            $anomalies = array_merge($anomalies, $this->detectFinancialAnomalies($data, $batchId));
        }
        
        return $anomalies;
    }
    
    protected function detectShipmentAnomalies(array $data, string $batchId): array
    {
        $anomalies = [];
        
        foreach ($data as $index => $record) {
            // Detect impossible delivery times
            $duration = $record['delivery_duration_minutes'] ?? null;
            $distance = $record['distance_km'] ?? null;
            
            if ($duration && $distance && $duration < 5 && $distance > 100) {
                $anomalies[] = [
                    'table_name' => 'fact_shipments',
                    'record_id' => $index,
                    'anomaly_type' => 'business_rule',
                    'anomaly_category' => 'delivery_time_distance_mismatch',
                    'description' => "Delivery time ($duration min) too short for distance ($distance km)",
                    'severity_score' => 0.9,
                    'detection_method' => 'business_rule',
                    'anomaly_data' => [
                        'delivery_duration_minutes' => $duration,
                        'distance_km' => $distance,
                        'expected_min_time' => max(5, $distance * 2) // At least 2 min per km
                    ],
                    'batch_id' => $batchId,
                    'status' => 'DETECTED'
                ];
            }
            
            // Detect unusual margin patterns
            $margin = $record['margin_percentage'] ?? null;
            $clientTier = $record['client_tier'] ?? null;
            
            if ($margin && $clientTier) {
                $expectedMargins = $this->getExpectedMarginsByClientTier($clientTier);
                if ($margin < $expectedMargins['min'] || $margin > $expectedMargins['max']) {
                    $anomalies[] = [
                        'table_name' => 'fact_shipments',
                        'record_id' => $index,
                        'anomaly_type' => 'business_rule',
                        'anomaly_category' => 'margin_outside_expected_range',
                        'description' => "Margin {$margin}% outside expected range for {$clientTier} client",
                        'severity_score' => 0.7,
                        'detection_method' => 'business_rule',
                        'anomaly_data' => [
                            'client_tier' => $clientTier,
                            'actual_margin' => $margin,
                            'expected_range' => $expectedMargins
                        ],
                        'batch_id' => $batchId,
                        'status' => 'DETECTED'
                    ];
                }
            }
        }
        
        return $anomalies;
    }
    
    protected function detectFinancialAnomalies(array $data, string $batchId): array
    {
        $anomalies = [];
        
        foreach ($data as $index => $record) {
            // Detect large round numbers (potential data entry errors)
            $amount = $record['debit_amount'] ?? $record['credit_amount'] ?? 0;
            
            if ($amount > 0 && $this->isRoundNumber($amount) && $amount > 10000) {
                $anomalies[] = [
                    'table_name' => 'fact_financial_transactions',
                    'record_id' => $index,
                    'anomaly_type' => 'business_rule',
                    'anomaly_category' => 'suspicious_round_amount',
                    'description' => "Large round amount detected: $amount",
                    'severity_score' => 0.5,
                    'detection_method' => 'business_rule',
                    'anomaly_data' => [
                        'amount' => $amount,
                        'transaction_type' => $record['transaction_type'] ?? 'unknown'
                    ],
                    'batch_id' => $batchId,
                    'status' => 'DETECTED'
                ];
            }
        }
        
        return $anomalies;
    }
    
    protected function getNumericFields(string $tableName): array
    {
        $numericFields = [];
        
        switch ($tableName) {
            case 'fact_shipments':
                $numericFields = [
                    'declared_value', 'shipping_charge', 'cod_amount', 'fuel_surcharge',
                    'insurance_cost', 'total_cost', 'revenue', 'margin', 'margin_percentage',
                    'weight_kg', 'distance_km', 'delivery_attempts', 'delivery_duration_minutes'
                ];
                break;
            case 'fact_financial_transactions':
                $numericFields = ['debit_amount', 'credit_amount', 'running_balance'];
                break;
            case 'fact_performance_metrics':
                $numericFields = [
                    'total_shipments', 'delivered_shipments', 'returned_shipments',
                    'on_time_delivery_rate', 'first_attempt_success_rate', 'average_delivery_time_hours',
                    'total_revenue', 'total_cost', 'total_margin', 'margin_percentage'
                ];
                break;
        }
        
        return $numericFields;
    }
    
    protected function getTextFields(string $tableName): array
    {
        $textFields = [];
        
        switch ($tableName) {
            case 'fact_shipments':
                $textFields = ['tracking_number', 'status', 'exception_reason'];
                break;
            case 'dim_customer':
                $textFields = ['first_name', 'last_name', 'full_name', 'email', 'phone'];
                break;
        }
        
        return $textFields;
    }
    
    protected function calculateStandardDeviation(array $values, float $mean): float
    {
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        $variance = array_sum($squaredDiffs) / count($values);
        return sqrt($variance);
    }
    
    protected function calculatePercentile(array $sortedValues, float $percentile): float
    {
        $count = count($sortedValues);
        $index = $percentile * ($count - 1);
        
        if (is_float($index)) {
            $lower = floor($index);
            $upper = ceil($index);
            return ($sortedValues[$lower] + $sortedValues[$upper]) / 2;
        }
        
        return $sortedValues[$index];
    }
    
    protected function calculateIsolationScores(array $featureVectors): array
    {
        // Simplified isolation forest scoring
        $scores = [];
        
        foreach ($featureVectors as $index => $vector) {
            $score = 0.0;
            
            // Calculate isolation score based on feature distribution
            foreach ($vector as $featureIndex => $value) {
                $otherValues = array_column($featureVectors, $featureIndex);
                $sorted = sort($otherValues);
                
                $rank = $this->getValueRank($value, $otherValues);
                $isolationScore = abs($rank - count($otherValues) / 2) / (count($otherValues) / 2);
                $score += $isolationScore;
            }
            
            $scores[$index] = $score / count($vector);
        }
        
        return $scores;
    }
    
    protected function getValueRank($value, array $array): int
    {
        $rank = 1;
        foreach ($array as $item) {
            if ($item < $value) $rank++;
        }
        return $rank;
    }
    
    protected function containsUnusualCharacters(string $text): bool
    {
        return preg_match('/[^\x20-\x7E\xA0-\xFF]/', $text) === 1;
    }
    
    protected function findUnusualCharacters(string $text): array
    {
        $unusual = [];
        $chars = str_split($text);
        
        foreach ($chars as $char) {
            if (ord($char) > 127 && ord($char) < 160) {
                $unusual[] = $char;
            }
        }
        
        return array_unique($unusual);
    }
    
    protected function getAverageFieldLength(string $tableName, string $field): float
    {
        // In production, this would query the database for average lengths
        // For now, return default values
        $defaults = [
            'first_name' => 10,
            'last_name' => 12,
            'tracking_number' => 12,
            'email' => 25
        ];
        
        return $defaults[$field] ?? 20;
    }
    
    protected function getExpectedMarginsByClientTier(string $tier): array
    {
        return [
            'ENTERPRISE' => ['min' => 15, 'max' => 35],
            'STANDARD' => ['min' => 25, 'max' => 50],
            'BASIC' => ['min' => 35, 'max' => 65],
        ];
    }
    
    protected function isRoundNumber(float $amount): bool
    {
        return $amount % 100 === 0;
    }
    
    protected function storeAnomalies(array $anomalies): void
    {
        foreach ($anomalies as $anomaly) {
            DB::table('etl_anomaly_detection')->insert($anomaly);
        }
    }
}