<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

/**
 * Autorisation des collections du membre (M4, M5). auth:sanctum dit QUI (401), cette Policy dit
 * s'il a le DROIT (403).
 *
 * Ici, seul l'auteur voit, modifie et supprime sa collection, publique ou non. La consultation des
 * collections publiques par les autres (V13, B3) passera par des routes à part (issue #27), qui ne
 * montreront jamais une collection privée ou masquée.
 */
class CollectionPolicy
{
    public function view(User $user, Collection $collection): bool
    {
        return $collection->user_id === $user->id;
    }

    public function update(User $user, Collection $collection): bool
    {
        return $collection->user_id === $user->id;
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $collection->user_id === $user->id;
    }
}
