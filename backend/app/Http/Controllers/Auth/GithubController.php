<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Reglage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * « Se connecter avec GitHub » (issue #4), en plus de l'email et du mot de passe (V19, M1).
 *
 * Flux par code d'autorisation : redirect() envoie le navigateur chez GitHub avec un state rangé
 * en session ; callback() vérifie ce state (Socialite le fait), échange le code contre un jeton de
 * serveur à serveur (le secret ne passe jamais par le navigateur), lit le profil, puis ouvre la
 * session Laravel. Le jeton GitHub n'est pas gardé : donnée non stockée = donnée qui ne fuit pas.
 *
 * Routes dans routes/web.php : elles ont besoin de la session pour le state. Jamais ->stateless().
 * On renvoie toujours vers config('app.frontend_url'), jamais vers une adresse lue dans la requête
 * (sinon : redirection ouverte).
 */
class GithubController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        // user:email : GitHub ne donne l'email principal que si on demande ce droit (Socialite l'ajoute déjà).
        return Socialite::driver('github')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $profil = Socialite::driver('github')->user();
        } catch (InvalidStateException $e) {
            // State absent ou différent : retour falsifié, ou session expirée entre l'aller et le retour.
            return $this->echec('ERR_OAUTH_STATE');
        } catch (Throwable $e) {
            // Refus de l'utilisateur, GitHub en panne, code déjà utilisé… Détail dans les logs, jamais à l'écran.
            report($e);

            return $this->echec('ERR_OAUTH_FAILED');
        }

        $githubId = (string) $profil->getId();
        $user = User::query()->where('github_id', $githubId)->first();

        if ($user === null) {
            $email = $profil->getEmail();

            if ($email === null) {
                return $this->echec('ERR_OAUTH_NO_EMAIL');
            }

            // Pas de rapprochement par email : nos emails ne sont jamais vérifiés (vérification écartée
            // par le CDC), donc rien ne prouve que le compte local appartient à la personne GitHub.
            // On refuse, et le membre se connecte avec son mot de passe.
            if (User::query()->where('email', Str::lower($email))->exists()) {
                return $this->echec('ERR_OAUTH_EMAIL_TAKEN');
            }

            // Même règle que l'inscription par formulaire (A8).
            if (! Reglage::inscriptionsOuvertes()) {
                return $this->echec('ERR_REGISTRATION_CLOSED');
            }

            $user = new User;
            $user->pseudo = $this->pseudoLibre((string) ($profil->getNickname() ?? 'membre'));
            $user->email = Str::lower($email);
            // Colonne sensible, affectée à la main, jamais par #[Fillable].
            $user->github_id = $githubId;
            $user->save();
        }

        // Même règle que la connexion par mot de passe (VerifierIdentifiants / authenticateUsing).
        if ($user->suspendu_le !== null) {
            return $this->echec('ERR_ACCOUNT_SUSPENDED');
        }

        // Login déclenche l'événement Login : NoterDerniereConnexion met à jour derniere_connexion_le.
        Auth::guard('web')->login($user);
        // Nouvel identifiant de session après la connexion (fixation de session).
        $request->session()->regenerate();

        return redirect()->away((string) config('app.frontend_url'));
    }

    /**
     * Le pseudo GitHub s'il est libre, sinon suivi d'un nombre (users.pseudo est unique, 50 caractères).
     */
    private function pseudoLibre(string $souhaite): string
    {
        $base = Str::limit($souhaite, 44, '');
        $pseudo = $base;

        while (User::query()->where('pseudo', $pseudo)->exists()) {
            $pseudo = $base.'-'.random_int(1, 99999);
        }

        return $pseudo;
    }

    /**
     * Retour au front avec un code court ; le front affiche le message. Codes provisoires (contrat commun).
     */
    private function echec(string $code): RedirectResponse
    {
        return redirect()->away(config('app.frontend_url').'/connexion?erreur='.$code);
    }
}
