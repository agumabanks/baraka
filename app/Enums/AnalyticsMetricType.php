<?php

namespace App\Enums;

enum AnalyticsMetricType: string
{
    case THROUGHPUT = 'throughput';
    case LATENCY = 'latency';
    case ERROR_RATE = 'error_rate';
    case CAPACITY_UTILIZATION = 'capacity_utilization';
    case BRANCH_EFFICIENCY = 'branch_efficiency';
    case SHIPMENT_VELOCITY = 'shipment_velocity';
    case SCAN_ACCURACY = 'scan_accuracy';
    case DELIVERY_COMPLIANCE = 'delivery_compliance';

    public function label(): string
    {
        return match ($this) {
            self::THROUGHPUT => 'Throughput (shipments/hour)',
            self::LATENCY => 'Latency (seconds)',
            self::ERROR_RATE => 'Error Rate (%)',
            self::CAPACITY_UTILIZATION => 'Capacity Utilization (%)',
            self::BRANCH_EFFICIENCY => 'Branch Efficiency Score',
            self::SHIPMENT_VELOCITY => 'Shipment Velocity (km/hour)',
            self::SCAN_ACCURACY => 'Scan Accuracy (%)',
            self::DELIVERY_COMPLIANCE => 'Delivery Compliance (%)',
        };
    }

    public function threshold(): array
    {
        return match ($this) {
            self::THROUGHPUT => ['warning' => 50, 'critical' => 25],
            self::LATENCY => ['warning' => 5000, 'critical' => 10000],
            self::ERROR_RATE => ['warning' => 1, 'critical' => 5],
            self::CAPACITY_UTILIZATION => ['warning' => 80, 'critical' => 95],
            self::BRANCH_EFFICIENCY => ['warning' => 60, 'critical' => 40],
            self::SHIPMENT_VELOCITY => ['warning' => 30, 'critical' => 15],
            self::SCAN_ACCURACY => ['warning' => 98, 'critical' => 95],
            self::DELIVERY_COMPLIANCE => ['warning' => 95, 'critical' => 90],
        };
    }
}
