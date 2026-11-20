<?php

namespace App\Http\Resources;

use App\Models\Annotation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une annotation de l'auteur lui-même (M6) : avec sa visibilité et son état de modération.
 * Les annotations publiques lues par tous passent par AnnotationPubliqueResource, plus courte.
 *
 * @mixin Annotation
 */
class AnnotationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'est_publique' => $this->est_publique,
            'masquee' => $this->masque_le !== null, // règle 14 : l'auteur sait qu'elle n'est plus montrée
            'page' => new AdresseResource($this->whenLoaded('adresse')),
            'cree_le' => $this->created_at?->toIso8601String(),
            'modifiee_le' => $this->updated_at?->toIso8601String(),
        ];
    }
}
