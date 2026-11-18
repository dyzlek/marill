<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH /api/collections/{id} (M4 renommer, M5 publique ou privée). Chaque champ est facultatif :
 * on n'envoie que ce qui change. Le droit (c'est bien la sienne) est vérifié par CollectionPolicy.
 */
class ModifierCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'est_publique' => ['sometimes', 'boolean'],
        ];
    }
}
