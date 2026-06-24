<?php

namespace App\Enums;

enum InspectionPhotoPosition: string
{
    case Front = 'front';
    case Back = 'back';
    case Left = 'left';
    case Right = 'right';
    case Interior = 'interior';
    case Damage = 'damage';
    case Other = 'other';
}
