<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RiderProfile;
use App\Models\User;

class RiderProfilePolicy
{
    public function viewDocuments(User $user, RiderProfile $profile): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function review(User $user, RiderProfile $profile): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
