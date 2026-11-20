<?php

use App\Http\Controllers\Api\AnnotationController;
use App\Http\Controllers\Api\AnnotationsDePageController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CollectionPubliqueController;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\ExportCompteController;
use App\Http\Controllers\Api\HasardController;
use App\Http\Controllers\Api\HistoriqueRechercheController;
use App\Http\Controllers\Api\IdentiteController;
use App\Http\Controllers\Api\JetonController;
use App\Http\Controllers\Api\LienCourtController;
use App\Http\Controllers\Api\MarquePageController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PagesPopulairesController;
use App\Http\Controllers\Api\RechercheController;
use App\Http\Controllers\Api\SignalementController;
use App\Http\Controllers\Api\TextesBannisReseauController;
use App\Http\Controllers\Api\UtilisateurCourantController;
use App\Http\Controllers\Api\VoteController;
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

    // Mon compte (M9, M10, M11, M12). Toujours « mon » compte : aucun id dans l'URL. auth:sanctum sur les contrôleurs.
    Route::patch('user', [CompteController::class, 'update'])->name('user.update');
    Route::put('user/password', [CompteController::class, 'changerMotDePasse'])->name('user.password');
    Route::delete('user', [CompteController::class, 'destroy'])->name('user.destroy');
    Route::get('user/export', [ExportCompteController::class, 'export'])->name('user.export');
    Route::post('user/import', [ExportCompteController::class, 'import'])->name('user.import');

    // Mon historique de recherches (M8, règle 12).
    Route::get('recherches', [HistoriqueRechercheController::class, 'index'])->name('recherches.index');
    Route::delete('recherches/{recherche}', [HistoriqueRechercheController::class, 'destroy'])->whereNumber('recherche')->name('recherches.destroy');

    // Voter pour ou contre une page (M7, règle 10). PUT pose mon vote (idempotent), DELETE le retire.
    Route::put('pages/{adresse}/vote', [VoteController::class, 'update'])->name('pages.vote.update');
    Route::delete('pages/{adresse}/vote', [VoteController::class, 'destroy'])->name('pages.vote.destroy');

    // Limites de débit. throttle:essais,minutes,préfixe : le préfixe donne à la route son propre compteur (sans
    // lui, toutes les routes limitées ainsi partagent un compteur par IP). throttle:nom : une limite nommée
    // (AppServiceProvider), dont le quota se lit dans les réglages (A10).

    // Jetons des clients extérieurs (Bruno, scripts). throttle : 5 essais par minute et par adresse IP,
    // contre qui essaierait des mots de passe à la chaîne (429 au-delà, ERR_TOO_MANY_REQUESTS).
    Route::post('tokens', [JetonController::class, 'store'])->middleware('throttle:5,1,jetons')->name('tokens.store');
    Route::delete('tokens/courant', [JetonController::class, 'destroy'])->middleware('auth:sanctum')->name('tokens.destroy');

    // Marque-pages du membre connecté (M2, M3, M13). auth:sanctum et les Policies sont déclarés sur le
    // contrôleur (attributs #[Middleware] et #[Authorize]). Pas de modification : le CDC n'en prévoit pas.
    Route::apiResource('marque-pages', MarquePageController::class)
        ->only(['index', 'store', 'show', 'destroy'])
        ->parameters(['marque-pages' => 'marquePage']);

    // Collections du membre connecté (M4, M5, M13, règle 8). auth:sanctum et la Policy sur le contrôleur.
    // Les pages : POST ajoute à la fin, PUT remplace toute la liste dans le nouvel ordre.
    Route::apiResource('collections', CollectionController::class);
    Route::post('collections/{collection}/pages', [CollectionController::class, 'ajouterPage'])->name('collections.pages.store');
    Route::put('collections/{collection}/pages', [CollectionController::class, 'rangerPages'])->name('collections.pages.update');

    // Annotations du membre connecté (M6, règle 9) ; seuls les membres annotent. Lecture publique d'une page
    // (V14) sans compte : ses annotations publiques, non masquées.
    Route::apiResource('annotations', AnnotationController::class);
    Route::get('pages/{adresse}/annotations', AnnotationsDePageController::class)->middleware('throttle:api-publique')->name('pages.annotations');

    // La liste de mes signalements (V18) est réservée au membre.
    Route::get('signalements', [SignalementController::class, 'index'])->middleware('auth:sanctum')->name('signalements.index');

    // A10 : l'API publique, ouverte sans jeton, sous la limite de débit « api-publique » (AppServiceProvider) ;
    // le quota se règle dans le back-office. Les deux actions sans compte qui écrivent en base (créer un lien
    // court, signaler) ont en plus la limite « publications », plus serrée.
    Route::middleware('throttle:api-publique')->group(function () {
        // Liens courts (V11, V12), publics : un visiteur en crée et en ouvre.
        Route::post('liens-courts', [LienCourtController::class, 'store'])->middleware('throttle:publications')->name('liens-courts.store');
        Route::get('liens-courts/{slug}', [LienCourtController::class, 'show'])->name('liens-courts.show');

        // Signaler un contenu publié (V17), sans compte possible.
        Route::post('signalements', [SignalementController::class, 'store'])->middleware('throttle:publications')->name('signalements.store');

        // V13 · B3 : collections publiques ; B4 : pages populaires. Lisibles par tous, autres bibliothèques comprises.
        Route::get('collections-publiques', [CollectionPubliqueController::class, 'index'])->name('collections-publiques.index');
        Route::get('collections-publiques/{collection}', [CollectionPubliqueController::class, 'show'])->whereNumber('collection')->name('collections-publiques.show');
        Route::get('pages-populaires', PagesPopulairesController::class)->name('pages-populaires');

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

        // A6 · B5 : nos textes bannis, pour les seules bibliothèques du réseau (jeton réseau, middleware sur
        // le contrôleur). La seule route publique qui exige un jeton. Chemin provisoire (contrat commun).
        Route::get('reseau/textes-bannis', TextesBannisReseauController::class)->name('reseau.textes-bannis');

        // Page au hasard (V9, F7), publique. Le chemin exact sera fixé par le contrat d'API commun.
        Route::get('hasard', HasardController::class)->name('hasard');
    });
});
