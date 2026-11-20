<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * M10 : confirmer avant d'effacer. Avec un mot de passe : le redonner (current_password).
 * Compte GitHub sans mot de passe : retaper son pseudo, comme sur GitHub pour supprimer un dépôt.
 */
class SupprimerCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $membre = $this->user();

        return $membre?->password !== null
            ? ['current_password' => ['required', 'string', 'current_password:sanctum']]
            : ['pseudo' => ['required', 'string', Rule::in([$membre?->pseudo])]];
    }
}
