<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdminOrAdminOrVendedor();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isSuperAdmin() || $order->seller_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isVendedor();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isSuperAdmin() || $order->seller_id === $user->id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isSuperAdmin() || $order->seller_id === $user->id;
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->isSuperAdmin();
    }
}
