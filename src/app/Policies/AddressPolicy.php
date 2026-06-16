<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    /**
     * Customer can manage only their own addresses.
     */
    public function view(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }

    public function update(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }

    public function delete(User $user, Address $address): bool
    {
        if ($user->id !== $address->user_id) {
            return false;
        }

        // Cannot delete an address that is linked to existing orders
        return ! $address->orders()->exists();
    }

    public function setDefault(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }
}
