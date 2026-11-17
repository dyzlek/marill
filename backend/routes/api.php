<?php

use App\Http\Controllers\Api\HasardController;
use App\Http\Controllers\Api\IdentiteController;
use App\Http\Controllers\Api\JetonController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\RechercheController;
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

    // Jetons des clients extérieurs (Bruno, scripts). throttle : 5 essais par minute et par adresse IP,
    // contre qui essaierait des mots de passe à la chaîne (429 au-delà, ERR_TOO_MANY_REQUESTS).
    Route::post('tokens', [JetonController::class, 'store'])->middleware('throttle:5,1')->name('tokens.store');
    Route::delete('tokens/courant', [JetonController::class, 'destroy'])->middleware('auth:sanctum')->name('tokens.destroy');

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
