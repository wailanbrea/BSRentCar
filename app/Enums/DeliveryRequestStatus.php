<?php

namespace App\Enums;

enum DeliveryRequestStatus: string
{
    case Requested = 'requested';
    case Assigned = 'assigned';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
