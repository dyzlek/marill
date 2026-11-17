<?php

namespace App\Http\Resources;

use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un signalement, vu par celui qui l'a fait : ce qu'il vise (pour que le front le cache, V18) et quand.
 * Ni le statut ni l'administrateur qui a traité : V18 cache le contenu « quelle que soit la décision »,
 * la décision reste à la modération.
 *
 * @mixin Signalement
 */
class SignalementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cible' => match (true) {
                $this->annotation_id !== null => ['type' => 'annotation', 'id' => $this->annotation_id],
                $this->collection_id !== null => ['type' => 'collection', 'id' => $this->collection_id],
                default => ['type' => 'lien_court', 'slug' => $this->whenLoaded('lienCourt', fn () => $this->lienCourt?->slug)],
            },
            'motif' => $this->motif,
            'cree_le' => $this->created_at?->toIso8601String(),
        ];
    }
}
