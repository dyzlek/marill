<?php

namespace App\Http\Middleware;

use App\Exceptions\ErreurApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A6 · B5 : la seule route de l'API publique qui exige le jeton réseau (« Authorization: Bearer <jeton> »).
 *
 * Le jeton est commun aux dix bibliothèques et gardé dans la configuration du serveur (BABEL_JETON_RESEAU
 * dans le .env), jamais en base ni dans le dépôt. Ce n'est pas un jeton Sanctum : il ne désigne pas un
 * membre, il prouve seulement qu'on fait partie du réseau.
 */
class ExigerJetonReseau
{
    public function handle(Request $request, Closure $next): Response
    {
        $attendu = config('bibliotheque.jeton_reseau');
        $recu = $request->bearerToken();

        // hash_equals : comparaison en temps constant. Avec ===, le temps de réponse trahirait combien de
        // caractères du début sont justes, et on pourrait deviner le jeton petit à petit.
        // Jeton non configuré chez nous : personne n'entre (on ne compare jamais à une chaîne vide).
        if (! is_string($attendu) || $attendu === '' || ! is_string($recu) || ! hash_equals($attendu, $recu)) {
            throw new ErreurApi([[
                'code' => 'ERR_INVALID_NETWORK_TOKEN',
                'champ' => null,
                'message' => 'Cette route est réservée aux bibliothèques du réseau.',
            ]]);
        }

        return $next($request);
    }
}
