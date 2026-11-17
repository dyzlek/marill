<?php

/*
 * Issue #15 : les 14 tables du MLD et leurs règles (docs/cahier-des-charges.md, livrable 3).
 *
 * Deux barrières par règle, testées chacune :
 * - le modèle refuse avant d'écrire (RegleNonRespectee, message en français) ;
 * - la base refuse même une écriture qui contourne les modèles (DB::table()->insert, comme une requête SQL
 *   tapée à la main) : QueryException levée par une contrainte UNIQUE, CHECK ou FOREIGN KEY.
 */

use App\Babel\Hasard;
use App\Enums\Role;
use App\Exceptions\RegleNonRespectee;
use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\MarquePage;
use App\Models\Recherche;
use App\Models\Signalement;
use App\Models\TexteBanni;
use App\Models\User;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('migrate:fresh --seed remplit les 14 tables', function () {
    $this->seed();

    $tables = ['users', 'bibliotheques', 'adresses', 'marque_pages', 'collections', 'collection_pages',
        'annotations', 'votes', 'liens_courts', 'signalements', 'textes_bannis', 'recherches',
        'activites_jour', 'reglages'];

    foreach ($tables as $table) {
        expect(DB::table($table)->count())->toBeGreaterThan(0, "table {$table} vide");
    }
    expect(Bibliotheque::locale()->identifiant)->toBe(config('bibliotheque.identifiant'));
});

test('règle 1 : aucune table ne stocke le texte d\'une page ni la clé', function () {
    // Les seules colonnes de texte long sont celles du MLD qui ne sont pas des pages : le texte banni,
    // le passage d'un lien court et le texte d'une recherche (des extraits, pas des pages lues).
    expect(Schema::hasColumn('adresses', 'texte'))->toBeFalse()
        ->and(Schema::hasColumn('bibliotheques', 'cle'))->toBeFalse()
        ->and(Schema::getColumnListing('bibliotheques'))->toContain('empreinte_cle');
});

describe('règle 5 : une seule bibliothèque locale', function () {
    test('le modèle refuse une seconde bibliothèque locale', function () {
        Bibliotheque::factory()->locale()->create();

        Bibliotheque::factory()->locale()->create();
    })->throws(RegleNonRespectee::class, 'Règle 5');

    test('la base la refuse aussi (index unique partiel)', function () {
        Bibliotheque::factory()->locale()->create();
        $ligne = Bibliotheque::factory()->make(['est_locale' => true])->getAttributes();

        DB::table('bibliotheques')->insert($ligne);
    })->throws(QueryException::class, 'UNIQUE');

    test('les autres bibliothèques sont aussi nombreuses qu\'on veut', function () {
        Bibliotheque::factory()->locale()->create();
        Bibliotheque::factory()->count(3)->create();

        expect(Bibliotheque::query()->count())->toBe(4);
    });
});

describe('règles 2 à 4 : adresses', function () {
    test('une adresse n\'est enregistrée qu\'une fois par bibliothèque', function () {
        $ici = Bibliotheque::factory()->locale()->create();

        $a = Adresse::enregistrer($ici, '0-w1-s1-v1:1');
        $b = Adresse::enregistrer($ici, '0-w1-s1-v1:1');

        expect($b->id)->toBe($a->id)
            ->and($a->empreinte)->toBe(hash('sha256', '0-w1-s1-v1:1'))
            ->and($a->empreinte_livre)->toBe(hash('sha256', '0-w1-s1-v1'));
    });

    test('la même adresse dans une autre bibliothèque est une autre ligne', function () {
        $ici = Bibliotheque::factory()->locale()->create();
        $ailleurs = Bibliotheque::factory()->create();

        expect(Adresse::enregistrer($ailleurs, '0-w1-s1-v1:1')->id)
            ->not->toBe(Adresse::enregistrer($ici, '0-w1-s1-v1:1')->id);
    });

    test('la base refuse un doublon (bibliotheque_id, empreinte)', function () {
        $adresse = Adresse::factory()->create();
        $doublon = collect($adresse->getAttributes())->except(['id', 'created_at'])->all();

        DB::table('adresses')->insert($doublon);
    })->throws(QueryException::class, 'UNIQUE');

    test('une adresse mal écrite n\'est pas enregistrée', function () {
        Adresse::enregistrer(Bibliotheque::factory()->locale()->create(), '00-w1-s1-v1:1');
    })->throws(RegleNonRespectee::class, 'Règle 2');

    test('une adresse tirée au hasard (hexagone de ~3 000 caractères) se retrouve à l\'identique', function () {
        $ecrite = app(Hasard::class)->auHasard();

        $adresse = Adresse::enregistrer(Bibliotheque::factory()->locale()->create(), $ecrite);

        expect($adresse->fresh()?->ecriture())->toBe($ecrite);
    });

    test('un livre est une adresse sans page, et les pages d\'un livre partagent son empreinte', function () {
        $ici = Bibliotheque::factory()->locale()->create();

        $livre = Adresse::enregistrer($ici, '1a-w2-s3-v4');
        $page = Adresse::enregistrer($ici, '1a-w2-s3-v4:410');

        expect($livre->estUnLivre())->toBeTrue()
            ->and($page->estUnLivre())->toBeFalse()
            ->and($page->empreinte_livre)->toBe($livre->empreinte);
    });
});

test('règle 7 : un membre ne garde qu\'une fois la même page', function () {
    $marquePage = MarquePage::factory()->create();

    MarquePage::factory()->create(['user_id' => $marquePage->user_id, 'adresse_id' => $marquePage->adresse_id]);
})->throws(QueryException::class, 'UNIQUE');

describe('règle 8 : une collection est ordonnée', function () {
    test('les pages se relisent dans l\'ordre, et une page peut revenir', function () {
        $collection = Collection::factory()->create();
        [$a, $b] = Adresse::factory()->count(2)->create();

        $collection->pages()->attach($b->id, ['position' => 2]);
        $collection->pages()->attach($a->id, ['position' => 1]);
        $collection->pages()->attach($b->id, ['position' => 3]);

        expect($collection->pages()->pluck('adresses.id')->all())->toBe([$a->id, $b->id, $b->id])
            ->and($collection->pages()->first()?->rangement->position)->toBe(1);
    });

    test('deux pages ne peuvent pas avoir la même position (clé composée)', function () {
        $collection = Collection::factory()->create();
        [$a, $b] = Adresse::factory()->count(2)->create();

        $collection->pages()->attach($a->id, ['position' => 1]);
        $collection->pages()->attach($b->id, ['position' => 1]);
    })->throws(QueryException::class, 'UNIQUE');
});

describe('règle 10 : un vote par membre et par page', function () {
    test('un second vote sur la même page est refusé (clé composée)', function () {
        $user = User::factory()->create();
        $adresse = Adresse::factory()->create();

        $user->pagesVotees()->attach($adresse->id, ['valeur' => 1]);
        $user->pagesVotees()->attach($adresse->id, ['valeur' => -1]);
    })->throws(QueryException::class, 'UNIQUE');

    test('un vote vaut +1 ou −1, rien d\'autre (CHECK)', function () {
        User::factory()->create()->pagesVotees()->attach(Adresse::factory()->create()->id, ['valeur' => 2]);
    })->throws(QueryException::class, 'vote_plus_ou_moins_un');

    test('les votes d\'une page s\'additionnent, la base date chaque vote', function () {
        $adresse = Adresse::factory()->create();
        foreach ([1, 1, -1] as $valeur) {
            User::factory()->create()->pagesVotees()->attach($adresse->id, ['valeur' => $valeur]);
        }

        expect((int) $adresse->votes()->sum('valeur'))->toBe(1)
            ->and($adresse->votes()->whereNull('created_at')->exists())->toBeFalse();
    });
});

describe('règle 11 : un lien court a exactement une cible', function () {
    test('le modèle refuse deux cibles', function () {
        LienCourt::factory()->create(['collection_id' => Collection::factory()]);
    })->throws(RegleNonRespectee::class, 'exactement une cible');

    test('le modèle refuse un lien sans cible', function () {
        LienCourt::factory()->create(['adresse_id' => null]);
    })->throws(RegleNonRespectee::class, 'exactement une cible');

    test('un lien de visiteur expire obligatoirement', function () {
        LienCourt::factory()->create(['expire_le' => null]);
    })->throws(RegleNonRespectee::class, 'expire obligatoirement');

    test('un passage ne va qu\'avec une adresse', function () {
        LienCourt::factory()->versUneCollection()->parUnMembre()->create(['passage' => 'bonjour']);
    })->throws(RegleNonRespectee::class, 'passage');

    test('la base refuse les mêmes lignes (CHECK nommés)', function (array $colonnes, string $contrainte) {
        $ligne = ['slug' => 'abcdefgh', 'visites' => 0, 'adresse_id' => Adresse::factory()->create()->id,
            'collection_id' => null, 'user_id' => null, 'expire_le' => now(), 'passage' => null];

        // Pest appelle les closures du jeu de données dans le test (base déjà prête) : $colonnes est un tableau.
        expect(fn () => DB::table('liens_courts')->insert(array_merge($ligne, $colonnes)))
            ->toThrow(QueryException::class, $contrainte);
    })->with([
        'deux cibles' => [fn () => ['collection_id' => Collection::factory()->create()->id], 'lien_court_une_seule_cible'],
        'aucune cible' => [fn () => ['adresse_id' => null], 'lien_court_une_seule_cible'],
        'visiteur sans expiration' => [fn () => ['expire_le' => null], 'lien_court_visiteur_expire'],
        'passage vers une collection' => [fn () => ['adresse_id' => null, 'collection_id' => Collection::factory()->create()->id, 'passage' => 'x'], 'lien_court_passage_vers_une_adresse'],
    ]);

    test('un membre crée un lien sans expiration, un lien peut viser un livre', function () {
        $lien = LienCourt::factory()->parUnMembre()->for(Adresse::factory()->livre())->create();

        expect($lien->expire_le)->toBeNull()
            ->and($lien->adresse()->first()?->estUnLivre())->toBeTrue();
    });
});

describe('règle 13 : un signalement vise exactement un contenu', function () {
    test('le modèle refuse un signalement sans cible ou avec deux cibles', function (array $cibles) {
        expect(fn () => Signalement::factory()->create($cibles))
            ->toThrow(RegleNonRespectee::class, 'Règle 13');
    })->with([
        'aucune' => [['annotation_id' => null]],
        'deux' => [['collection_id' => fn () => Collection::factory()->create()->id]],
    ]);

    test('la base refuse un signalement sans cible (CHECK)', function () {
        DB::table('signalements')->insert(['motif' => 'x', 'statut' => 'en_attente']);
    })->throws(QueryException::class, 'signalement_une_seule_cible');

    test('supprimer le contenu signalé supprime ses signalements', function () {
        $signalement = Signalement::factory()->create();

        Annotation::query()->whereKey($signalement->annotation_id)->delete();

        expect(Signalement::query()->count())->toBe(0);
    });
});

test('règle 18 : retirer un texte banni retire sa marque, pas l\'adresse', function () {
    $texte = TexteBanni::factory()->create();
    $adresse = Adresse::factory()->create(['texte_banni_id' => $texte->id]);

    $texte->delete();

    expect($adresse->fresh()?->texte_banni_id)->toBeNull();
});

test('règle 19 : supprimer un compte efface ses données, garde ses signalements sans auteur', function () {
    $user = User::factory()->create();
    $adresse = Adresse::factory()->create();
    MarquePage::factory()->for($user)->for($adresse)->create();
    $collection = Collection::factory()->for($user)->create();
    $collection->pages()->attach($adresse->id, ['position' => 1]);
    Annotation::factory()->for($user)->for($adresse)->create();
    $user->pagesVotees()->attach($adresse->id, ['valeur' => 1]);
    LienCourt::factory()->parUnMembre($user)->create();
    Recherche::factory()->for($user)->create();
    $signalement = Signalement::factory()->create(['auteur_id' => $user->id]);

    $user->delete();

    foreach (['marque_pages', 'collections', 'collection_pages', 'votes', 'liens_courts', 'recherches'] as $table) {
        expect(DB::table($table)->count())->toBe(0, "{$table} non vidée");
    }
    expect(Annotation::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($signalement->fresh()?->auteur_id)->toBeNull()
        ->and(Adresse::query()->whereKey($adresse->id)->exists())->toBeTrue(); // l'adresse est partagée, elle reste
});

test('le rôle ne s\'affecte pas par un formulaire (hors de #[Fillable])', function () {
    $user = User::query()->create(['pseudo' => 'malin', 'email' => 'malin@example.com', 'password' => 'secret', 'role' => 'admin']);

    expect($user->fresh()?->role)->toBe(Role::Membre)
        ->and($user->fresh()?->isAdmin())->toBeFalse()
        ->and(User::factory()->admin()->create()->isAdmin())->toBeTrue();
});

test('le pseudo est unique', function () {
    User::factory()->create(['pseudo' => 'babel']);
    User::factory()->create(['pseudo' => 'babel']);
})->throws(QueryException::class, 'UNIQUE');

test('preventLazyLoading : une relation non chargée lève une exception hors production', function () {
    MarquePage::factory()->count(2)->create();

    // Avec with(), une seule requête de plus pour toutes les adresses : pas d'exception.
    expect(MarquePage::query()->with('adresse')->get()->map(fn (MarquePage $m) => $m->adresse->id))->toHaveCount(2);

    // Sans with(), chaque ->adresse lancerait une requête cachée (N+1) : refusé.
    MarquePage::query()->get()->each(fn (MarquePage $m) => $m->adresse);
})->throws(LazyLoadingViolationException::class);
