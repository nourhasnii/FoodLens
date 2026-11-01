<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Contrôle d'accès serveur aux produits du back-office.
 *
 * - `manage_products` : accès de base au module produits (producteur/distributeur/admin).
 * - `manage_all_products` : lève la restriction d'appartenance (distributeur/admin).
 *
 * Un producteur ne gère donc que les produits dont il est le producteur.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_products');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->manages($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_products');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->manages($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->manages($user, $product);
    }

    private function manages(User $user, Product $product): bool
    {
        if (! $user->can('manage_products')) {
            return false;
        }

        return $user->can('manage_all_products')
            || $product->producer_id === $user->getKey();
    }
}
