<?php

/*
 * Issue #27 (V13, B3, B4, M7, règle 10) : collections publiques, votes et pages populaires.
 */

use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\MarquePage;
use App\Models\Signalement;
use App\Models\TexteBanni;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create();
    $this->membre = User::factory()->create(['pseudo' => 'autrice']);
});

describe('V13 · B3 : collections publiques', function () {
    test('seulement les publiques et non masquées, avec le pseudo sans l\'email', function () {
        $publique = Collection::factory()->publique()->for($this->membre)->create(['nom' => 'À lire']);
        Collection::factory()->for($this->membre)->create(); // privée
        Collection::factory()->publique()->create()->forceFill(['masque_le' => now()])->save();

        $reponse = $this->getJson('/api/collections-publiques')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publique->id)
            ->assertJsonPath('data.0.auteur', 'autrice');

        expect($reponse->getContent())->not->toContain($this->membre->email);
    });

    test('une collection privée donne 404, comme une inconnue', function () {
        $privee = Collection::factory()->for($this->membre)->create();

        $this->getJson("/api/collections-publiques/{$privee->id}")->assertNotFound();
        $this->getJson('/api/collections-publiques/999')->assertNotFound();
    });

    test('une page marquée d\'un texte banni disparaît, le reste reste (règle 18)', function () {
        $collection = Collection::factory()->publique()->create();
        $saine = Adresse::enregistrer($this->ici, '0-w1-s1-v1:1');
        $bannie = Adresse::enregistrer($this->ici, '0-w1-s1-v1:2');
        $bannie->forceFill(['texte_banni_id' => TexteBanni::factory()->for($this->ici, 'origine')->create()->id])->save();
        $collection->pages()->attach($saine->id, ['position' => 1]);
        $collection->pages()->attach($bannie->id, ['position' => 2]);

        $this->getJson("/api/collections-publiques/{$collection->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.pages')
            ->assertJsonPath('data.pages.0.page.adresse', '0-w1-s1-v1:1');
    });

    test('V18 : un membre ne voit plus une collection qu\'il a signalée', function () {
        $collection = Collection::factory()->publique()->create();
        $lecteur = User::factory()->create();
        $signalement = Signalement::factory()->make(['annotation_id' => null, 'collection_id' => $collection->id]);
        $signalement->auteur()->associate($lecteur)->save();

        $this->getJson('/api/collections-publiques')->assertJsonCount(1, 'data');
        $this->actingAs($lecteur, 'sanctum')->getJson('/api/collections-publiques')->assertJsonCount(0, 'data');
    });

    test('N+1 : 20 collections en un nombre fixe de requêtes', function () {
        Collection::factory()->count(20)->publique()->create();

        DB::enableQueryLog();
        $this->getJson('/api/collections-publiques')->assertOk()->assertJsonCount(20, 'data');

        // comptage (pagination), collections + nombre de pages, auteurs
        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(4);
    });
});

describe('M7 : voter', function () {
    test('voter, changer d\'avis, retirer : un seul vote par page (règle 10)', function () {
        $this->actingAs($this->membre, 'sanctum')
            ->putJson('/api/pages/0-w1-s1-v1:1/vote', ['valeur' => 1])
            ->assertOk()
            ->assertJsonPath('data.valeur', 1);
        $this->actingAs($this->membre, 'sanctum')->putJson('/api/pages/0-w1-s1-v1:1/vote', ['valeur' => -1])->assertOk();

        expect($this->membre->pagesVotees()->count())->toBe(1)
            ->and($this->membre->pagesVotees()->firstOrFail()->vote->valeur)->toBe(-1);

        $this->actingAs($this->membre, 'sanctum')->deleteJson('/api/pages/0-w1-s1-v1:1/vote')->assertNoContent();
        expect($this->membre->pagesVotees()->count())->toBe(0);
    });

    test('seulement +1 ou −1, jamais un livre, jamais sans compte', function () {
        $this->actingAs($this->membre, 'sanctum')->putJson('/api/pages/0-w1-s1-v1:1/vote', ['valeur' => 5])->assertUnprocessable();
        $this->actingAs($this->membre, 'sanctum')->putJson('/api/pages/0-w1-s1-v1/vote', ['valeur' => 1])->assertUnprocessable();
        auth()->forgetGuards();
        $this->putJson('/api/pages/0-w1-s1-v1:1/vote', ['valeur' => 1])->assertUnauthorized();
    });
});

describe('B4 : pages populaires', function () {
    test('score = votes + marque-pages, du plus haut au plus bas', function () {
        $a = Adresse::enregistrer($this->ici, '0-w1-s1-v1:1');
        $b = Adresse::enregistrer($this->ici, '0-w1-s1-v1:2');
        foreach (User::factory()->count(3)->create() as $u) {
            $u->pagesVotees()->attach($b->id, ['valeur' => 1]);
        }
        $this->membre->pagesVotees()->attach($a->id, ['valeur' => -1]);
        MarquePage::factory()->for($this->membre)->for($a)->create();
        MarquePage::factory()->for(User::factory())->for($a)->create();

        $this->getJson('/api/pages-populaires')
            ->assertOk()
            ->assertJsonPath('data.0.adresse', '0-w1-s1-v1:2')
            ->assertJsonPath('data.0.score', 3)
            ->assertJsonPath('data.1.adresse', '0-w1-s1-v1:1')
            ->assertJsonPath('data.1.score', 1)
            ->assertJsonPath('data.1.votes', -1)
            ->assertJsonPath('data.1.marque_pages', 2);
    });

    test('ni les pages d\'ailleurs, ni celles marquées d\'un texte banni', function () {
        $ailleurs = Adresse::factory()->for(Bibliotheque::factory(), 'bibliotheque')->create();
        $bannie = Adresse::enregistrer($this->ici, '0-w1-s1-v1:3');
        $bannie->forceFill(['texte_banni_id' => TexteBanni::factory()->for($this->ici, 'origine')->create()->id])->save();
        $this->membre->pagesVotees()->attach([$ailleurs->id => ['valeur' => 1], $bannie->id => ['valeur' => 1]]);

        $this->getJson('/api/pages-populaires')->assertOk()->assertJsonCount(0, 'data');
    });
});
