<?php

use App\Http\Controllers\Api\HasardController;
use App\Http\Controllers\Api\IdentiteController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\RechercheController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Toutes les routes de l'API : préfixe d'URL /api (bootstrap/app.php), groupe de middlewares « api »
// (sans session ni CSRF), et noms préfixés « api. » pour ne pas entrer en collision avec routes/web.php.
Route::name('api.')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum')->name('user');

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
