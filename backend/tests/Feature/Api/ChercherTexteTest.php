<?php

use App\Babel\Validateur;
use Babel\Moteur\Moteur;

// V4 · V5 · V6 · B2 : chercher un texte. POST /api/recherche { texte, mode }
// Le serveur normalise toujours (F3), puis appelle chercher (F2). Modes proposés : exact et bruit.
// Référence : docs/cahier-des-charges.md, livrable 2, F2 et F3.

function chercher(array $corps)
{
    return test()->postJson('/api/recherche', $corps);
}

it('renvoie le texte normalisé, le mode et l\'adresse, sans position', function () {
    chercher(['texte' => 'Où est passé l\'été ?', 'mode' => 'exact'])
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['texte', 'mode', 'adresse']])
        ->assertJsonPath('data.texte', 'ou est passe l ete')
        ->assertJsonPath('data.mode', 'exact');
});

it('renvoie une adresse de page valide dont la page contient le texte', function (string $mode) {
    $adresse = chercher(['texte' => 'Où est passé l\'été ?', 'mode' => $mode])->json('data.adresse');

    expect(app(Validateur::class)->validerPage($adresse)->valide)->toBeTrue()
        ->and(app(Moteur::class)->lire($adresse))->toContain('ou est passe l ete');
})->with(['exact', 'bruit']);

it('met le texte seul en mode exact et l\'entoure de caractères en mode bruit', function () {
    $moteur = app(Moteur::class);
    $exacte = $moteur->lire(chercher(['texte' => 'bonjour', 'mode' => 'exact'])->json('data.adresse'));
    $bruitee = $moteur->lire(chercher(['texte' => 'bonjour', 'mode' => 'bruit'])->json('data.adresse'));

    expect(trim(str_replace('bonjour', '', $exacte)))->toBe('')
        ->and(trim(str_replace('bonjour', '', $bruitee)))->not->toBe('');
});

it('donne toujours la même adresse pour le même texte et le même mode, bruit compris', function (string $mode) {
    $premiere = chercher(['texte' => 'la bibliotheque', 'mode' => $mode])->json('data.adresse');

    expect(chercher(['texte' => 'la bibliotheque', 'mode' => $mode])->json('data.adresse'))->toBe($premiere);
})->with(['exact', 'bruit']);

it('normalise sur le serveur : deux écritures de la même phrase mènent à la même page', function () {
    $brute = chercher(['texte' => "  OÙ EST\npassé L'ÉTÉ ?!  ", 'mode' => 'exact']);
    $propre = chercher(['texte' => 'ou est passe l ete', 'mode' => 'exact']);

    expect($brute->json('data.texte'))->toBe('ou est passe l ete')
        ->and($brute->json('data.adresse'))->toBe($propre->json('data.adresse'));
});

it('accepte un texte de 3 200 caractères exactement', function () {
    chercher(['texte' => str_repeat('a', Moteur::LONGUEUR_PAGE), 'mode' => 'exact'])->assertOk();
});

it('répond 422 au format commun', function (array $corps, array $codes, array $champs) {
    chercher($corps)
        ->assertStatus(422)
        ->assertJsonPath('erreurs.*.code', $codes)
        ->assertJsonPath('erreurs.*.champ', $champs);
})->with([
    'texte absent' => [['mode' => 'exact'], ['ERR_EMPTY_QUERY'], ['texte']],
    'texte vide' => [['texte' => '', 'mode' => 'exact'], ['ERR_EMPTY_QUERY'], ['texte']],
    'vide après conversion' => [['texte' => '?! 123', 'mode' => 'exact'], ['ERR_EMPTY_QUERY'], ['texte']],
    'texte qui n\'est pas une chaîne' => [['texte' => ['a'], 'mode' => 'exact'], ['ERR_EMPTY_QUERY'], ['texte']],
    'trop long après conversion' => [['texte' => str_repeat('a', 3201), 'mode' => 'exact'], ['ERR_TEXT_TOO_LONG'], ['texte']],
    'mode inconnu' => [['texte' => 'a', 'mode' => 'flou'], ['ERR_UNKNOWN_MODE'], ['mode']],
    'mode absent' => [['texte' => 'a'], ['ERR_UNKNOWN_MODE'], ['mode']],
    'mode page (réservé à l\'import)' => [['texte' => 'a', 'mode' => 'page'], ['ERR_UNKNOWN_MODE'], ['mode']],
    'mode en majuscules' => [['texte' => 'a', 'mode' => 'EXACT'], ['ERR_UNKNOWN_MODE'], ['mode']],
    'tout manque' => [[], ['ERR_EMPTY_QUERY', 'ERR_UNKNOWN_MODE'], ['texte', 'mode']],
]);

it('ne recopie pas l\'entrée brute dans les messages d\'erreur', function () {
    $reponse = chercher(['texte' => 'a', 'mode' => '<script>']);

    expect($reponse->status())->toBe(422)
        ->and($reponse->getContent())->not->toContain('script');
});

it('ne demande ni jeton ni session', function () {
    chercher(['texte' => 'a', 'mode' => 'exact'])->assertOk();
});

it('porte le nom de route api.recherche', function () {
    expect(route('api.recherche', absolute: false))->toBe('/api/recherche');
});
