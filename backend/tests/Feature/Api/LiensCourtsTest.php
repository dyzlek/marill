<?php

/*
 * Issue #21 (V11, V12, règle 11) : créer et ouvrir un lien court vers une page, un livre ou une collection.
 */

use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\TexteBanni;
use App\Models\User;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create();
});

describe('V11 : créer un lien court', function () {
    test('un visiteur crée un lien vers une page : pas d\'auteur, expire dans 30 jours', function () {
        $reponse = $this->postJson('/api/liens-courts', ['adresse' => '1a-w2-s3-v4:5', 'passage' => 'la bibliotheque'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'page')
            ->assertJsonPath('data.ouvrir', '1a-w2-s3-v4:5')
            ->assertJsonPath('data.passage', 'la bibliotheque')
            ->assertJsonPath('data.visites', 0);

        $lien = LienCourt::query()->sole();
        expect($lien->user_id)->toBeNull()
            ->and($lien->expire_le?->isSameDay(now()->addDays(30)))->toBeTrue()
            ->and($reponse->json('data.slug'))->toBe($lien->slug)
            ->and($lien->slug)->toMatch('/\A[a-km-np-z2-9]{8}\z/'); // ni l, ni o, ni 0, ni 1
    });

    test('un membre crée un lien qui n\'expire pas, et dont il est l\'auteur', function () {
        $membre = User::factory()->create();

        $this->actingAs($membre)->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1:1'])
            ->assertCreated()
            ->assertJsonPath('data.expire_le', null)
            ->assertJsonMissingPath('data.user_id');

        expect(LienCourt::query()->sole()->user_id)->toBe($membre->id);
    });

    test('un membre connecté par jeton est aussi reconnu', function () {
        $membre = User::factory()->create();
        $jeton = $membre->createToken('test')->plainTextToken;

        $this->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1:1'], ['Authorization' => "Bearer {$jeton}"])
            ->assertCreated();

        expect(LienCourt::query()->sole()->user_id)->toBe($membre->id);
    });

    test('règle 4 : un lien vers un livre s\'ouvre sur sa page 1', function () {
        $this->postJson('/api/liens-courts', ['adresse' => '1a-w2-s3-v4'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'livre')
            ->assertJsonPath('data.page.adresse', '1a-w2-s3-v4')
            ->assertJsonPath('data.ouvrir', '1a-w2-s3-v4:1');
    });

    test('un lien vers une collection publique', function () {
        $collection = Collection::factory()->publique()->create(['nom' => 'Parcours']);

        $this->postJson('/api/liens-courts', ['collection' => $collection->id])
            ->assertCreated()
            ->assertJsonPath('data.type', 'collection')
            ->assertJsonPath('data.collection', ['id' => $collection->id, 'nom' => 'Parcours'])
            ->assertJsonPath('data.page', null);
    });

    test('une collection privée ne se partage pas (même message que si elle n\'existait pas)', function () {
        $privee = Collection::factory()->create();

        $this->postJson('/api/liens-courts', ['collection' => $privee->id])->assertJsonValidationErrors('collection');
        $this->postJson('/api/liens-courts', ['collection' => 999])->assertJsonValidationErrors('collection');

        expect(LienCourt::query()->count())->toBe(0);
    });

    test('règle 11 : exactement une cible, un passage seulement avec une page', function (array $corps, string $champ) {
        $collection = Collection::factory()->publique()->create();
        $corps = array_map(fn ($v) => $v === 'COLLECTION' ? $collection->id : $v, $corps);

        $this->postJson('/api/liens-courts', $corps)->assertJsonValidationErrors($champ);

        expect(LienCourt::query()->count())->toBe(0);
    })->with([
        'aucune cible' => [[], 'adresse'],
        'deux cibles' => [['adresse' => '0-w1-s1-v1:1', 'collection' => 'COLLECTION'], 'adresse'],
        'passage avec une collection' => [['collection' => 'COLLECTION', 'passage' => 'abc'], 'passage'],
        'passage avec un livre' => [['adresse' => '0-w1-s1-v1', 'passage' => 'abc'], 'passage'],
        'passage hors alphabet' => [['adresse' => '0-w1-s1-v1:1', 'passage' => 'Été !'], 'passage'],
    ]);

    test('une adresse invalide renvoie les erreurs de F5', function () {
        $this->postJson('/api/liens-courts', ['adresse' => '0-w9-s1-v1:1'])
            ->assertNotFound()
            ->assertJsonPath('erreurs.0.code', 'ERR_OUT_OF_BOUNDS');
    });

    test('M13 : un lien vers la page d\'une autre bibliothèque', function () {
        Bibliotheque::factory()->create(['identifiant' => 'babel-7']);

        $this->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-7'])
            ->assertCreated()
            ->assertJsonPath('data.page.bibliotheque', 'babel-7')
            ->assertJsonPath('data.page.locale', false);
    });

    test('limite provisoire : 10 créations par minute, puis 429', function () {
        foreach (range(1, 10) as $i) {
            $this->postJson('/api/liens-courts', ['adresse' => "0-w1-s1-v1:{$i}"])->assertCreated();
        }

        $this->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1:11'])
            ->assertTooManyRequests()
            ->assertJsonPath('erreurs.0.code', 'ERR_TOO_MANY_REQUESTS');
    });
});

describe('V12 : ouvrir un lien court', function () {
    test('ouvrir compte une visite', function () {
        $lien = LienCourt::factory()->for(Adresse::factory()->for($this->ici))->create(['visites' => 0]);

        $this->getJson("/api/liens-courts/{$lien->slug}")->assertOk()->assertJsonPath('data.visites', 1);
        $this->getJson("/api/liens-courts/{$lien->slug}")->assertOk()->assertJsonPath('data.visites', 2);

        expect($lien->fresh()?->visites)->toBe(2);
    });

    test('inconnu : 404 ERR_LINK_NOT_FOUND', function () {
        $this->getJson('/api/liens-courts/abcdefgh')
            ->assertNotFound()
            ->assertExactJson(['erreurs' => [['code' => 'ERR_LINK_NOT_FOUND', 'champ' => 'slug', 'message' => 'Ce lien court n\'existe pas.']]]);
    });

    test('expiré : 410 ERR_LINK_EXPIRED, et la visite ne compte pas', function () {
        $lien = LienCourt::factory()->create(['expire_le' => now()->subMinute(), 'visites' => 3]);

        $this->getJson("/api/liens-courts/{$lien->slug}")
            ->assertStatus(410)
            ->assertJsonPath('erreurs.0.code', 'ERR_LINK_EXPIRED');

        expect($lien->fresh()?->visites)->toBe(3);
    });

    // Paramètre typé Closure : Pest passe la fonction telle quelle, on l'appelle dans le test (base prête).
    test('masqué : 410 ERR_LINK_HIDDEN', function (Closure $preparer) {
        $lien = $preparer();

        $this->getJson("/api/liens-courts/{$lien->slug}")
            ->assertStatus(410)
            ->assertJsonPath('erreurs.0.code', 'ERR_LINK_HIDDEN');
    })->with([
        'le lien lui-même (règle 14)' => fn () => LienCourt::factory()->create(['masque_le' => now()]),
        'sa page contient un texte banni (règle 18)' => fn () => LienCourt::factory()
            ->for(Adresse::factory()->state(['texte_banni_id' => TexteBanni::factory()]))->create(),
        'sa collection est masquée' => fn () => LienCourt::factory()
            ->versUneCollection(Collection::factory()->publique()->create(['masque_le' => now()]))->create(),
        'sa collection est redevenue privée' => fn () => LienCourt::factory()
            ->versUneCollection(Collection::factory()->create())->create(),
    ]);
});
