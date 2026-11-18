<?php

use App\Http\Controllers\Auth\GithubController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Connexion avec GitHub (issue #4). Dans web.php : la session garde le state entre l'aller et le retour.
// Hors du groupe auth (on n'est pas encore connecté) ; throttle avec son propre préfixe.
Route::middleware(['guest', 'throttle:10,1,oauth'])->prefix('auth/github')->name('auth.github.')->group(function () {
    Route::get('redirect', [GithubController::class, 'redirect'])->name('redirect');
    Route::get('callback', [GithubController::class, 'callback'])->name('callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
