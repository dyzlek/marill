<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * M1 : le compte qui correspond à cet email et à ce mot de passe, ou null.
 *
 * Une seule vérification pour les deux portes d'entrée : la connexion par session (Fortify, front
 * Angular) et la création de jeton (POST /api/tokens, clients extérieurs). Chacune décide ensuite
 * quoi dire d'un compte suspendu.
 */
final class VerifierIdentifiants
{
    public function __invoke(string $email, string $motDePasse): ?User
    {
        // L'email est rangé en minuscules (config fortify.lowercase_usernames) : on cherche pareil.
        $user = User::query()->where('email', Str::lower($email))->first();

        // Hash::check compare au hachage stocké ; on ne lit jamais le mot de passe en clair.
        // Utilisateur inconnu ou mauvais mot de passe : même réponse (null), pour ne pas révéler
        // quels emails ont un compte.
        // password nul : compte venu par GitHub, sans mot de passe (issue #4).
        if ($user === null || $user->password === null || ! Hash::check($motDePasse, $user->password)) {
            return null;
        }

        return $user;
    }
}
