<?php

use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use Babel\Moteur\Moteur;
use Illuminate\Support\Facades\Route;

// Format commun des erreurs : { "erreurs": [ { "code", "champ", "message" }, … ] }.
// 422 dès qu'une erreur de forme figure dans la liste, 404 quand il ne reste que ERR_OUT_OF_BOUNDS, 429 pour le quota.
// Référence : docs/cahier-des-charges.md, livrable 2, « Les codes d'erreur ».
// Les vraies routes n'existent pas encore (#10, #11) : on déclare des routes de test, dans le groupe « api ».

beforeEach(function () {
    Route::middleware('api')->prefix('api/essai')->group(function () {
        Route::get('lire', fn () => ['texte' => app(Moteur::class)->lire(request()->string('adresse'))]);
        Route::get('chercher', fn () => ['adresse' => app(Moteur::class)->chercher(request()->string('texte'), 'exact')]);
        Route::get('valider', function (Validateur $validateur) {
            $resultat = $validateur->validerPage(request()->string('adresse'));
            throw_unless($resultat->valide, ErreurApi::depuisValidation($resultat));

            return ['niveau' => $resultat->niveau];
        });
        Route::get('quota', fn () => ['ok' => true])->middleware('throttle:1,1');
    });
});

it('répond 422 avec toutes les erreurs pour une adresse mal écrite', function () {
    $this->getJson('/api/essai/lire?adresse=Ab-w9-s1-v1:01')
        ->assertStatus(422)
        ->assertExactJsonStructure(['erreurs' => ['*' => ['code', 'champ', 'message']]])
        ->assertJsonPath('erreurs.*.code', ['ERR_INVALID_HEX_CHARS', 'ERR_OUT_OF_BOUNDS', 'ERR_INVALID_ADDRESS_SYNTAX'])
        ->assertJsonPath('erreurs.*.champ', ['hexagone', 'mur', 'page']);
});

it('répond 404 quand il ne reste que ERR_OUT_OF_BOUNDS', function () {
    $this->getJson('/api/essai/lire?adresse=0-w5-s6-v33:411')
        ->assertStatus(404)
        ->assertJsonPath('erreurs.*.code', array_fill(0, 4, 'ERR_OUT_OF_BOUNDS'));
});

it('répond 200 quand tout va bien', function () {
    $this->getJson('/api/essai/lire?adresse=0-w1-s1-v1:1')
        ->assertOk()
        ->assertJsonPath('texte', fn (string $texte) => strlen($texte) === Moteur::LONGUEUR_PAGE);
});

it('donne la même forme et le même statut aux erreurs de notre validateur', function (string $adresse, int $statut) {
    $avecMoteur = $this->getJson('/api/essai/lire?adresse='.urlencode($adresse));
    $avecValidateur = $this->getJson('/api/essai/valider?adresse='.urlencode($adresse));

    $avecValidateur->assertStatus($statut);
    expect($avecMoteur->status())->toBe($statut)
        ->and($avecValidateur->json('erreurs.*.code'))->toBe($avecMoteur->json('erreurs.*.code'))
        ->and($avecValidateur->json('erreurs.*.champ'))->toBe($avecMoteur->json('erreurs.*.champ'));
})->with([
    'mal écrite' => ['0-w04-s1-v1:1', 422],
    'forme et limites mélangées' => ['Ab-w9-s1-v1:01', 422],
    'hors limites' => ['0-w1-s1-v1:411', 404],
    'livre au lieu d\'une page' => ['0-w1-s1-v1', 422],
]);

it('traduit aussi les erreurs de recherche', function () {
    $this->getJson('/api/essai/chercher?texte=')
        ->assertStatus(422)
        ->assertJsonPath('erreurs.0.code', 'ERR_EMPTY_QUERY')
        ->assertJsonPath('erreurs.0.champ', 'texte');
});

it('ne recopie jamais l\'entrée dans les messages', function (string $route) {
    $piege = '<script>alert(1)</script>';

    $reponse = $this->getJson("/api/essai/$route?adresse=".urlencode($piege.'-w1-s1-v1:1'));

    expect($reponse->status())->toBe(422)
        ->and($reponse->getContent())->not->toContain('script');
})->with(['lire', 'valider']);

it('répond 429 avec ERR_TOO_MANY_REQUESTS quand le quota est dépassé', function () {
    $this->getJson('/api/essai/quota')->assertOk();

    $this->getJson('/api/essai/quota')
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertExactJson(['erreurs' => [[
            'code' => 'ERR_TOO_MANY_REQUESTS',
            'champ' => null,
            'message' => 'Trop de requêtes : réessayez plus tard.',
        ]]]);
});

it('traite ERR_INVALID_ALPHABET comme un oubli dans notre code : 500, sans liste d\'erreurs', function () {
    // Le serveur normalise toujours avant d'appeler le moteur : si cette erreur arrive, c'est un bug à corriger.
    $reponse = $this->getJson('/api/essai/chercher?texte=ABC');

    expect($reponse->status())->toBe(500)
        ->and($reponse->json('erreurs'))->toBeNull();
});

it('laisse CleInvalide en erreur d\'installation : 500', function () {
    config(['babel.cle' => 'pas-une-cle']);
    app()->forgetInstance(Moteur::class);

    $reponse = $this->getJson('/api/essai/lire?adresse=0-w1-s1-v1:1');

    expect($reponse->status())->toBe(500)
        ->and($reponse->json('erreurs'))->toBeNull();
});
