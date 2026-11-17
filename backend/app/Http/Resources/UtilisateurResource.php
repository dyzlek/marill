<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Le compte connecté, tel que le front le voit. Jamais le modèle brut : le mot de passe haché, les
 * secrets de double authentification ou suspendu_le n'ont rien à faire dans une réponse.
 *
 * @mixin User
 */
class UtilisateurResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pseudo' => $this->pseudo,
            'email' => $this->email,
            'role' => $this->role->value,
        ];
    }
}
