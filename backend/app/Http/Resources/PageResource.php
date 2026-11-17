<?php

namespace App\Http\Resources;

use App\Babel\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forme de la réponse « page » : c'est le contrat. Les autres bibliothèques lisent ces champs (B1) :
 * renommer un champ ici casse le réseau. À aligner sur le contrat d'API commun dès sa publication.
 *
 * @property-read Page $resource
 */
class PageResource extends JsonResource
{
    /** @return array{adresse: string, texte: string} */
    public function toArray(Request $request): array
    {
        return [
            'adresse' => $this->resource->adresse,
            'texte' => $this->resource->texte,
        ];
    }
}
