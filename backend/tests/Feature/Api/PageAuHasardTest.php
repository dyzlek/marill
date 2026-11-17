<?php

use App\Babel\Validateur;
use Babel\Moteur\Moteur;

// V9 : ouvrir une page au hasard. GET /api/hasard renvoie l'adresse tirée et son texte, comme GET /api/pages/{adresse}.

it('renvoie une page tirée au hasard, avec son adresse et ses 3 200 caractères', function () {
    $reponse = $this->getJson('/api/hasard')
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['adresse', 'texte']]);

    $adresse = $reponse->json('data.adresse');
    expect(app(Validateur::class)->validerPage($adresse)->valide)->toBeTrue()
        ->and($reponse->json('data.texte'))->toBe(app(Moteur::class)->lire($adresse));
});

it('tire une autre page à chaque appel et interdit la mise en cache', function () {
    $premiere = $this->getJson('/api/hasard');

    $premiere->assertHeader('Cache-Control', 'no-store, private');
    expect($this->getJson('/api/hasard')->json('data.adresse'))->not->toBe($premiere->json('data.adresse'));
});

it('ne demande ni jeton ni session et porte le nom api.hasard', function () {
    $this->getJson('/api/hasard')->assertOk();

    expect(route('api.hasard', absolute: false))->toBe('/api/hasard');
});
