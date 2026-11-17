<?php

namespace App\Http\Resources;

use App\Models\Adresse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une adresse enregistrée, avec sa bibliothèque : une page d'ailleurs se manipule comme une page d'ici
 * (M13), le front sait où la lire grâce à « bibliotheque ». Jamais le texte : il se lit par GET /api/pages.
 *
 * @mixin Adresse
 */
class AdresseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'adresse' => $this->ecriture(),
            // whenLoaded : la Resource ne lance jamais de requête cachée ; au contrôleur de charger la relation.
            'bibliotheque' => $this->whenLoaded('bibliotheque', fn () => $this->bibliotheque->identifiant),
            'locale' => $this->whenLoaded('bibliotheque', fn () => $this->bibliotheque->est_locale),
        ];
    }
}
