<?php

/*
 * Issue #19 (M4, M5, M13, règle 8) : créer, renommer, rendre publique, supprimer une collection,
 * et y ranger des pages dans l'ordre voulu.
 */

use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create();
    $this->membre = User::factory()->create();
});

/** Les adresses de la collection, dans l'ordre renvoyé par l'API. */
function adressesDe($reponse): array
{
    return array_column(array_column($reponse->json('data.pages'), 'page'), 'adresse');
}

test('401 sans être connecté', function () {
    $this->getJson('/api/collections')->assertUnauthorized();
    $this->postJson('/api/collections', ['nom' => 'x'])->assertUnauthorized();
});

describe('M4 · M5 : créer, renommer, publier, supprimer', function () {
    test('201, vide et privée par défaut', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/collections', ['nom' => 'Parcours', 'description' => 'Un texte en trois pages'])
            ->assertCreated()
            ->assertJsonPath('data.nom', 'Parcours')
            ->assertJsonPath('data.est_publique', false)
            ->assertJsonPath('data.masquee', false)
            ->assertJsonPath('data.pages', []);
    });

    test('le nom est obligatoire et limité à 120 caractères', function () {
        $this->actingAs($this->membre)->postJson('/api/collections', [])->assertUnprocessable();
        $this->actingAs($this->membre)->postJson('/api/collections', ['nom' => str_repeat('a', 121)])->assertUnprocessable();
    });

    test('renommer et rendre publique, puis privée (M5)', function () {
        $collection = Collection::factory()->for($this->membre)->create();

        $this->actingAs($this->membre)
            ->patchJson("/api/collections/{$collection->id}", ['nom' => 'Nouveau nom', 'est_publique' => true])
            ->assertOk()
            ->assertJsonPath('data.nom', 'Nouveau nom')
            ->assertJsonPath('data.est_publique', true);

        $this->actingAs($this->membre)
            ->patchJson("/api/collections/{$collection->id}", ['est_publique' => false])
            ->assertJsonPath('data.est_publique', false)
            ->assertJsonPath('data.nom', 'Nouveau nom'); // ce qui n'est pas envoyé ne change pas
    });

    test('supprimer : 204, les pages rangées partent, les adresses restent', function () {
        $collection = Collection::factory()->for($this->membre)->create();
        $this->actingAs($this->membre)->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1:1']);

        $this->actingAs($this->membre)->deleteJson("/api/collections/{$collection->id}")->assertNoContent();

        expect(Collection::count())->toBe(0)
            ->and(DB::table('collection_pages')->count())->toBe(0)
            ->and(Adresse::count())->toBe(1);
    });

    test('ma liste ne montre que mes collections, avec leur nombre de pages', function () {
        $mienne = Collection::factory()->for($this->membre)->create();
        Collection::factory()->publique()->create(); // celle d'un autre, même publique
        $this->actingAs($this->membre)->postJson("/api/collections/{$mienne->id}/pages", ['adresse' => '0-w1-s1-v1:1']);

        $this->actingAs($this->membre)->getJson('/api/collections')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mienne->id)
            ->assertJsonPath('data.0.nombre_pages', 1)
            ->assertJsonMissingPath('data.0.pages');
    });
});

describe('règle 8 : une collection est ordonnée', function () {
    test("ajouter met la page à la fin, et l'ordre est conservé", function () {
        $collection = Collection::factory()->for($this->membre)->create();

        foreach (['1-w1-s1-v1:1', 'a-w2-s3-v4:5', '1-w1-s1-v1:2'] as $adresse) {
            $this->actingAs($this->membre)
                ->postJson("/api/collections/{$collection->id}/pages", ['adresse' => $adresse])
                ->assertOk();
        }

        $reponse = $this->actingAs($this->membre)->getJson("/api/collections/{$collection->id}")->assertOk();
        expect(adressesDe($reponse))->toBe(['1-w1-s1-v1:1', 'a-w2-s3-v4:5', '1-w1-s1-v1:2'])
            ->and(array_column($reponse->json('data.pages'), 'position'))->toBe([1, 2, 3]);
    });

    test('une même page peut revenir deux fois', function () {
        $collection = Collection::factory()->for($this->membre)->create();

        $this->actingAs($this->membre)->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1:1']);
        $reponse = $this->actingAs($this->membre)->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1:1']);

        expect(adressesDe($reponse))->toBe(['0-w1-s1-v1:1', '0-w1-s1-v1:1'])
            ->and(Adresse::count())->toBe(1); // règle 2 : l'adresse n'est enregistrée qu'une fois
    });

    test('ranger : la liste envoyée remplace la précédente, positions 1, 2, 3 sans trou', function () {
        $collection = Collection::factory()->for($this->membre)->create();
        foreach (['1-w1-s1-v1:1', '2-w1-s1-v1:1', '3-w1-s1-v1:1'] as $adresse) {
            $this->actingAs($this->membre)->postJson("/api/collections/{$collection->id}/pages", ['adresse' => $adresse]);
        }

        // Inverser et retirer la page du milieu, en un appel.
        $reponse = $this->actingAs($this->membre)
            ->putJson("/api/collections/{$collection->id}/pages", ['pages' => [
                ['adresse' => '3-w1-s1-v1:1'],
                ['adresse' => '1-w1-s1-v1:1'],
            ]])
            ->assertOk();

        expect(adressesDe($reponse))->toBe(['3-w1-s1-v1:1', '1-w1-s1-v1:1'])
            ->and(array_column($reponse->json('data.pages'), 'position'))->toBe([1, 2]);
    });

    test('une liste vide vide la collection', function () {
        $collection = Collection::factory()->for($this->membre)->create();
        $this->actingAs($this->membre)->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1:1']);

        $this->actingAs($this->membre)
            ->putJson("/api/collections/{$collection->id}/pages", ['pages' => []])
            ->assertOk()
            ->assertJsonPath('data.pages', []);
    });

    test('une seule adresse fausse et rien ne change', function () {
        $collection = Collection::factory()->for($this->membre)->create();
        $this->actingAs($this->membre)->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1:1']);

        $this->actingAs($this->membre)
            ->putJson("/api/collections/{$collection->id}/pages", ['pages' => [
                ['adresse' => '1-w1-s1-v1:1'],
                ['adresse' => '1-w9-s1-v1:1'], // mur 9 : hors bornes, 404 (ERR_OUT_OF_BOUNDS seul)
            ]])
            ->assertNotFound();

        expect(adressesDe($this->actingAs($this->membre)->getJson("/api/collections/{$collection->id}")))
            ->toBe(['0-w1-s1-v1:1']);
    });

    test('règle 4 : un livre entier est refusé', function () {
        $collection = Collection::factory()->for($this->membre)->create();

        $this->actingAs($this->membre)
            ->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1'])
            ->assertUnprocessable()
            ->assertJsonStructure(['erreurs' => [['code', 'champ', 'message']]]);
    });

    test("M13 : une page d'une autre bibliothèque", function () {
        $ailleurs = Bibliotheque::factory()->create();
        $collection = Collection::factory()->for($this->membre)->create();

        $this->actingAs($this->membre)
            ->postJson("/api/collections/{$collection->id}/pages", ['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => $ailleurs->identifiant])
            ->assertOk()
            ->assertJsonPath('data.pages.0.page.bibliotheque', $ailleurs->identifiant)
            ->assertJsonPath('data.pages.0.page.locale', false);
    });
});

describe("une collection n'est visible et modifiable que par son auteur", function () {
    test('403 sur la collection privée d\'un autre, pour tout', function () {
        $autre = Collection::factory()->create();

        $this->actingAs($this->membre)->getJson("/api/collections/{$autre->id}")->assertForbidden();
        $this->actingAs($this->membre)->patchJson("/api/collections/{$autre->id}", ['nom' => 'x'])->assertForbidden();
        $this->actingAs($this->membre)->deleteJson("/api/collections/{$autre->id}")->assertForbidden();
        $this->actingAs($this->membre)->postJson("/api/collections/{$autre->id}/pages", ['adresse' => '0-w1-s1-v1:1'])->assertForbidden();
        $this->actingAs($this->membre)->putJson("/api/collections/{$autre->id}/pages", ['pages' => []])->assertForbidden();

        expect($autre->fresh()->nom)->not->toBe('x');
    });

    test("même publique, la collection d'un autre ne se modifie pas ici", function () {
        $autre = Collection::factory()->publique()->create();

        $this->actingAs($this->membre)->patchJson("/api/collections/{$autre->id}", ['nom' => 'x'])->assertForbidden();
    });

    test("l'administrateur n'a pas d'exception", function () {
        $collection = Collection::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson("/api/collections/{$collection->id}")
            ->assertForbidden();
    });

    test('le membre voit que sa collection a été masquée', function () {
        $collection = Collection::factory()->for($this->membre)->create();
        $collection->forceFill(['masque_le' => now()])->save();

        $this->actingAs($this->membre)->getJson("/api/collections/{$collection->id}")
            ->assertJsonPath('data.masquee', true);
    });
});

test('N+1 : une collection de 20 pages se lit en un nombre fixe de requêtes', function () {
    $collection = Collection::factory()->for($this->membre)->create();
    $pages = array_map(fn ($i) => ['adresse' => "{$i}-w1-s1-v1:1"], range(1, 20));
    $this->actingAs($this->membre)->putJson("/api/collections/{$collection->id}/pages", ['pages' => $pages]);

    DB::enableQueryLog();
    $this->actingAs($this->membre)->getJson("/api/collections/{$collection->id}")->assertOk();

    // collection, pages (jointure pivot), bibliothèques ; preventLazyLoading lèverait une exception sinon.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(4);
});
