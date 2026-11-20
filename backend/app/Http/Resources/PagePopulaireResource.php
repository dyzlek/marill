<?php

namespace App\Http\Resources;

use App\Models\Adresse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une page populaire (B4) : l'adresse et son score, des totaux anonymes.
 *
 * @mixin Adresse
 *
 * @property int|string|null $votes_sum_valeur
 * @property int $marque_pages_count
 */
class PagePopulaireResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $votes = (int) $this->votes_sum_valeur;

        return [
            ...(new AdresseResource($this->resource))->toArray($request),
            'votes' => $votes,
            'marque_pages' => $this->marque_pages_count,
            'score' => $votes + $this->marque_pages_count,
        ];
    }
}
