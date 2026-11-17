<?php

use App\Http\Controllers\Api\PageController;
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
});
