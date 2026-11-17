<?php

namespace App\Http\Resources;

use App\Babel\Identite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forme de la réponse « identité » : c'est le contrat. La plateforme et les autres bibliothèques la lisent
 * (B6) et gardent l'empreinte pour repérer un changement de clé (A9) : renommer un champ casse le réseau.
 *
 * @property-read Identite $resource
 */
class IdentiteResource extends JsonResource
{
    /** @return array{identifiant: string, nom: string, description: ?string, empreinte: string} */
    public function toArray(Request $request): array
    {
        return [
            'identifiant' => $this->resource->identifiant,
            'nom' => $this->resource->nom,
            'description' => $this->resource->description,
            'empreinte' => $this->resource->empreinte,
        ];
    }
}
