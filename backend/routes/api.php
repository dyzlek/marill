<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Toutes les routes de l'API : préfixe d'URL /api (bootstrap/app.php), groupe de middlewares « api »
// (sans session ni CSRF), et noms préfixés « api. » pour ne pas entrer en collision avec routes/web.php.
Route::name('api.')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum')->name('user');
});
