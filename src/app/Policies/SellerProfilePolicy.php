<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SellerProfile;

class SellerProfilePolicy
{
    /**
     * Determine whether the user can update the seller profile.
     */
    public function update(User $user, SellerProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
