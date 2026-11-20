<?php

/*
 * Issues #23, #24, #25 : le back-office Filament. A1 · A2 · A3 (comptes), A4 (file des signalements),
 * A8 (configurer la bibliothèque) et A10 (quotas dans les réglages).
 *
 * Les règles sont testées sur les méthodes des modèles (sans écran), puis une fois à travers l'écran
 * (Livewire::test) pour vérifier que les boutons appellent bien ces méthodes.
 */

use App\Enums\Role;
use App\Enums\StatutSignalement;
use App\Filament\Pages\ReglagesBibliotheque;
use App\Filament\Resources\Signalements\Pages\ListSignalements;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\MarquePage;
use App\Models\Reglage;
use App\Models\Signalement;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->ici = Bibliotheque::factory()->locale()->create(['identifiant' => 'babel-3', 'nom' => 'Babel 3']);
    $this->admin = User::factory()->admin()->create();
    $this->membre = User::factory()->create();
});

describe('accès au back-office', function () {
    test('un admin entre sur /admin', function () {
        $this->actingAs($this->admin)->get('/admin')->assertOk();
    });

    test('un membre est refusé (403), un visiteur renvoyé vers la connexion', function () {
        $this->actingAs($this->membre)->get('/admin')->assertForbidden();
        auth()->logout();
        $this->get('/admin')->assertRedirect('/admin/login');
    });

    test('un admin suspendu n\'entre plus', function () {
        $this->admin->forceFill(['suspendu_le' => now()])->save();

        expect($this->admin->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
    });
});

describe('A1 · A2 · A3 : les comptes', function () {
    test('A2 suspendre coupe les jetons et les sessions ; rétablir rend l\'accès', function () {
        $this->membre->createToken('bruno');
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $this->membre->id, 'payload' => '', 'last_activity' => time()]);

        $this->membre->suspendre();

        expect($this->membre->estSuspendu())->toBeTrue()
            ->and($this->membre->tokens()->count())->toBe(0)
            ->and(DB::table('sessions')->where('user_id', $this->membre->id)->count())->toBe(0);

        $this->postJson('/api/tokens', ['email' => $this->membre->email, 'password' => 'password', 'nom' => 'x'])
            ->assertUnauthorized();

        $this->membre->retablir();
        expect($this->membre->fresh()?->estSuspendu())->toBeFalse();
    });

    test('A3 promouvoir fait un admin', function () {
        $this->membre->promouvoir();

        expect($this->membre->fresh()?->role)->toBe(Role::Admin);
    });

    test('A1 la liste se filtre et les boutons appellent le modèle', function () {
        $suspendu = User::factory()->create(['suspendu_le' => now()]);
        $this->actingAs($this->admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$this->membre, $suspendu])
            ->filterTable('suspendu', true)
            ->assertCanSeeTableRecords([$suspendu])
            ->assertCanNotSeeTableRecords([$this->membre]);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('suspendre')->table($this->membre));

        expect($this->membre->fresh()?->estSuspendu())->toBeTrue();
    });

    test('un admin ne peut ni se suspendre ni se supprimer', function () {
        $this->actingAs($this->admin);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('suspendre')->table($this->admin))
            ->assertActionHidden(TestAction::make('delete')->table($this->admin));
    });

    test('A2 supprimer efface ses données (règle 19), ses signalements restent sans auteur', function () {
        MarquePage::factory()->for($this->membre)->create();
        $signalement = Signalement::factory()->for(Annotation::factory()->publique())->create();
        $signalement->auteur()->associate($this->membre)->save();
        $this->actingAs($this->admin);

        Livewire::test(ListUsers::class)->callAction(TestAction::make('delete')->table($this->membre));

        expect(User::query()->find($this->membre->id))->toBeNull()
            ->and(MarquePage::query()->count())->toBe(0)
            ->and($signalement->fresh()?->auteur_id)->toBeNull();
    });
});

describe('A4 : la file des signalements', function () {
    test('masquer date le contenu et garde qui a décidé', function () {
        $annotation = Annotation::factory()->publique()->create();
        $signalement = Signalement::factory()->for($annotation)->create();

        $signalement->masquerContenu($this->admin);

        expect($annotation->fresh()?->masque_le)->not->toBeNull()
            ->and($signalement->fresh()?->statut)->toBe(StatutSignalement::Traite)
            ->and($signalement->fresh()?->traite_par_id)->toBe($this->admin->id)
            ->and($signalement->fresh()?->traite_le)->not->toBeNull();
    });

    test('rétablir remet le contenu visible', function () {
        $collection = Collection::factory()->publique()->create();
        $collection->forceFill(['masque_le' => now()])->save();
        $signalement = Signalement::factory()->make(['annotation_id' => null, 'collection_id' => $collection->id]);
        $signalement->save();

        $signalement->retablirContenu($this->admin);

        expect($collection->fresh()?->masque_le)->toBeNull();
    });

    test('rejeter ne touche pas au contenu', function () {
        $annotation = Annotation::factory()->publique()->create();
        $signalement = Signalement::factory()->for($annotation)->create();

        $signalement->rejeter($this->admin);

        expect($annotation->fresh()?->masque_le)->toBeNull()
            ->and($signalement->fresh()?->statut)->toBe(StatutSignalement::Rejete);
    });

    test('la file montre les signalements en attente, et le bouton masque', function () {
        $enAttente = Signalement::factory()->for(Annotation::factory()->publique())->create();
        $dejaRejete = Signalement::factory()->for(Annotation::factory()->publique())->create();
        $dejaRejete->rejeter($this->admin);
        $this->actingAs($this->admin);

        Livewire::test(ListSignalements::class)
            ->assertCanSeeTableRecords([$enAttente])
            ->assertCanNotSeeTableRecords([$dejaRejete])
            ->callAction(TestAction::make('masquer')->table($enAttente));

        expect($enAttente->fresh()?->annotation?->masque_le)->not->toBeNull();
    });

    test('une annotation masquée n\'est plus lue en public', function () {
        $annotation = Annotation::factory()->publique()->create();
        $adresse = $annotation->load('adresse')->adresse->ecriture();
        Signalement::factory()->for($annotation)->create()->masquerContenu($this->admin);

        $this->getJson("/api/pages/{$adresse}/annotations")->assertOk()->assertJsonCount(0, 'data');
    });
});

describe('A8 · A10 : configurer ma bibliothèque', function () {
    test('le formulaire enregistre nom, description, inscriptions et quotas', function () {
        $this->actingAs($this->admin);

        Livewire::test(ReglagesBibliotheque::class)
            ->assertSet('data.nom', 'Babel 3')
            ->fillForm([
                'nom' => 'La bibliothèque du groupe 3',
                'description' => 'Toutes les pages possibles.',
                'inscriptions_ouvertes' => false,
                'quota_api' => 120,
                'quota_publications' => 5,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($this->ici->fresh()?->nom)->toBe('La bibliothèque du groupe 3')
            ->and(Reglage::inscriptionsOuvertes())->toBeFalse()
            ->and(Reglage::quotaApi())->toBe(120)
            ->and(Reglage::quotaPublications())->toBe(5);
    });

    test('B6 : l\'identité publiée vient de la ligne locale', function () {
        $this->ici->update(['nom' => 'Nouveau nom', 'description' => 'Nouvelle description']);

        $this->getJson('/api/identite')
            ->assertJsonPath('data.identifiant', 'babel-3')
            ->assertJsonPath('data.nom', 'Nouveau nom')
            ->assertJsonPath('data.description', 'Nouvelle description');
    });

    test('inscriptions fermées : 403 ERR_REGISTRATION_CLOSED', function () {
        Reglage::ecrire(Reglage::INSCRIPTIONS_OUVERTES, '0');

        $this->postJson('/register', ['pseudo' => 'x', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('erreurs.0.code', 'ERR_REGISTRATION_CLOSED');
    });

    test('le nom est obligatoire', function () {
        $this->actingAs($this->admin);

        Livewire::test(ReglagesBibliotheque::class)
            ->fillForm(['nom' => ''])
            ->call('save')
            ->assertHasFormErrors(['nom' => 'required']);
    });
});
