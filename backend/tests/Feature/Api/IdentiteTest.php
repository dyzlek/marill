<?php

use Babel\Moteur\Moteur;

// B6 : connaître notre identité (identifiant, nom, description, empreinte de clé). GET /api/identite
// La plateforme et les autres bibliothèques la lisent ; l'empreinte leur montre si notre clé a changé (A9).
// Référence : docs/cahier-des-charges.md, livrable 1 (B6) et livrable 2 (F8).

it('donne l\'identifiant, le nom, la description et l\'empreinte de la clé', function () {
    config([
        'bibliotheque.identifiant' => 'babel-3',
        'bibliotheque.nom' => 'Bibliothèque de Babel',
        'bibliotheque.description' => 'Toutes les pages possibles.',
    ]);

    $this->getJson('/api/identite')
        ->assertOk()
        ->assertExactJson(['data' => [
            'identifiant' => 'babel-3',
            'nom' => 'Bibliothèque de Babel',
            'description' => 'Toutes les pages possibles.',
            'empreinte' => app(Moteur::class)->empreinte(),
        ]]);
});

it('publie une empreinte de 64 caractères hexadécimaux en minuscules', function () {
    expect($this->getJson('/api/identite')->json('data.empreinte'))->toMatch('/^[0-9a-f]{64}$/');
});

it('accepte une bibliothèque sans description', function () {
    config(['bibliotheque.description' => null]);

    $this->getJson('/api/identite')
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('ne laisse jamais sortir la clé du moteur', function () {
    $cle = config('babel.cle');
    $contenu = $this->getJson('/api/identite')->getContent();

    expect($cle)->toBeString()->not->toBe('')
        ->and($contenu)->not->toContain($cle)
        ->and($contenu)->not->toContain(str_replace('base64:', '', $cle))
        ->and($contenu)->not->toContain('base64:');
});

it('change d\'empreinte quand la clé change : c\'est ce qui la rend utile au réseau', function () {
    $avant = $this->getJson('/api/identite')->json('data.empreinte');

    config(['babel.cle' => 'base64:'.base64_encode(random_bytes(32))]);
    app()->forgetInstance(Moteur::class);

    expect($this->getJson('/api/identite')->json('data.empreinte'))->not->toBe($avant);
});

it('ne demande ni jeton ni session', function () {
    $this->getJson('/api/identite')->assertOk();
});

it('porte le nom de route api.identite', function () {
    expect(route('api.identite', absolute: false))->toBe('/api/identite');
});
