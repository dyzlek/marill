<?php

namespace App\Http\Requests;

use App\Models\Annotation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH /api/annotations/{id} (M6) : changer le texte et/ou la visibilité. La page annotée ne
 * change pas (on supprime et on recrée). Le droit est vérifié par AnnotationPolicy.
 */
class ModifierAnnotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'contenu' => ['sometimes', 'required', 'string', 'max:'.Annotation::LONGUEUR_MAX],
            'est_publique' => ['sometimes', 'boolean'],
        ];
    }
}
