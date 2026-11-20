<?php

namespace App\Http\Resources;

use App\Models\Recherche;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une recherche de mon historique (M8). Pas d'adresse : elle n'est pas stockée (règle 12).
 *
 * @mixin Recherche
 */
class RecherchePasseeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'texte' => $this->texte,
            'mode' => $this->mode->value,
            'portee' => $this->portee->value,
            'cree_le' => $this->created_at?->toIso8601String(),
        ];
    }
}
