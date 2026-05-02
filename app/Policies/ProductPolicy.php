<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdminOrAdminOrVendedor();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->seller_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isVendedor();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->seller_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->seller_id === $user->id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->isSuperAdmin();
    }
}
