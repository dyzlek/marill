<?php

namespace App\Http\Resources;

use App\Models\Adresse;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une collection publique vue par un visiteur ou une autre bibliothèque (V13, B3). À la différence de
 * CollectionResource (le membre et ses propres collections) : le pseudo de l'auteur, jamais son email ni son
 * id, et ni est_publique ni masquee (une collection montrée ici est forcément publique et visible).
 *
 * @mixin Collection
 */
class CollectionPubliqueResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'auteur' => $this->whenLoaded('user', fn () => $this->user->pseudo),
            'nombre_pages' => $this->whenCounted('pages'),
            'pages' => $this->whenLoaded('pages', fn () => $this->pages->map(fn (Adresse $page) => [
                'position' => $page->rangement->position,
                'page' => new AdresseResource($page),
            ])),
            'modifiee_le' => $this->updated_at?->toIso8601String(),
        ];
    }
}
