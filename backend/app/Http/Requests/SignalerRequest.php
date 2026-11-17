<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/signalements (V17). Règle 13 : exactement un contenu visé, une annotation, une collection ou
 * un lien court. Jamais une page (on ne masque jamais une page : un texte choquant se bannit, A5).
 * Le lien court se désigne par son slug : c'est tout ce qu'un visiteur en connaît.
 */
class SignalerRequest extends FormRequest
{
    /** Tout le monde peut signaler, visiteurs compris. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'annotation' => ['required_without_all:collection,lien_court', 'prohibits:collection,lien_court', 'integer'],
            'collection' => ['required_without_all:annotation,lien_court', 'prohibits:annotation,lien_court', 'integer'],
            'lien_court' => ['required_without_all:annotation,collection', 'prohibits:annotation,collection', 'string'],
            'motif' => ['required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $uneSeuleCible = 'Un signalement vise un seul contenu : une annotation, une collection ou un lien court.';

        return [
            'annotation.required_without_all' => $uneSeuleCible,
            'collection.required_without_all' => $uneSeuleCible,
            'lien_court.required_without_all' => $uneSeuleCible,
            'annotation.prohibits' => $uneSeuleCible,
            'collection.prohibits' => $uneSeuleCible,
            'lien_court.prohibits' => $uneSeuleCible,
        ];
    }
}
