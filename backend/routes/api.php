<?php

use App\Http\Controllers\Api\HasardController;
use App\Http\Controllers\Api\IdentiteController;
use App\Http\Controllers\Api\JetonController;
use App\Http\Controllers\Api\LienCourtController;
use App\Http\Controllers\Api\MarquePageController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\RechercheController;
use App\Http\Controllers\Api\SignalementController;
use App\Http\Controllers\Api\UtilisateurCourantController;
use Illuminate\Support\Facades\Route;

// Toutes les routes de l'API : préfixe d'URL /api (bootstrap/app.php), groupe de middlewares « api »,
// noms préfixés « api. » pour ne pas entrer en collision avec routes/web.php. Sans session ni CSRF,
// sauf pour une requête de notre front Angular (statefulApi() dans bootstrap/app.php).
//
// Inscription, connexion et déconnexion ne sont pas ici : ce sont les routes de Fortify
// (POST /register, /login, /logout), qui répondent en JSON quand le front le demande.
Route::name('api.')->group(function () {
    // Le compte connecté (M1). auth:sanctum accepte la session du front Angular ET un jeton Bearer : 401 sinon.
    Route::get('user', UtilisateurCourantController::class)->middleware('auth:sanctum')->name('user');

    // Limites de débit : throttle:essais,minutes,préfixe. Le préfixe donne à chaque route son propre compteur.
    // Sans lui, Laravel compte par adresse IP (ou par membre) pour TOUTES les routes limitées : 5 essais de
    // jeton ratés auraient aussi bloqué la création de liens courts depuis la même adresse.

    // Jetons des clients extérieurs (Bruno, scripts). throttle : 5 essais par minute et par adresse IP,
    // contre qui essaierait des mots de passe à la chaîne (429 au-delà, ERR_TOO_MANY_REQUESTS).
    Route::post('tokens', [JetonController::class, 'store'])->middleware('throttle:5,1,jetons')->name('tokens.store');
    Route::delete('tokens/courant', [JetonController::class, 'destroy'])->middleware('auth:sanctum')->name('tokens.destroy');

    // Marque-pages du membre connecté (M2, M3, M13). auth:sanctum et les Policies sont déclarés sur le
    // contrôleur (attributs #[Middleware] et #[Authorize]). Pas de modification : le CDC n'en prévoit pas.
    Route::apiResource('marque-pages', MarquePageController::class)
        ->only(['index', 'store', 'show', 'destroy'])
        ->parameters(['marque-pages' => 'marquePage']);

    // Liens courts (V11, V12), publics : un visiteur en crée et en ouvre. Création limitée à 10 par minute et
    // par IP (provisoire : la vraie limite sera celle de A10, issue #26). Ouvrir n'est pas limité ici.
    Route::post('liens-courts', [LienCourtController::class, 'store'])->middleware('throttle:10,1,liens-courts')->name('liens-courts.store');
    Route::get('liens-courts/{slug}', [LienCourtController::class, 'show'])->name('liens-courts.show');

    // Signalements (V17, V18). Signaler est public, avec la même limite provisoire que les liens courts
    // (le CDC range les deux actions sans compte sous A10). La liste de mes signalements est réservée au membre.
    Route::post('signalements', [SignalementController::class, 'store'])->middleware('throttle:10,1,signalements')->name('signalements.store');
    Route::get('signalements', [SignalementController::class, 'index'])->middleware('auth:sanctum')->name('signalements.index');

    // Lecture publique (V1, B1). Une adresse ne contient pas de « / » : le paramètre par défaut ([^/]+) suffit.
    // Le chemin exact sera fixé par le contrat d'API commun.
    Route::apiResource('pages', PageController::class)
        ->only('show')
        ->parameters(['pages' => 'adresse']);

    // Recherche publique (V4, V5, V6, B2). POST plutôt que GET : un texte de 3 200 caractères accentués,
    // encodé dans l'URL, dépasserait la limite de longueur de beaucoup de serveurs. Rien n'est enregistré : 200.
    // Le chemin exact sera fixé par le contrat d'API commun.
    Route::post('recherche', RechercheController::class)->name('recherche');

    // Identité publique (B6) : identifiant, nom, description, empreinte de la clé (jamais la clé).
    // Le chemin exact sera fixé par le contrat d'API commun.
    Route::get('identite', IdentiteController::class)->name('identite');

    // Page au hasard (V9, F7), publique. Le chemin exact sera fixé par le contrat d'API commun.
    Route::get('hasard', HasardController::class)->name('hasard');
});
