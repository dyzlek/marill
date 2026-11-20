<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangerMotDePasseRequest;
use App\Http\Requests\ModifierCompteRequest;
use App\Http\Requests\SupprimerCompteRequest;
use App\Http\Resources\UtilisateurResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * M9 : modifier son pseudo, son email ou son mot de passe. M10 : supprimer son compte (RGPD).
 *
 * Toujours « mon » compte : l'identité vient de la session ou du jeton ($request->user()), jamais d'un id
 * dans l'URL. Il n'y a donc rien à autoriser de plus : pas de Policy, on ne peut viser personne d'autre.
 */
#[Middleware('auth:sanctum')]
class CompteController extends Controller
{
    /** M9 : pseudo et/ou email (PATCH : ce qui n'est pas envoyé ne change pas). */
    public function update(ModifierCompteRequest $request): UtilisateurResource
    {
        $membre = $this->membre($request);
        $membre->update($request->validated());

        return new UtilisateurResource($membre);
    }

    /**
     * M9 : changer de mot de passe. L'ancien est demandé (current_password) : quelqu'un qui trouve
     * une session ouverte ne doit pas pouvoir s'approprier le compte. 204.
     *
     * Les autres sessions et tous les jetons sont coupés : si le mot de passe a fui, l'intrus perd l'accès.
     * La session en cours est gardée, régénérée.
     */
    public function changerMotDePasse(ChangerMotDePasseRequest $request): Response
    {
        $membre = $this->membre($request);
        $membre->update(['password' => $request->validated('password')]); // haché par le cast « hashed »

        $membre->tokens()->delete();
        if ($request->hasSession()) {
            DB::table('sessions')->where('user_id', $membre->id)->where('id', '!=', $request->session()->getId())->delete();
            $request->session()->regenerate();
        }

        return response()->noContent();
    }

    /**
     * M10 · règle 19 : supprimer mon compte. 204. Les clés étrangères en cascade effacent marque-pages,
     * collections, annotations, votes, recherches et liens courts ; les signalements restent, sans auteur
     * (nullOnDelete). Confirmation demandée (mot de passe, ou pseudo pour un compte GitHub sans mot de passe) :
     * l'action est irréversible.
     */
    public function destroy(SupprimerCompteRequest $request): Response
    {
        $membre = $this->membre($request);

        // Couper l'accès avant d'effacer : session du front et jetons des clients extérieurs.
        $membre->tokens()->delete();
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        DB::table('sessions')->where('user_id', $membre->id)->delete();

        $membre->delete();

        return response()->noContent();
    }

    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
