<?php

/*
 * Issue #29 (M11, M12, règle 20) : exporter ses données, les importer dans une autre bibliothèque.
 */

use App\Babel\ExportCompte;
use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\MarquePage;
use App\Models\Recherche;
use App\Models\User;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create(['identifiant' => 'babel-3']);
    $this->membre = User::factory()->create();
});

/** Un fichier d'export minimal valide. */
function fichierExport(array $avec = []): array
{
    return [
        'format' => ExportCompte::FORMAT,
        'marque_pages' => [],
        'collections' => [],
        'annotations' => [],
        ...$avec,
    ];
}

describe('M11 : exporter', function () {
    test('un fichier JSON à télécharger, avec tout, sans le mot de passe', function () {
        $page = Adresse::enregistrer($this->ici, '0-w1-s1-v1:1');
        MarquePage::factory()->for($this->membre)->for($page)->create(['titre' => 'Première']);
        $collection = Collection::factory()->for($this->membre)->create(['nom' => 'Parcours']);
        $collection->pages()->attach($page->id, ['position' => 1]);
        Annotation::factory()->for($this->membre)->for($page)->create();
        $this->membre->pagesVotees()->attach($page->id, ['valeur' => 1]);
        Recherche::factory()->for($this->membre)->create();
        LienCourt::factory()->parUnMembre($this->membre)->for($page)->create();

        $reponse = $this->actingAs($this->membre, 'sanctum')->get('/api/user/export')
            ->assertOk()
            ->assertHeader('Content-Disposition')
            ->assertJsonPath('format', ExportCompte::FORMAT)
            ->assertJsonPath('profil.email', $this->membre->email)
            ->assertJsonPath('marque_pages.0.adresse', '0-w1-s1-v1:1')
            ->assertJsonPath('marque_pages.0.bibliotheque', 'babel-3')
            ->assertJsonPath('collections.0.pages.0.position', 1)
            ->assertJsonCount(1, 'annotations')
            ->assertJsonPath('votes.0.valeur', 1)
            ->assertJsonCount(1, 'recherches')
            ->assertJsonCount(1, 'liens_courts');

        expect($reponse->getContent())->not->toContain('password');
    });

    test('réservé au membre connecté', function () {
        $this->getJson('/api/user/export')->assertUnauthorized();
    });
});

describe('M12 : importer', function () {
    test('recrée marque-pages, collections et annotations, collections et annotations privées', function () {
        $this->actingAs($this->membre, 'sanctum')->postJson('/api/user/import', fichierExport([
            'marque_pages' => [['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-3', 'titre' => 'A']],
            'collections' => [['nom' => 'Parcours', 'est_publique' => true, 'pages' => [
                ['adresse' => '0-w1-s1-v1:2', 'bibliotheque' => 'babel-3'],
                ['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-3'],
            ]]],
            'annotations' => [['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-3', 'contenu' => 'Vu', 'est_publique' => true]],
            'votes' => [['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-3', 'valeur' => 1]],
        ]))
            ->assertCreated()
            ->assertJsonPath('data', ['marque_pages' => 1, 'collections' => 1, 'annotations' => 1, 'doublons' => 0]);

        $collection = $this->membre->collections()->with('pages')->firstOrFail();
        expect($collection->est_publique)->toBeFalse()
            ->and($collection->pages->map->ecriture()->all())->toBe(['0-w1-s1-v1:2', '0-w1-s1-v1:1'])
            ->and($this->membre->annotations()->firstOrFail()->est_publique)->toBeFalse()
            ->and($this->membre->pagesVotees()->count())->toBe(0); // les votes ne sont pas repris
    });

    test('une page d\'une bibliothèque inconnue garde son origine', function () {
        $this->actingAs($this->membre, 'sanctum')->postJson('/api/user/import', fichierExport([
            'marque_pages' => [['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-9']],
        ]))->assertCreated();

        $origine = Bibliotheque::query()->where('identifiant', 'babel-9')->firstOrFail();
        expect($this->membre->marquePages()->with('adresse')->firstOrFail()->adresse->bibliotheque_id)->toBe($origine->id)
            ->and($origine->est_locale)->toBeFalse();
    });

    test('deux marque-pages sur la même page sont fusionnés', function () {
        $page = Adresse::enregistrer($this->ici, '0-w1-s1-v1:1');
        MarquePage::factory()->for($this->membre)->for($page)->create();

        $this->actingAs($this->membre, 'sanctum')->postJson('/api/user/import', fichierExport([
            'marque_pages' => [['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-3']],
        ]))->assertJsonPath('data.doublons', 1);

        expect($this->membre->marquePages()->count())->toBe(1);
    });

    test('une adresse fausse : rien n\'est importé, l\'erreur dit où', function () {
        $this->actingAs($this->membre, 'sanctum')->postJson('/api/user/import', fichierExport([
            'marque_pages' => [
                ['adresse' => '0-w1-s1-v1:1', 'bibliotheque' => 'babel-3'],
                ['adresse' => 'pas-une-adresse', 'bibliotheque' => 'babel-3'],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('erreurs.0.champ', 'marque_pages.1.adresse');

        expect($this->membre->marquePages()->count())->toBe(0);
    });

    test('un format inconnu est refusé', function () {
        $this->actingAs($this->membre, 'sanctum')
            ->postJson('/api/user/import', fichierExport(['format' => 'autre/9']))
            ->assertUnprocessable();
    });

    test('aller-retour : l\'export d\'un membre s\'importe chez un autre', function () {
        $page = Adresse::enregistrer($this->ici, '0-w1-s1-v1:7');
        MarquePage::factory()->for($this->membre)->for($page)->create();
        $fichier = $this->actingAs($this->membre, 'sanctum')->getJson('/api/user/export')->json();

        $autre = User::factory()->create();
        $this->actingAs($autre, 'sanctum')->postJson('/api/user/import', $fichier)->assertCreated();

        expect($autre->marquePages()->count())->toBe(1);
    });
});
