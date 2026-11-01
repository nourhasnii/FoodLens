<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;

/**
 * Contrôle d'accès serveur aux lots du back-office.
 *
 * L'appartenance d'un lot est déduite du produit associé (`product.producer_id`).
 * Un producteur ne gère donc que les lots de ses propres produits.
 */
class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_products');
    }

    public function view(User $user, Batch $batch): bool
    {
        return $this->manages($user, $batch);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_products');
    }

    public function update(User $user, Batch $batch): bool
    {
        return $this->manages($user, $batch);
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $this->manages($user, $batch);
    }

    private function manages(User $user, Batch $batch): bool
    {
        if (! $user->can('manage_products')) {
            return false;
        }

        if ($user->can('manage_all_products')) {
            return true;
        }

        return $batch->product?->producer_id === $user->getKey();
    }
}
