<?php

use App\Babel\Validateur;
use Babel\Moteur\Moteur;

// V1 · B1 : lire une page par son adresse. GET /api/pages/{adresse}
// Route publique : un visiteur ou une autre bibliothèque lit sans jeton.
// Référence : docs/cahier-des-charges.md, livrable 2, F1.

it('renvoie l\'adresse et les 3 200 caractères de la page', function () {
    $this->getJson('/api/pages/0-w1-s1-v1:1')
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['adresse', 'texte']])
        ->assertJsonPath('data.adresse', '0-w1-s1-v1:1')
        ->assertJsonPath('data.texte', app(Moteur::class)->lire('0-w1-s1-v1:1'));
});

it('renvoie un texte de 3 200 caractères pris dans les 29 symboles', function () {
    $texte = $this->getJson('/api/pages/k3x-w2-s4-v17:205')->json('data.texte');

    expect($texte)->toHaveLength(Moteur::LONGUEUR_PAGE)
        ->toMatch('/^['.preg_quote(Moteur::ALPHABET, '/').']+$/');
});

it('donne toujours le même texte pour la même adresse', function () {
    $premiere = $this->getJson('/api/pages/zz-w4-s5-v32:410')->json('data.texte');

    expect($this->getJson('/api/pages/zz-w4-s5-v32:410')->json('data.texte'))->toBe($premiere);
});

it('lit la page unique du dernier hexagone (adresse de plus de 3 000 caractères)', function () {
    $adresse = Validateur::dernierHexagone().'-w1-s1-v1:1';

    $this->getJson('/api/pages/'.$adresse)
        ->assertOk()
        ->assertJsonPath('data.adresse', $adresse);
});

it('accepte le « : » encodé dans l\'URL', function () {
    $this->getJson('/api/pages/0-w1-s1-v1%3A1')
        ->assertOk()
        ->assertJsonPath('data.adresse', '0-w1-s1-v1:1');
});

it('répond 422 au format commun pour une adresse mal écrite', function (string $chemin, array $codes, array $champs) {
    $this->getJson('/api/pages/'.$chemin)
        ->assertStatus(422)
        ->assertJsonPath('erreurs.*.code', $codes)
        ->assertJsonPath('erreurs.*.champ', $champs);
})->with([
    'zéro devant le mur' => ['0-w04-s1-v1:1', ['ERR_INVALID_ADDRESS_SYNTAX'], ['mur']],
    'hexagone en majuscules' => ['ABC-w1-s1-v1:1', ['ERR_INVALID_HEX_CHARS'], ['hexagone']],
    'espace final' => ['0-w1-s1-v1:1%20', ['ERR_INVALID_ADDRESS_SYNTAX'], ['page']],
    'saut de ligne final' => ['0-w1-s1-v1:1%0A', ['ERR_INVALID_ADDRESS_SYNTAX'], ['page']],
    'plusieurs erreurs' => ['Ab-w9-s1-v1:01', ['ERR_INVALID_HEX_CHARS', 'ERR_OUT_OF_BOUNDS', 'ERR_INVALID_ADDRESS_SYNTAX'], ['hexagone', 'mur', 'page']],
    'texte quelconque' => ['bonjour', ['ERR_INVALID_ADDRESS_SYNTAX'], ['adresse']],
]);

it('refuse une adresse de livre : on lit une page, pas un livre', function () {
    $this->getJson('/api/pages/0-w1-s1-v1')
        ->assertStatus(422)
        ->assertJsonPath('erreurs.0.code', 'ERR_INVALID_ADDRESS_SYNTAX')
        ->assertJsonPath('erreurs.0.champ', 'page');
});

it('répond 404 pour une adresse bien écrite qui n\'existe pas', function (string $adresse) {
    $this->getJson('/api/pages/'.$adresse)
        ->assertNotFound()
        ->assertJsonPath('erreurs.0.code', 'ERR_OUT_OF_BOUNDS');
})->with([
    'page 411' => '0-w1-s1-v1:411',
    'après la page unique du dernier hexagone' => fn () => Validateur::dernierHexagone().'-w1-s1-v1:2',
]);

it('ne demande ni jeton ni session', function () {
    // Aucun actingAs : la requête arrive comme celle d'un visiteur ou d'une autre bibliothèque.
    $this->getJson('/api/pages/0-w1-s1-v1:1')->assertOk();
});

it('porte le nom de route api.pages.show', function () {
    expect(route('api.pages.show', ['adresse' => '0-w1-s1-v1:1'], absolute: false))
        ->toBe('/api/pages/0-w1-s1-v1:1');
});
