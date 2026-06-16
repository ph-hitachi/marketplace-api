<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the user can view the wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can update (e.g. top up) the wallet.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can set the wallet as default.
     */
    public function setDefault(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }
}
