<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/tokens : un client extérieur (Bruno, script) échange email et mot de passe contre un jeton.
 * Pas de vérification du compte ici : seulement la forme. Les erreurs de forme gardent pour l'instant le
 * format de Laravel (le contrat d'API commun dira s'il faut les traduire).
 */
class CreerJetonRequest extends FormRequest
{
    /** Route publique : tout le monde peut demander un jeton, c'est le mot de passe qui décide. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // Le nom du jeton, pour que le membre reconnaisse plus tard où il l'utilise (« bruno », « script »).
            'nom' => ['required', 'string', 'max:100'],
        ];
    }
}
