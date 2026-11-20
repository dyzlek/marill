<?php

namespace App\Http\Requests;

use App\Models\Annotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/annotations (M6). L'adresse elle-même est vérifiée par F5 dans le contrôleur.
 */
class AnnoterRequest extends FormRequest
{
    /** Tout membre connecté peut annoter : auth:sanctum suffit (seuls les membres annotent). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'adresse' => ['required', 'string'],
            'bibliotheque' => ['nullable', 'string', Rule::exists('bibliotheques', 'identifiant')],
            // Règle 9 : au plus 2 000 caractères. « max » compte les caractères (mb_strlen), pas les octets :
            // un « é » compte pour un. required refuse aussi une annotation faite seulement d'espaces.
            'contenu' => ['required', 'string', 'max:'.Annotation::LONGUEUR_MAX],
            // Pas de valeur par défaut : publique ou privée est un choix explicite du membre (comme en base).
            'est_publique' => ['required', 'boolean'],
        ];
    }
}
