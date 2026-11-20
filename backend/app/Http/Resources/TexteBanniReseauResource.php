<?php

namespace App\Http\Resources;

use App\Models\TexteBanni;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un texte banni tel que les autres bibliothèques le relèvent (B5) : le texte normalisé (il faut le texte
 * pour le chercher chez soi, règle 16), son empreinte (pour éviter les doublons), le motif et la date.
 *
 * @mixin TexteBanni
 */
class TexteBanniReseauResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'texte' => $this->texte,
            'empreinte' => $this->empreinte,
            'motif' => $this->motif,
            'banni_le' => $this->decide_le?->toIso8601String(),
        ];
    }
}
