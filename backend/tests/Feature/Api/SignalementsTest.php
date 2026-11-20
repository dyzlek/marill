<?php

/*
 * Issue #22 (V17, V18, règles 13 à 15) : signaler un contenu publié, ne plus voir ce qu'on a signalé.
 */

use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\Signalement;
use App\Models\User;

beforeEach(function () {
    Bibliotheque::factory()->locale()->create();
});

describe('V17 : signaler un contenu publié', function () {
    test('un visiteur signale une annotation publique : 201, auteur nul, en attente', function () {
        $annotation = Annotation::factory()->publique()->create();

        $this->postJson('/api/signalements', ['annotation' => $annotation->id, 'motif' => 'Injurieux'])
            ->assertCreated()
            ->assertJsonPath('data.cible', ['type' => 'annotation', 'id' => $annotation->id])
            ->assertJsonPath('data.motif', 'Injurieux')
            ->assertJsonMissingPath('data.statut');

        $signalement = Signalement::query()->sole();
        expect($signalement->auteur_id)->toBeNull()
            ->and($signalement->annotation_id)->toBe($annotation->id)
            ->and($signalement->fresh()?->statut->value)->toBe('en_attente');
    });

    test('un membre signale une collection publique : il en est l\'auteur', function () {
        $membre = User::factory()->create();
        $collection = Collection::factory()->publique()->create();

        $this->actingAs($membre)->postJson('/api/signalements', ['collection' => $collection->id, 'motif' => 'Spam'])
            ->assertCreated()
            ->assertJsonPath('data.cible.type', 'collection');

        expect(Signalement::query()->sole()->auteur_id)->toBe($membre->id);
    });

    test('un lien court se signale par son slug', function () {
        $lien = LienCourt::factory()->create();

        $this->postJson('/api/signalements', ['lien_court' => $lien->slug, 'motif' => 'Mène à un texte choquant'])
            ->assertCreated()
            ->assertJsonPath('data.cible', ['type' => 'lien_court', 'slug' => $lien->slug]);

        expect(Signalement::query()->sole()->lien_court_id)->toBe($lien->id);
    });

    test('un contenu privé ou inconnu ne se signale pas (même message)', function (Closure $corps, string $champ) {
        $this->postJson('/api/signalements', [...$corps(), 'motif' => 'x'])
            ->assertJsonValidationErrors([$champ => 'Aucun contenu publié ne correspond.']);

        expect(Signalement::query()->count())->toBe(0);
    })->with([
        'annotation privée' => [fn () => ['annotation' => Annotation::factory()->create(['est_publique' => false])->id], 'annotation'],
        'collection privée' => [fn () => ['collection' => Collection::factory()->create()->id], 'collection'],
        'annotation inconnue' => [fn () => ['annotation' => 999], 'annotation'],
        'lien inconnu' => [fn () => ['lien_court' => 'inconnu0'], 'lien_court'],
    ]);

    test('règle 13 : exactement un contenu visé, jamais une page', function (array $corps) {
        $this->postJson('/api/signalements', [...$corps, 'motif' => 'x'])->assertUnprocessable();

        expect(Signalement::query()->count())->toBe(0);
    })->with([
        'aucune cible' => [[]],
        'deux cibles' => [['annotation' => 1, 'collection' => 1]],
        'une page' => [['adresse' => '0-w1-s1-v1:1']],
    ]);

    test('le motif est obligatoire', function () {
        $this->postJson('/api/signalements', ['lien_court' => LienCourt::factory()->create()->slug])
            ->assertJsonValidationErrors('motif');
    });

    test('un membre ne signale qu\'une fois le même contenu : 409', function () {
        $membre = User::factory()->create();
        $annotation = Annotation::factory()->publique()->create();

        $this->actingAs($membre)->postJson('/api/signalements', ['annotation' => $annotation->id, 'motif' => 'a'])->assertCreated();
        $this->actingAs($membre)->postJson('/api/signalements', ['annotation' => $annotation->id, 'motif' => 'b'])
            ->assertStatus(409)
            ->assertJsonPath('erreurs.0.code', 'ERR_ALREADY_REPORTED');

        expect(Signalement::query()->count())->toBe(1);
    });

    test('des visiteurs différents signalent chacun (le serveur ne les reconnaît pas)', function () {
        $annotation = Annotation::factory()->publique()->create();

        $this->postJson('/api/signalements', ['annotation' => $annotation->id, 'motif' => 'a'])->assertCreated();
        $this->postJson('/api/signalements', ['annotation' => $annotation->id, 'motif' => 'b'])->assertCreated();

        expect(Signalement::query()->count())->toBe(2);
    });

    test('A10 : liens courts et signalements partagent le quota des publications, lire ne compte pas', function () {
        $lien = LienCourt::factory()->for(Adresse::factory()->for(Bibliotheque::locale()))->create();
        foreach (range(1, 15) as $i) {
            $this->getJson("/api/liens-courts/{$lien->slug}")->assertOk(); // lire : l'autre compteur
        }

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/liens-courts', ['adresse' => "0-w1-s1-v1:{$i}"])->assertCreated();
        }
        foreach (range(1, 5) as $i) {
            $this->postJson('/api/signalements', ['lien_court' => $lien->slug, 'motif' => "n° {$i}"])->assertCreated();
        }
        $this->postJson('/api/signalements', ['lien_court' => $lien->slug, 'motif' => 'de trop'])
            ->assertTooManyRequests()
            ->assertJsonPath('erreurs.0.code', 'ERR_TOO_MANY_REQUESTS');
    });
});

describe('V18 : ne plus voir ce qu\'on a signalé', function () {
    test('GET /api/signalements : mes signalements seulement, réservé aux membres', function () {
        $membre = User::factory()->create();
        $lien = LienCourt::factory()->create();
        Signalement::factory()->create(['auteur_id' => $membre->id, 'annotation_id' => null, 'lien_court_id' => $lien->id]);
        Signalement::factory()->create(); // celui d'un visiteur

        $this->getJson('/api/signalements')->assertUnauthorized();
        $this->actingAs($membre)->getJson('/api/signalements')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cible', ['type' => 'lien_court', 'slug' => $lien->slug]);
    });

    test('un membre ne rouvre pas un lien court qu\'il a signalé ; les autres si', function () {
        $membre = User::factory()->create();
        $lien = LienCourt::factory()->create();
        $this->actingAs($membre)->postJson('/api/signalements', ['lien_court' => $lien->slug, 'motif' => 'x'])->assertCreated();

        $this->actingAs($membre)->getJson("/api/liens-courts/{$lien->slug}")
            ->assertStatus(410)
            ->assertJsonPath('erreurs.0.message', 'Vous avez signalé ce lien court.');

        // Dans un test, le garde sanctum garde en mémoire le membre de la requête précédente ; en vrai, chaque
        // requête repart de zéro. forgetGuards() reproduit ça.
        app('auth')->forgetGuards();
        $this->actingAs(User::factory()->create())->getJson("/api/liens-courts/{$lien->slug}")->assertOk();
    });

    test('le filtre nonSignalesPar retire ce que le membre a signalé', function () {
        $membre = User::factory()->create();
        [$signalee, $autre] = Annotation::factory()->publique()->count(2)->create();
        Signalement::factory()->create(['annotation_id' => $signalee->id, 'auteur_id' => $membre->id]);

        expect(Annotation::query()->nonSignalesPar($membre)->pluck('id')->all())->toBe([$autre->id])
            ->and(Annotation::query()->nonSignalesPar(null)->count())->toBe(2);
    });
});
