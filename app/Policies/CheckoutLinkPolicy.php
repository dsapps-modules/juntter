<?php

namespace App\Policies;

use App\Models\CheckoutLink;
use App\Models\User;

class CheckoutLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdminOrAdminOrVendedor();
    }

    public function view(User $user, CheckoutLink $checkoutLink): bool
    {
        return $user->isSuperAdmin() || $checkoutLink->seller_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isVendedor();
    }

    public function update(User $user, CheckoutLink $checkoutLink): bool
    {
        return $user->isSuperAdmin() || $checkoutLink->seller_id === $user->id;
    }

    public function delete(User $user, CheckoutLink $checkoutLink): bool
    {
        return $user->isSuperAdmin() || $checkoutLink->seller_id === $user->id;
    }

    public function restore(User $user, CheckoutLink $checkoutLink): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, CheckoutLink $checkoutLink): bool
    {
        return $user->isSuperAdmin();
    }
}
