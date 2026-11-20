<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** M7 : la valeur vaut +1 ou −1 (règle 10), rien d'autre. La bibliothèque est facultative (M13). */
class VoterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'valeur' => ['required', 'integer', Rule::in([1, -1])],
            'bibliotheque' => ['sometimes', 'nullable', 'string', 'exists:bibliotheques,identifiant'],
        ];
    }
}
