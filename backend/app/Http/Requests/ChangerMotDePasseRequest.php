<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * M9 : nouveau mot de passe (règles de Password::defaults(), confirmé) et l'ancien.
 * Un compte créé par GitHub n'a pas de mot de passe : il n'a pas d'ancien à donner, il en choisit un.
 */
class ChangerMotDePasseRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $aUnMotDePasse = $this->user()?->password !== null;

        return [
            'current_password' => $aUnMotDePasse ? ['required', 'string', 'current_password:sanctum'] : ['prohibited'],
            'password' => $this->passwordRules(),
        ];
    }
}
