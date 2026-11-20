<?php

/*
 * Issue #28 (M8, règle 12) : l'historique des recherches d'un membre.
 * Issue #30 (A11, règle 21) : les compteurs par jour et les statistiques du back-office.
 * Issue #26 (A10) : la limite de débit de l'API publique, réglable.
 */

use App\Babel\Statistiques;
use App\Models\ActiviteJour;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\LienCourt;
use App\Models\Recherche;
use App\Models\Reglage;
use App\Models\User;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create();
    $this->membre = User::factory()->create();
});

describe('M8 : historique des recherches', function () {
    test('la recherche d\'un membre est gardée, normalisée, sans l\'adresse', function () {
        $this->actingAs($this->membre, 'sanctum')
            ->postJson('/api/recherche', ['texte' => 'La Bibliothèque', 'mode' => 'exact'])
            ->assertOk();

        $this->actingAs($this->membre, 'sanctum')
            ->getJson('/api/recherches')
            ->assertOk()
            ->assertJsonPath('data.0.texte', 'la bibliotheque')
            ->assertJsonPath('data.0.mode', 'exact')
            ->assertJsonPath('data.0.portee', 'locale')
            ->assertJsonMissingPath('data.0.adresse');
    });

    test('celle d\'un visiteur ne laisse qu\'un compteur', function () {
        $this->postJson('/api/recherche', ['texte' => 'babel', 'mode' => 'exact'])->assertOk();

        expect(Recherche::query()->count())->toBe(0)
            ->and(ActiviteJour::query()->find(now()->toDateString())?->recherches)->toBe(1);
    });

    test('je n\'efface que mes recherches', function () {
        $mienne = Recherche::factory()->for($this->membre)->create();
        $autre = Recherche::factory()->create();

        $this->actingAs($this->membre, 'sanctum')->deleteJson("/api/recherches/{$autre->id}")->assertNotFound();
        $this->actingAs($this->membre, 'sanctum')->deleteJson("/api/recherches/{$mienne->id}")->assertNoContent();
    });
});

describe('A11 : statistiques', function () {
    test('lire une page ajoute 1 au compteur du jour, sans autre ligne', function () {
        $this->getJson('/api/pages/0-w1-s1-v1:1')->assertOk();
        $this->getJson('/api/pages/0-w1-s1-v1:2')->assertOk();
        $this->getJson('/api/hasard')->assertOk();

        expect(ActiviteJour::query()->count())->toBe(1)
            ->and(ActiviteJour::query()->find(now()->toDateString())?->pages_lues)->toBe(3);
    });

    test('les chiffres du tableau de bord', function () {
        ActiviteJour::query()->create(['jour' => now()->subDays(2)->toDateString(), 'pages_lues' => 10, 'recherches' => 4]);
        ActiviteJour::query()->create(['jour' => now()->subDays(30)->toDateString(), 'pages_lues' => 999, 'recherches' => 999]);
        $this->membre->forceFill(['derniere_connexion_le' => now()])->save();
        $page = Adresse::enregistrer($this->ici, '0-w1-s1-v1:1');
        LienCourt::factory()->parUnMembre($this->membre)->for($page)->create(['visites' => 12]);

        $stats = app(Statistiques::class);

        expect($stats->comptes())->toBe(1)
            ->and($stats->membresActifs())->toBe(1)
            ->and($stats->pagesLues())->toBe(10) // la ligne d'il y a 30 jours n'entre pas dans les 7 jours
            ->and($stats->recherches())->toBe(4)
            ->and($stats->pagesLuesParJour())->toBe([0.0, 0.0, 0.0, 0.0, 10.0, 0.0, 0.0])
            ->and($stats->pagesLesPlusPartagees()->first()?->ecriture())->toBe('0-w1-s1-v1:1');
    });
});

describe('A10 : limite de débit', function () {
    test('le quota se lit dans les réglages, puis 429 au format commun', function () {
        Reglage::ecrire(Reglage::QUOTA_API, '3');

        foreach (range(1, 3) as $i) {
            $this->getJson('/api/identite')->assertOk();
        }
        $this->getJson('/api/hasard')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('erreurs.0.code', 'ERR_TOO_MANY_REQUESTS');
    });

    test('un membre a son propre compteur, distinct de celui de son adresse IP', function () {
        Reglage::ecrire(Reglage::QUOTA_API, '1');

        $this->getJson('/api/identite')->assertOk();
        $this->getJson('/api/identite')->assertTooManyRequests();
        $this->actingAs($this->membre, 'sanctum')->getJson('/api/identite')->assertOk();
    });

    test('les routes des membres ne sont pas sous le quota public', function () {
        Reglage::ecrire(Reglage::QUOTA_API, '1');

        foreach (range(1, 3) as $i) {
            $this->actingAs($this->membre, 'sanctum')->getJson('/api/marque-pages')->assertOk();
        }
    });
});
