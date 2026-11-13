<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Rider = 'rider';
    case Passenger = 'passenger';
}
