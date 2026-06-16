<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Customer can view their own order.
     * Admin can view any order.
     */
    public function viewAsCustomer(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id;
    }

    /**
     * Customer can cancel their own order.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id;
    }

    /**
     * Customer can confirm their own order.
     */
    public function confirm(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id;
    }

    /**
     * Seller can view/update their own order.
     */
    public function viewAsSeller(User $user, Order $order): bool
    {
        return $user->id === $order->seller_id;
    }

    /**
     * Seller can update status of their own order.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->id === $order->seller_id;
    }

    /**
     * Seller can cancel their own order.
     */
    public function cancelAsSeller(User $user, Order $order): bool
    {
        return $user->id === $order->seller_id;
    }
}

