<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Exceptions\ErreurApi;
use App\Models\Reglage;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * V19 : créer un compte avec un pseudo, un email (identifiant de connexion) et un mot de passe.
 * Fortify appelle cette action sur POST /register (formulaire Blade ou JSON envoyé par Angular).
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // A8 : inscriptions fermées. Vérifié avant la validation : inutile de dire « pseudo déjà pris »
        // à quelqu'un qui ne peut de toute façon pas s'inscrire. 403 : la requête est bien formée,
        // c'est le serveur qui refuse. Code provisoire, en attendant le contrat d'API commun.
        if (! Reglage::inscriptionsOuvertes()) {
            throw new ErreurApi([[
                'code' => 'ERR_REGISTRATION_CLOSED',
                'champ' => null,
                'message' => 'Les inscriptions sont fermées sur cette bibliothèque.',
            ]]);
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        // role n'est pas passé : un compte créé par l'inscription est toujours un membre (valeur par défaut).
        return User::create([
            'pseudo' => $input['pseudo'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
