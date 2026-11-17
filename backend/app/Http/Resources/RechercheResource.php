<?php

namespace App\Http\Resources;

use App\Babel\ResultatRecherche;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forme de la réponse « recherche » : c'est le contrat. Les autres bibliothèques l'appellent pour une
 * recherche sur tout le réseau (B2) : renommer un champ ici casse le réseau.
 *
 * @property-read ResultatRecherche $resource
 */
class RechercheResource extends JsonResource
{
    /** @return array{texte: string, mode: string, adresse: string} */
    public function toArray(Request $request): array
    {
        return [
            'texte' => $this->resource->texte,
            'mode' => $this->resource->mode,
            'adresse' => $this->resource->adresse,
        ];
    }
}
