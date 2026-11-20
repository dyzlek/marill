<?php

namespace App\Http\Requests;

use App\Babel\ExportCompte;
use App\Models\Annotation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * M12 : la forme du fichier d'export (le contenu JSON, envoyé tel quel dans le corps de la requête :
 * le front lit le fichier choisi par le membre et l'envoie). Le serveur vérifie le fichier contre le
 * contrat avant tout (migration, étape 3) ; les adresses elles-mêmes sont vérifiées par F5 ensuite.
 *
 * Les limites (nombre de lignes) protègent le serveur d'un fichier énorme ; choix du groupe.
 */
class ImporterCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $bibliotheque = ['required', 'string', 'max:40', 'regex:/\A[a-z0-9][a-z0-9-]*\z/'];

        return [
            'format' => ['required', Rule::in([ExportCompte::FORMAT])],
            'marque_pages' => ['present', 'array', 'max:5000'],
            'marque_pages.*.adresse' => ['required', 'string'],
            'marque_pages.*.bibliotheque' => $bibliotheque,
            'marque_pages.*.titre' => ['nullable', 'string', 'max:120'],
            'collections' => ['present', 'array', 'max:500'],
            'collections.*.nom' => ['required', 'string', 'max:120'],
            'collections.*.description' => ['nullable', 'string', 'max:2000'],
            'collections.*.pages' => ['present', 'array', 'max:500'],
            'collections.*.pages.*.adresse' => ['required', 'string'],
            'collections.*.pages.*.bibliotheque' => $bibliotheque,
            'annotations' => ['present', 'array', 'max:5000'],
            'annotations.*.adresse' => ['required', 'string'],
            'annotations.*.bibliotheque' => $bibliotheque,
            'annotations.*.contenu' => ['required', 'string', 'max:'.Annotation::LONGUEUR_MAX],
        ];
    }
}
