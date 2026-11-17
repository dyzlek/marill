<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/marque-pages (M2, M13). La forme de la requête seulement ; l'adresse elle-même est
 * vérifiée par F5 dans le contrôleur, pour répondre avec les codes ERR_… du cahier des charges.
 */
class GarderMarquePageRequest extends FormRequest
{
    /** Tout membre connecté peut garder une page : auth:sanctum suffit, pas de règle en plus. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'adresse' => ['required', 'string'],
            // M13 : l'identifiant d'une bibliothèque du réseau (la nôtre si absent). Elle doit être connue :
            // une ligne de BIBLIOTHEQUE, remplie par l'annuaire (milestone 3).
            'bibliotheque' => ['nullable', 'string', Rule::exists('bibliotheques', 'identifiant')],
            'titre' => ['nullable', 'string', 'max:120'],
        ];
    }
}
