<?php

namespace App\Policies;

use App\Models\Annotation;
use App\Models\User;

/**
 * Règle 9 : une annotation appartient à un membre. Lui seul la voit par /api/annotations/{id},
 * la modifie ou la supprime (403 sinon). Les annotations publiques des autres se lisent par la
 * route publique d'une page (V14), qui ne montre jamais une annotation privée ou masquée.
 * Pas d'exception pour l'admin : il masque (Filament, A4), il ne réécrit pas.
 */
class AnnotationPolicy
{
    public function view(User $user, Annotation $annotation): bool
    {
        return $annotation->user_id === $user->id;
    }

    public function update(User $user, Annotation $annotation): bool
    {
        return $annotation->user_id === $user->id;
    }

    public function delete(User $user, Annotation $annotation): bool
    {
        return $annotation->user_id === $user->id;
    }
}
