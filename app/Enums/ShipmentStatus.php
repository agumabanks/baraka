<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case CREATED = 'CREATED';
    case HANDED_OVER = 'HANDED_OVER';
    case ARRIVE = 'ARRIVE';
    case SORT = 'SORT';
    case LOAD = 'LOAD';
    case DEPART = 'DEPART';
    case IN_TRANSIT = 'IN_TRANSIT';
    case CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    case CUSTOMS_CLEARED = 'CUSTOMS_CLEARED';
    case ARRIVE_DEST = 'ARRIVE_DEST';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case RETURN_TO_SENDER = 'RETURN_TO_SENDER';
    case DAMAGED = 'DAMAGED';
}
