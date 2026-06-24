<?php

namespace App\Enums;

enum DeliveryRequestType: string
{
    case PickupPoint = 'pickup_point';
    case Home = 'home';
    case Office = 'office';
    case Airport = 'airport';
    case Hotel = 'hotel';
    case Custom = 'custom';
}
