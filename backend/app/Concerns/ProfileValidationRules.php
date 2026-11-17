<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, Unique|ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'pseudo' => $this->pseudoRules($userId),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Pseudo du MLD (UTILISATEUR.pseudo) : 50 caractères au plus, unique.
     * La règle unique doublonne l'index de la base pour donner un message d'erreur lisible au lieu d'un 500.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function pseudoRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'max:50',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
