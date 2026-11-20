<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * M9 : pseudo et email. Mêmes règles qu'à l'inscription (ProfileValidationRules, partagées avec Fortify),
 * en ignorant mon propre compte pour l'unicité, et « sometimes » : un PATCH peut n'envoyer qu'un des deux.
 * Rien d'autre n'est accepté : role, suspendu_le, github_id ne passent pas par ici.
 */
class ModifierCompteRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return true; // auth:sanctum a déjà vérifié qu'il y a un membre ; il ne modifie que lui-même
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $id = $this->user()?->id;

        return [
            'pseudo' => ['sometimes', ...$this->pseudoRules($id)],
            'email' => ['sometimes', ...$this->emailRules($id)],
        ];
    }
}
