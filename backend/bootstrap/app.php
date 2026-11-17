<?php

use App\Exceptions\ErreurApi;
use Babel\Moteur\Erreur\ErreurMoteur;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum en mode SPA : une requête /api qui vient du front Angular (domaine listé dans
        // SANCTUM_STATEFUL_DOMAINS) reçoit la session et la protection CSRF, comme une page web.
        // Les autres clients (Bruno, autres bibliothèques) restent sans session : jeton Bearer.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $versJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($versJson);

        // Erreurs du moteur : traduites une fois pour toutes au format commun (versTableau() donne déjà
        // code, champ, message). Les contrôleurs n'ont pas à les attraper.
        // CleInvalide n'est pas une ErreurMoteur : elle n'est jamais traduite et reste un 500 (erreur d'installation).
        $exceptions->render(fn (ErreurMoteur $e, Request $request) => $versJson($request)
            ? ErreurApi::depuisMoteur($e)->reponse()
            : null);

        // Une erreur du client n'a rien à faire dans les logs. Un oubli chez nous (ERR_INVALID_ALPHABET), si :
        // renvoyer false arrête le journal, ne rien renvoyer le laisse écrire.
        $exceptions->report(function (ErreurMoteur $e) {
            if (ErreurApi::depuisMoteur($e)->statut() !== null) {
                return false;
            }
        });
        $exceptions->dontReport(ErreurApi::class);

        // Quota dépassé (middleware throttle) : même forme, en gardant l'en-tête Retry-After.
        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => $versJson($request)
            ? (new ErreurApi([[
                'code' => 'ERR_TOO_MANY_REQUESTS',
                'champ' => null,
                'message' => 'Trop de requêtes : réessayez plus tard.',
            ]]))->reponse($e->getHeaders())
            : null);
    })->create();
