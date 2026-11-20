<?php

/*
 * Issue #35 (A5, A6, A7, B5, règles 16 à 18) : les textes bannis, la partie qui ne dépend que de nous.
 * Plus le nettoyage des liens expirés et l'avatar local du back-office.
 */

use App\Babel\Moderation;
use App\Enums\StatutTexteBanni;
use App\Filament\Resources\TextesBannis\Pages\ListTextesBannis;
use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\TexteBanni;
use App\Models\User;
use Babel\Moteur\Moteur;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->ici = Bibliotheque::locale();
    $this->membre = User::factory()->create();
    // Un passage pris dans une vraie page : il y est forcément.
    $this->texte = mb_substr(app(Moteur::class)->lire('0-w1-s1-v1:7'), 100, 30);
});

describe('A5 : déclarer, lever', function () {
    test('déclarer normalise, applique, et marque la page trouvée en mode exact si elle est enregistrée', function () {
        $exact = Adresse::enregistrer($this->ici, app(Moteur::class)->chercher('mot interdit', 'exact'));

        $banni = app(Moderation::class)->declarer('Mot  INTERDIT !', 'test');

        expect($banni->texte)->toBe('mot interdit')
            ->and($banni->statut)->toBe(StatutTexteBanni::Applique)
            ->and($banni->origine_id)->toBe($this->ici->id)
            ->and($exact->fresh()?->texte_banni_id)->toBe($banni->id);
    });

    test('déclarer deux fois le même texte ne fait pas de doublon', function () {
        app(Moderation::class)->declarer('mot interdit');
        app(Moderation::class)->declarer('Mot interdit');

        expect(TexteBanni::query()->count())->toBe(1);
    });

    test('la tâche de fond marque les pages déjà publiées qui le contiennent', function () {
        $annotation = Annotation::factory()->publique()->for($this->membre)
            ->for(Adresse::enregistrer($this->ici, '0-w1-s1-v1:7'))->create();

        app(Moderation::class)->declarer($this->texte); // la file est « sync » en test : la tâche passe tout de suite

        expect($annotation->adresse->fresh()?->texte_banni_id)->not->toBeNull();
        $this->getJson('/api/pages/0-w1-s1-v1:7/annotations')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/pages/0-w1-s1-v1:7')->assertOk(); // la page, elle, reste lisible
    });

    test('lever retire les marques, et l\'annotation réapparaît', function () {
        Annotation::factory()->publique()->for($this->membre)->for(Adresse::enregistrer($this->ici, '0-w1-s1-v1:7'))->create();
        $banni = app(Moderation::class)->declarer($this->texte);

        app(Moderation::class)->rejeter($banni);

        expect($banni->fresh()?->statut)->toBe(StatutTexteBanni::Rejete)
            ->and(Adresse::query()->whereNotNull('texte_banni_id')->count())->toBe(0);
        $this->getJson('/api/pages/0-w1-s1-v1:7/annotations')->assertJsonCount(1, 'data');
    });
});

describe('étape 3 : vérifier au moment de publier', function () {
    beforeEach(fn () => app(Moderation::class)->declarer($this->texte));

    test('une annotation publique sur la page est cachée tout de suite', function () {
        $this->actingAs($this->membre, 'sanctum')
            ->postJson('/api/annotations', ['adresse' => '0-w1-s1-v1:7', 'contenu' => 'vu', 'est_publique' => true])
            ->assertCreated();

        $this->getJson('/api/pages/0-w1-s1-v1:7/annotations')->assertJsonCount(0, 'data');
    });

    test('un lien court vers la page, ou vers son livre entier, ne s\'ouvre plus (410)', function () {
        $page = $this->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1:7'])->assertCreated()->json('data.slug');
        $livre = $this->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1'])->assertCreated()->json('data.slug');

        $this->getJson("/api/liens-courts/{$page}")->assertStatus(410)->assertJsonPath('erreurs.0.code', 'ERR_LINK_HIDDEN');
        $this->getJson("/api/liens-courts/{$livre}")->assertStatus(410);
    });

    test('dans une collection publique, seule la page marquée disparaît', function () {
        $collection = Collection::factory()->publique()->for($this->membre)->create();
        $this->actingAs($this->membre, 'sanctum')->putJson("/api/collections/{$collection->id}/pages", ['pages' => [
            ['adresse' => '0-w1-s1-v1:6'],
            ['adresse' => '0-w1-s1-v1:7'],
        ]])->assertOk();

        $this->getJson("/api/collections-publiques/{$collection->id}")
            ->assertJsonCount(1, 'data.pages')
            ->assertJsonPath('data.pages.0.page.adresse', '0-w1-s1-v1:6');
    });

    test('une page sans texte banni n\'est pas marquée', function () {
        $this->postJson('/api/liens-courts', ['adresse' => '0-w1-s1-v1:6'])->assertCreated();

        expect(Adresse::query()->where('page', 6)->firstOrFail()->texte_banni_id)->toBeNull();
    });
});

describe('A6 · B5 : la route des textes bannis, réservée au réseau', function () {
    beforeEach(fn () => config(['bibliotheque.jeton_reseau' => 'jeton-de-test']));

    test('sans jeton ou avec un faux : 401', function () {
        $this->getJson('/api/reseau/textes-bannis')->assertUnauthorized()->assertJsonPath('erreurs.0.code', 'ERR_INVALID_NETWORK_TOKEN');
        $this->withToken('faux')->getJson('/api/reseau/textes-bannis')->assertUnauthorized();
    });

    test('jeton non configuré chez nous : personne n\'entre, même avec un jeton vide', function () {
        config(['bibliotheque.jeton_reseau' => null]);

        $this->withToken('')->getJson('/api/reseau/textes-bannis')->assertUnauthorized();
    });

    test('seulement nos textes appliqués, jamais ceux d\'ailleurs ni les levés', function () {
        $applique = app(Moderation::class)->declarer('premier texte');
        app(Moderation::class)->rejeter(app(Moderation::class)->declarer('texte leve'));
        TexteBanni::factory()->for(Bibliotheque::factory(), 'origine')->create(); // venu d'un pair

        $this->withToken('jeton-de-test')->getJson('/api/reseau/textes-bannis')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.texte', 'premier texte')
            ->assertJsonPath('data.0.empreinte', $applique->empreinte);
    });
});

describe('A5 · A7 dans le back-office', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    test('déclarer depuis l\'écran', function () {
        Livewire::test(ListTextesBannis::class)
            ->callAction(TestAction::make('declarer')->table(), ['texte' => 'Texte à bannir', 'motif' => null]);

        expect(TexteBanni::query()->firstOrFail()->texte)->toBe('texte a bannir');
    });

    test('appliquer un texte proposé par un pair (A7)', function () {
        $propose = TexteBanni::factory()->for(Bibliotheque::factory(), 'origine')
            ->create(['statut' => StatutTexteBanni::Propose, 'decide_le' => null]);

        Livewire::test(ListTextesBannis::class)->callAction(TestAction::make('appliquer')->table($propose));

        expect($propose->fresh()?->statut)->toBe(StatutTexteBanni::Applique);
    });

    test('l\'aperçu compte les pages publiées qui seraient touchées', function () {
        Annotation::factory()->publique()->for($this->membre)->for(Adresse::enregistrer($this->ici, '0-w1-s1-v1:7'))->create();
        $propose = TexteBanni::factory()->for(Bibliotheque::factory(), 'origine')
            ->create(['texte' => $this->texte, 'empreinte' => TexteBanni::empreinteDe($this->texte), 'statut' => StatutTexteBanni::Propose]);

        expect(app(Moderation::class)->apercu($propose))->toBe(1);
    });
});

test('liens-courts:nettoyer efface les liens de visiteurs expirés, pas ceux des membres', function () {
    $page = Adresse::enregistrer($this->ici, '0-w1-s1-v1:1');
    $expire = LienCourt::factory()->for($page)->create(['expire_le' => now()->subDay()]);
    $valide = LienCourt::factory()->for($page)->create(['expire_le' => now()->addDay()]);
    $membre = LienCourt::factory()->parUnMembre($this->membre)->for($page)->create();

    $this->artisan('liens-courts:nettoyer')->assertSuccessful();

    expect(LienCourt::query()->pluck('id')->sort()->values()->all())->toBe(collect([$valide->id, $membre->id])->sort()->values()->all())
        ->and(LienCourt::query()->find($expire->id))->toBeNull();
});

test('l\'avatar du back-office est dessiné ici, sans service extérieur', function () {
    $admin = User::factory()->admin()->create(['pseudo' => 'Ada Lovelace']);

    $url = filament()->getPanel('admin')->getDefaultAvatarProvider();
    $avatar = app($url)->get($admin);

    expect($avatar)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($avatar, 26)))->toContain('AL');
});
