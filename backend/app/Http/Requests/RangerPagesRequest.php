<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT /api/collections/{id}/pages (M4) : la liste complète des pages, dans le nouvel ordre.
 * Réordonner, retirer ou dédoubler une page, c'est renvoyer la liste voulue : un seul appel, et
 * jamais de trou ni de doublon de position.
 */
class RangerPagesRequest extends FormRequest
{
    /** Au-delà, une seule requête serait trop lourde (choix du groupe, le CDC ne fixe pas de limite). */
    public const MAX_PAGES = 500;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            // present : une liste vide est permise (vider la collection), un champ absent ne l'est pas.
            'pages' => ['present', 'array', 'list', 'max:'.self::MAX_PAGES],
            'pages.*.adresse' => ['required', 'string'],
            'pages.*.bibliotheque' => ['nullable', 'string', Rule::exists('bibliotheques', 'identifiant')],
        ];
    }
}
