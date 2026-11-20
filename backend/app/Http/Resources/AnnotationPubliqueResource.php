<?php

namespace App\Http\Resources;

use App\Models\Annotation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une annotation publique lue par n'importe qui (V14). Le pseudo de l'auteur, jamais son email ni
 * son id de compte. L'id de l'annotation reste : c'est lui qu'on envoie pour la signaler (V17).
 *
 * @mixin Annotation
 */
class AnnotationPubliqueResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'auteur' => $this->whenLoaded('user', fn () => $this->user->pseudo),
            'cree_le' => $this->created_at?->toIso8601String(),
            'modifiee_le' => $this->updated_at?->toIso8601String(),
        ];
    }
}
