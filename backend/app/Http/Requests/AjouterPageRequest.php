<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/collections/{id}/pages (M4, M13) : ajouter une page à la fin. Même forme qu'un marque-page ;
 * l'adresse elle-même est vérifiée par F5 dans le contrôleur (codes ERR_… du cahier des charges).
 */
class AjouterPageRequest extends FormRequest
{
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
        ];
    }
}
