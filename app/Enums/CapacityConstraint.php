<?php

namespace App\Enums;

enum CapacityConstraint: string
{
    case PHYSICAL_SPACE = 'physical_space';
    case WEIGHT_LIMIT = 'weight_limit';
    case HAZMAT_RESTRICTION = 'hazmat_restriction';
    case TEMPERATURE_CONTROL = 'temperature_control';
    case VEHICLE_AVAILABILITY = 'vehicle_availability';
    case DRIVER_AVAILABILITY = 'driver_availability';
    case TIME_WINDOW = 'time_window';
    case ROUTE_CAPACITY = 'route_capacity';

    public function description(): string
    {
        return match ($this) {
            self::PHYSICAL_SPACE => 'Physical storage space in vehicle/hub',
            self::WEIGHT_LIMIT => 'Vehicle gross weight limit',
            self::HAZMAT_RESTRICTION => 'Hazardous materials restrictions',
            self::TEMPERATURE_CONTROL => 'Temperature-controlled environment requirement',
            self::VEHICLE_AVAILABILITY => 'Available vehicles',
            self::DRIVER_AVAILABILITY => 'Available drivers',
            self::TIME_WINDOW => 'Delivery time window constraints',
            self::ROUTE_CAPACITY => 'Route maximum stops or distance',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::PHYSICAL_SPACE, self::WEIGHT_LIMIT => 'high',
            self::HAZMAT_RESTRICTION, self::TEMPERATURE_CONTROL => 'high',
            self::VEHICLE_AVAILABILITY, self::DRIVER_AVAILABILITY => 'medium',
            self::TIME_WINDOW => 'medium',
            self::ROUTE_CAPACITY => 'low',
        };
    }
}
