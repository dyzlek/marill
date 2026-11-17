<?php

namespace App\Http\Requests;

use App\Exceptions\ErreurApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Entrée de la recherche : { "texte": "...", "mode": "exact" | "bruit" }.
 *
 * Ne vérifie que la présence et le type. Le reste (vide après conversion, trop long) se décide après
 * la normalisation : c'est le moteur qui répond ERR_EMPTY_QUERY ou ERR_TEXT_TOO_LONG.
 */
class ChercherTexteRequest extends FormRequest
{
    /**
     * Le moteur connaît aussi « page », mais il sert à l'import d'un compte (M12) : l'interface et le réseau
     * ne proposent que ces deux modes (V5).
     */
    public const MODES = ['exact', 'bruit'];

    /** Route publique : tout le monde peut chercher. Sans « true », Laravel répondrait 403. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // TrimStrings et ConvertEmptyStringsToNull (middlewares globaux) passent avant : "" devient null.
            'texte' => ['required', 'string'],
            'mode' => ['required', Rule::in(self::MODES)],
        ];
    }

    /**
     * Par défaut Laravel répond { message, errors } : on répond plutôt au format commun, avec les codes
     * du cahier des charges. Messages fixes : ils ne recopient jamais ce que le client a envoyé.
     */
    protected function failedValidation(Validator $validator): never
    {
        $codes = [
            'texte' => ['ERR_EMPTY_QUERY', 'Le texte à chercher est vide.'],
            'mode' => ['ERR_UNKNOWN_MODE', 'Le mode doit être « exact » ou « bruit ».'],
        ];

        $erreurs = [];
        foreach ($codes as $champ => [$code, $message]) {
            if ($validator->errors()->has($champ)) {
                $erreurs[] = ['code' => $code, 'champ' => $champ, 'message' => $message];
            }
        }

        throw new ErreurApi($erreurs);
    }
}
