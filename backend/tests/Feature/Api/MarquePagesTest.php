<?php

/*
 * Issue #18 (M2, M3, M13, règle 7) : garder une page, lister, rouvrir, retirer ses marque-pages.
 */

use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\MarquePage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create();
    $this->membre = User::factory()->create();
});

test('401 sans être connecté', function () {
    $this->getJson('/api/marque-pages')->assertUnauthorized();
    $this->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1'])->assertUnauthorized();
});

describe('M2 : garder une page', function () {
    test('201 avec la page, sa bibliothèque et le titre', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/marque-pages', ['adresse' => '1a-w2-s3-v4:5', 'titre' => 'À relire'])
            ->assertCreated()
            ->assertJsonPath('data.titre', 'À relire')
            ->assertJsonPath('data.page', [
                'adresse' => '1a-w2-s3-v4:5',
                'bibliotheque' => $this->ici->identifiant,
                'locale' => true,
            ]);

        expect($this->membre->marquePages()->count())->toBe(1);
    });

    test('le titre est facultatif', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1'])
            ->assertCreated()
            ->assertJsonPath('data.titre', null);
    });

    test('règle 7 : la même page deux fois est refusée proprement (409)', function () {
        $this->actingAs($this->membre)->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1'])->assertCreated();

        $this->actingAs($this->membre)->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1'])
            ->assertStatus(409)
            ->assertExactJson(['erreurs' => [[
                'code' => 'ERR_ALREADY_BOOKMARKED',
                'champ' => 'adresse',
                'message' => 'Cette page est déjà dans vos marque-pages.',
            ]]]);

        expect(MarquePage::query()->count())->toBe(1);
    });

    test('deux membres gardent la même page : une seule adresse enregistrée (règle 2)', function () {
        $autre = User::factory()->create();

        $this->actingAs($this->membre)->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1'])->assertCreated();
        $this->actingAs($autre)->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1'])->assertCreated();

        expect(MarquePage::query()->count())->toBe(2)
            ->and(Adresse::query()->count())->toBe(1);
    });

    test('une adresse invalide renvoie les erreurs de F5', function (string $adresse, int $statut, string $code) {
        $this->actingAs($this->membre)
            ->postJson('/api/marque-pages', ['adresse' => $adresse])
            ->assertStatus($statut)
            ->assertJsonPath('erreurs.0.code', $code);

        expect(MarquePage::query()->count())->toBe(0);
    })->with([
        'zéro de tête' => ['00-w1-s1-v1:1', 422, 'ERR_INVALID_ADDRESS_SYNTAX'],
        'majuscule' => ['A-w1-s1-v1:1', 422, 'ERR_INVALID_HEX_CHARS'],
        'hors limites' => ['0-w5-s1-v1:1', 404, 'ERR_OUT_OF_BOUNDS'],
        'un livre (règle 4)' => ['0-w1-s1-v1', 422, 'ERR_INVALID_ADDRESS_SYNTAX'],
    ]);

    test('M13 : une page d\'une autre bibliothèque du réseau', function () {
        $pair = Bibliotheque::factory()->create(['identifiant' => 'babel-7']);

        $this->actingAs($this->membre)
            ->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-7'])
            ->assertCreated()
            ->assertJsonPath('data.page.bibliotheque', 'babel-7')
            ->assertJsonPath('data.page.locale', false);

        expect(Adresse::query()->sole()->bibliotheque_id)->toBe($pair->id);
    });

    test('une bibliothèque inconnue est refusée', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'inconnue'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bibliotheque');
    });

    test('un titre de plus de 120 caractères est refusé', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/marque-pages', ['adresse' => '0-w1-s1-v1:1', 'titre' => str_repeat('a', 121)])
            ->assertJsonValidationErrors('titre');
    });
});

describe('M3 : lister, rouvrir, retirer', function () {
    test('la liste ne montre que mes marque-pages, du plus récent au plus ancien', function () {
        $ancien = MarquePage::factory()->for($this->membre)->create(['created_at' => now()->subDay()]);
        $recent = MarquePage::factory()->for($this->membre)->create();
        MarquePage::factory()->create(); // celui d'un autre membre

        $this->actingAs($this->membre)->getJson('/api/marque-pages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $recent->id)
            ->assertJsonPath('data.1.id', $ancien->id)
            ->assertJsonPath('meta.total', 2);
    });

    test('la liste charge les adresses en un nombre fixe de requêtes (pas de N+1)', function () {
        MarquePage::factory()->count(10)->for($this->membre)->create();

        DB::enableQueryLog();
        $this->actingAs($this->membre)->getJson('/api/marque-pages')->assertOk()->assertJsonCount(10, 'data');

        // compter + lire les marque-pages + leurs adresses + leurs bibliothèques, que la liste en ait 10 ou 1 000
        $selects = collect(DB::getQueryLog())->filter(fn ($q) => str_starts_with($q['query'], 'select'));
        expect($selects->count())->toBeLessThanOrEqual(5);
    });

    test('rouvrir le sien', function () {
        $marquePage = MarquePage::factory()->for($this->membre)->create();

        $this->actingAs($this->membre)->getJson("/api/marque-pages/{$marquePage->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $marquePage->id)
            ->assertJsonPath('data.page.adresse', $marquePage->adresse()->sole()->ecriture());
    });

    test('retirer le sien : 204, l\'adresse reste', function () {
        $marquePage = MarquePage::factory()->for($this->membre)->create();

        $this->actingAs($this->membre)->deleteJson("/api/marque-pages/{$marquePage->id}")->assertNoContent();

        expect(MarquePage::query()->count())->toBe(0)
            ->and(Adresse::query()->count())->toBe(1);
    });

    test('règle 7 : celui d\'un autre membre ne se lit ni ne se retire (403)', function () {
        $autre = MarquePage::factory()->create();

        $this->actingAs($this->membre)->getJson("/api/marque-pages/{$autre->id}")->assertForbidden();
        $this->actingAs($this->membre)->deleteJson("/api/marque-pages/{$autre->id}")->assertForbidden();

        expect(MarquePage::query()->count())->toBe(1);
    });

    test('404 pour un marque-page qui n\'existe pas', function () {
        $this->actingAs($this->membre)->getJson('/api/marque-pages/999')->assertNotFound();
    });
});
