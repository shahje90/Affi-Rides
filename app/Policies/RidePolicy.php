<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ride;
use App\Models\User;

class RidePolicy
{
    public function viewAsPassenger(User $user, Ride $ride): bool
    {
        return $user->role === UserRole::Passenger && $ride->passenger_id === $user->id;
    }

    public function viewAsRider(User $user, Ride $ride): bool
    {
        return $user->role === UserRole::Rider && $ride->rider_id === $user->id;
    }
}
