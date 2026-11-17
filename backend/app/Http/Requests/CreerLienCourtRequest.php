<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/liens-courts (V11). La forme seulement : l'adresse passe ensuite par F5 dans le contrôleur.
 *
 * Règle 11, côté requête : exactement une cible (adresse OU collection), et un passage seulement avec
 * une adresse. La base et le modèle LienCourt tiennent la même règle (contraintes CHECK) : ici, on le dit
 * au client avec un message sur le bon champ.
 */
class CreerLienCourtRequest extends FormRequest
{
    /** Tout le monde peut créer un lien court, visiteurs compris (décision 10 du CDC). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'adresse' => ['required_without:collection', 'prohibits:collection', 'string'],
            'bibliotheque' => ['nullable', 'string', Rule::exists('bibliotheques', 'identifiant')],
            'collection' => ['required_without:adresse', 'integer'],
            // Le passage est le texte d'une recherche ou un extrait de la page : seulement les 29 symboles
            // d'une page, au plus une page entière. Le front le retrouve dans la page avec localiser (F4).
            'passage' => ['nullable', 'prohibits:collection', 'string', 'max:3200', 'regex:/\A[a-z ,.]+\z/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'adresse.required_without' => 'Un lien court vise une adresse ou une collection.',
            'adresse.prohibits' => 'Un lien court vise une seule cible : une adresse ou une collection, pas les deux.',
            'passage.prohibits' => 'Un passage ne va qu\'avec une page, pas avec une collection.',
            'passage.regex' => 'Le passage ne contient que des lettres minuscules sans accent, des espaces, des virgules et des points.',
        ];
    }
}
