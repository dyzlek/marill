<?php

namespace App\Http\Resources;

use App\Models\MarquePage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un marque-page (M2, M3). Pas de user_id : on ne voit que les siens.
 *
 * @mixin MarquePage
 */
class MarquePageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'page' => new AdresseResource($this->whenLoaded('adresse')),
            'cree_le' => $this->created_at?->toIso8601String(),
        ];
    }
}
