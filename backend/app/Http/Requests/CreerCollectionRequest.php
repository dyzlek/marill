<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/collections (M4, M5). Une collection naît vide ; les pages s'ajoutent ensuite.
 */
class CreerCollectionRequest extends FormRequest
{
    /** Tout membre connecté peut créer une collection : auth:sanctum suffit. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:120'], // COLLECTION.nom texte(120)
            // Le MLD ne fixe pas de taille : 2 000, comme une annotation (choix du groupe).
            'description' => ['nullable', 'string', 'max:2000'],
            'est_publique' => ['sometimes', 'boolean'], // privée par défaut (M5)
        ];
    }
}
