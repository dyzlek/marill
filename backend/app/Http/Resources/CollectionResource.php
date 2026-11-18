<?php

namespace App\Http\Resources;

use App\Models\Adresse;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une collection du membre (M4, M5). Pas de user_id : on ne voit que les siennes.
 *
 * « pages » seulement si le contrôleur les a chargées (show), « nombre_pages » seulement s'il les a
 * comptées (index) : la Resource ne lance jamais de requête cachée.
 *
 * @mixin Collection
 */
class CollectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'est_publique' => $this->est_publique,
            // Le membre doit savoir qu'un administrateur l'a masquée (règle 14), sans en voir le détail.
            'masquee' => $this->masque_le !== null,
            'nombre_pages' => $this->whenCounted('pages'),
            'pages' => $this->whenLoaded('pages', fn () => $this->pages->map(fn (Adresse $page) => [
                'position' => $page->rangement->position,
                'page' => new AdresseResource($page),
            ])),
            'cree_le' => $this->created_at?->toIso8601String(),
            'modifiee_le' => $this->updated_at?->toIso8601String(),
        ];
    }
}
