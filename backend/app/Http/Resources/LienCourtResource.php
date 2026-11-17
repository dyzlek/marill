<?php

namespace App\Http\Resources;

use App\Models\LienCourt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un lien court (V11, V12) et ce qu'il faut pour l'ouvrir : le front n'a pas à deviner.
 * - type « page » : ouvrir `ouvrir` et y surligner `passage` (localiser, F4) s'il y en a un ;
 * - type « livre » : `page.adresse` est le livre, `ouvrir` est sa page 1 (règle 4) ;
 * - type « collection » : ouvrir la collection `collection.id`.
 * Pas d'auteur dans la réponse : le lien est public, son créateur n'a pas à l'être.
 *
 * @mixin LienCourt
 */
class LienCourtResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $adresse = $this->adresse_id !== null ? $this->adresse : null;

        return [
            'slug' => $this->slug,
            'type' => match (true) {
                $adresse === null => 'collection',
                $adresse->estUnLivre() => 'livre',
                default => 'page',
            },
            'page' => $adresse === null ? null : new AdresseResource($adresse),
            'ouvrir' => match (true) {
                $adresse === null => null,
                $adresse->estUnLivre() => $adresse->ecriture().':1',
                default => $adresse->ecriture(),
            },
            'collection' => $this->collection_id === null ? null : [
                'id' => $this->collection_id,
                'nom' => $this->whenLoaded('collection', fn () => $this->collection?->nom),
            ],
            'passage' => $this->passage,
            'visites' => $this->visites,
            'expire_le' => $this->expire_le?->toIso8601String(),
            'cree_le' => $this->created_at?->toIso8601String(),
        ];
    }
}
