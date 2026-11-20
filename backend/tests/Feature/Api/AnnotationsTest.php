<?php

/*
 * Issue #20 (M6, V14, règle 9) : annoter une page en public ou en privé, modifier, supprimer,
 * et lire les annotations publiques d'une page sans compte.
 */

use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create();
    $this->membre = User::factory()->create(['pseudo' => 'ada']);
});

/** Une annotation sur une page d'ici, écrite directement en base. */
function annotationSur(string $adresse, array $attributs = []): Annotation
{
    return Annotation::factory()->create([
        'adresse_id' => Adresse::enregistrer(Bibliotheque::locale(), $adresse)->id,
        ...$attributs,
    ]);
}

describe('M6 : annoter', function () {
    test('401 sans compte : seuls les membres annotent', function () {
        $this->postJson('/api/annotations', ['adresse' => '0-w1-s1-v1:1', 'contenu' => 'x', 'est_publique' => true])
            ->assertUnauthorized();
    });

    test('201 avec la page, le texte et la visibilité', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/annotations', ['adresse' => '1a-w2-s3-v4:5', 'contenu' => 'Un « mot » ici', 'est_publique' => true])
            ->assertCreated()
            ->assertJsonPath('data.contenu', 'Un « mot » ici')
            ->assertJsonPath('data.est_publique', true)
            ->assertJsonPath('data.masquee', false)
            ->assertJsonPath('data.page.adresse', '1a-w2-s3-v4:5');
    });

    test('règle 9 : 2 000 caractères acceptés, 2 001 refusés (accents comptés comme un caractère)', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/annotations', ['adresse' => '0-w1-s1-v1:1', 'contenu' => str_repeat('é', 2000), 'est_publique' => false])
            ->assertCreated();

        $this->actingAs($this->membre)
            ->postJson('/api/annotations', ['adresse' => '0-w1-s1-v1:1', 'contenu' => str_repeat('é', 2001), 'est_publique' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contenu');
    });

    test('texte vide et visibilité absente refusés', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/annotations', ['adresse' => '0-w1-s1-v1:1', 'contenu' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contenu', 'est_publique']);
    });

    test('règle 4 : un livre entier ne s\'annote pas', function () {
        $this->actingAs($this->membre)
            ->postJson('/api/annotations', ['adresse' => '0-w1-s1-v1', 'contenu' => 'x', 'est_publique' => true])
            ->assertUnprocessable()
            ->assertJsonStructure(['erreurs' => [['code', 'champ', 'message']]]);
    });

    test('modifier le texte puis rendre privée', function () {
        $annotation = annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => true]);

        $this->actingAs($this->membre)
            ->patchJson("/api/annotations/{$annotation->id}", ['contenu' => 'Corrigé'])
            ->assertOk()
            ->assertJsonPath('data.contenu', 'Corrigé')
            ->assertJsonPath('data.est_publique', true);

        $this->actingAs($this->membre)
            ->patchJson("/api/annotations/{$annotation->id}", ['est_publique' => false])
            ->assertJsonPath('data.est_publique', false);
    });

    test('réécrire une annotation masquée ne la fait pas réapparaître', function () {
        $annotation = annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => true]);
        $annotation->forceFill(['masque_le' => now()])->save();

        $this->actingAs($this->membre)
            ->patchJson("/api/annotations/{$annotation->id}", ['contenu' => 'Autre chose', 'masque_le' => null])
            ->assertOk()
            ->assertJsonPath('data.masquee', true);
    });

    test('supprimer : 204, ses signalements partent avec elle', function () {
        $annotation = annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => true]);
        Signalement::factory()->create(['annotation_id' => $annotation->id, 'collection_id' => null, 'lien_court_id' => null]);

        $this->actingAs($this->membre)->deleteJson("/api/annotations/{$annotation->id}")->assertNoContent();

        expect(Annotation::count())->toBe(0)->and(Signalement::count())->toBe(0);
    });

    test('ma liste : les miennes seulement, publiques et privées', function () {
        annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => false]);
        annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => true]);
        annotationSur('0-w1-s1-v1:1', ['est_publique' => true]); // d'un autre

        $this->actingAs($this->membre)->getJson('/api/annotations')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test("403 sur l'annotation d'un autre, même publique, même pour l'admin", function () {
        $autre = annotationSur('0-w1-s1-v1:1', ['est_publique' => true]);

        $this->actingAs($this->membre)->getJson("/api/annotations/{$autre->id}")->assertForbidden();
        $this->actingAs($this->membre)->patchJson("/api/annotations/{$autre->id}", ['contenu' => 'x'])->assertForbidden();
        $this->actingAs($this->membre)->deleteJson("/api/annotations/{$autre->id}")->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->patchJson("/api/annotations/{$autre->id}", ['contenu' => 'x'])->assertForbidden();

        expect($autre->fresh()->contenu)->not->toBe('x');
    });
});

describe('V14 : lire les annotations publiques d\'une page, sans compte', function () {
    test('seulement les publiques et non masquées, avec le pseudo et sans email', function () {
        annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => true, 'contenu' => 'visible']);
        annotationSur('0-w1-s1-v1:1', ['user_id' => $this->membre->id, 'est_publique' => false, 'contenu' => 'privée']);
        annotationSur('0-w1-s1-v1:1', ['est_publique' => true, 'contenu' => 'masquée'])->forceFill(['masque_le' => now()])->save();
        annotationSur('0-w1-s1-v1:2', ['est_publique' => true, 'contenu' => 'autre page']);

        $reponse = $this->getJson('/api/pages/0-w1-s1-v1:1/annotations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.contenu', 'visible')
            ->assertJsonPath('data.0.auteur', 'ada');

        expect($reponse->getContent())->not->toContain($this->membre->email)
            ->and($reponse->json('data.0'))->not->toHaveKey('est_publique');
    });

    test('une page jamais annotée : liste vide, et rien n\'est écrit en base', function () {
        $this->getJson('/api/pages/zz-w4-s5-v32:410/annotations')
            ->assertOk()
            ->assertJsonPath('data', []);

        expect(Adresse::count())->toBe(0);
    });

    test('adresse fausse : mêmes codes que la lecture de la page', function () {
        $this->getJson('/api/pages/0-w1-s1-v1/annotations')->assertUnprocessable();
        $this->getJson('/api/pages/0-w9-s1-v1:1/annotations')->assertNotFound();
    });

    test('V18 : un membre ne voit plus une annotation qu\'il a signalée', function () {
        $annotation = annotationSur('0-w1-s1-v1:1', ['est_publique' => true]);
        Signalement::factory()->create([
            'annotation_id' => $annotation->id, 'collection_id' => null, 'lien_court_id' => null,
            'auteur_id' => $this->membre->id,
        ]);

        $this->actingAs($this->membre, 'sanctum')
            ->getJson('/api/pages/0-w1-s1-v1:1/annotations')
            ->assertJsonCount(0, 'data');
    });

    test('N+1 : 20 annotations en un nombre fixe de requêtes', function () {
        foreach (range(1, 20) as $i) {
            annotationSur('0-w1-s1-v1:1', ['est_publique' => true]);
        }

        DB::enableQueryLog();
        $this->getJson('/api/pages/0-w1-s1-v1:1/annotations')->assertOk()->assertJsonCount(20, 'data');

        // quota A10 (réglage, lu une fois puis gardé en cache), bibliothèque locale, adresse,
        // comptage (pagination), annotations, auteurs
        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(6);
    });
});
