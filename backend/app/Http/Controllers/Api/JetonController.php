<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\VerifierIdentifiants;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreerJetonRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Jetons Sanctum pour les clients extérieurs (issue #16). Notre front Angular n'en a pas besoin :
 * il utilise la session (cookie HttpOnly). Le même auth:sanctum accepte les deux.
 */
class JetonController extends Controller
{
    /**
     * POST /api/tokens. Le jeton en clair n'est montré qu'ici, une seule fois : la base n'en garde que
     * le hachage (SHA-256), comme pour un mot de passe. Perdu, il faut en créer un autre.
     */
    public function store(CreerJetonRequest $request, VerifierIdentifiants $verifier): JsonResponse
    {
        $user = $verifier($request->string('email')->toString(), $request->string('password')->toString());

        // Message volontairement vague, et le même pour tous les refus : mauvais email, mauvais mot de
        // passe ou compte suspendu. Un inconnu n'apprend pas quels emails ont un compte.
        if ($user === null || $user->suspendu_le !== null) {
            throw new ErreurApi([[
                'code' => 'ERR_INVALID_CREDENTIALS',
                'champ' => null,
                'message' => 'Identifiants incorrects.',
            ]]);
        }

        $jeton = $user->createToken($request->string('nom')->toString());

        return response()->json(['data' => ['jeton' => $jeton->plainTextToken]], 201);
    }

    /**
     * DELETE /api/tokens/courant : révoque le jeton qui a servi à cette requête.
     * Avec une session (front Angular), il n'y a pas de jeton à révoquer : on se déconnecte par POST /logout.
     */
    public function destroy(Request $request): Response
    {
        $jeton = $request->user()?->currentAccessToken();

        if ($jeton instanceof PersonalAccessToken) {
            $jeton->delete();
        }

        return response()->noContent();
    }
}
