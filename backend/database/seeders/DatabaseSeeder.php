<?php

namespace Database\Seeders;

use App\Babel\Hasard;
use App\Enums\EtatBibliotheque;
use App\Enums\StatutSignalement;
use App\Enums\StatutTexteBanni;
use App\Models\ActiviteJour;
use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\MarquePage;
use App\Models\Recherche;
use App\Models\Reglage;
use App\Models\Signalement;
use App\Models\TexteBanni;
use App\Models\User;
use Babel\Moteur\Moteur;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : php artisan migrate:fresh --seed.
 *
 * Pas de WithoutModelEvents (contrairement au starter kit) : les vérifications des modèles
 * (une seule bibliothèque locale, une seule cible par lien court ou signalement) restent actives,
 * le seeder passe par les mêmes règles que l'application.
 *
 * Comptes de test (mot de passe « password », celui de UserFactory) : admin@example.com (admin),
 * test@example.com (membre). Valeurs de développement seulement, jamais en production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(Moteur $moteur, Hasard $hasard): void
    {
        // Règle 5 : notre bibliothèque est une ligne comme les autres. Son identité vient de la
        // configuration (config/bibliotheque.php) et son empreinte du moteur : jamais la clé.
        $ici = Bibliotheque::factory()->locale()->create([
            'identifiant' => config('bibliotheque.identifiant'),
            'nom' => config('bibliotheque.nom'),
            'description' => config('bibliotheque.description'),
            'url_actuelle' => config('app.url'),
            'empreinte_cle' => $moteur->empreinte(),
        ]);
        $pairs = Bibliotheque::factory()->count(3)->create();
        $pairs->first()?->update(['etat' => EtatBibliotheque::HorsLigne, 'echecs_consecutifs' => 3]);

        Reglage::query()->create(['cle' => 'inscriptions_ouvertes', 'valeur' => '1']);

        $admin = User::factory()->admin()->create(['pseudo' => 'admin', 'email' => 'admin@example.com']);
        $testeur = User::factory()->create(['pseudo' => 'testeur', 'email' => 'test@example.com']);
        $membres = User::factory()->count(5)->create();

        // Des adresses d'ici, enregistrées comme le fera l'application : validées, puis découpées.
        $premiere = Adresse::enregistrer($ici, '0-w1-s1-v1:1');
        $phrase = Adresse::enregistrer($ici, $moteur->chercher('la bibliotheque de babel', 'exact'));
        $auHasard = Adresse::enregistrer($ici, $hasard->auHasard()); // un hexagone de ~3 000 caractères
        $livre = Adresse::enregistrer($ici, '0-w1-s1-v1'); // un livre entier : seul un lien court le vise
        $ailleurs = Adresse::factory()->for($pairs->last(), 'bibliotheque')->create(); // page d'un pair (M13)
        $pages = collect([$premiere, $phrase, $auHasard, $ailleurs]);

        foreach ($pages as $adresse) {
            MarquePage::factory()->for($testeur)->for($adresse)->create();
        }

        // Règle 8 : une collection ordonnée, où une même page peut revenir.
        $collection = Collection::factory()->publique()->for($testeur)->create(['nom' => 'Premières lectures']);
        $collection->pages()->attach($premiere->id, ['position' => 1]);
        $collection->pages()->attach($phrase->id, ['position' => 2]);
        $collection->pages()->attach($premiere->id, ['position' => 3]);
        Collection::factory()->for($testeur)->create(['nom' => 'Brouillon privé']);

        $annotation = Annotation::factory()->publique()->for($testeur)->for($phrase)
            ->create(['contenu' => 'La phrase est au milieu de la page.']);
        Annotation::factory()->for($membres->first())->for($premiere)->create(['est_publique' => false]);

        // Règle 10 : un vote par membre et par page, par la table pivot.
        foreach ($membres as $membre) {
            $membre->pagesVotees()->attach($phrase->id, ['valeur' => 1]);
        }
        $testeur->pagesVotees()->attach($premiere->id, ['valeur' => -1]);

        // Règle 11 : un lien de membre vers une page avec un passage, un lien de visiteur vers un livre.
        LienCourt::factory()->parUnMembre($testeur)->for($phrase)->create(['passage' => 'la bibliotheque de babel']);
        LienCourt::factory()->for($livre)->create();
        LienCourt::factory()->parUnMembre($testeur)->versUneCollection($collection)->create();

        // Règle 13 : un visiteur signale une annotation ; un membre a signalé une collection, traité par l'admin.
        Signalement::factory()->for($annotation)->create(['motif' => 'Hors sujet']);
        $traite = Signalement::factory()->make(['annotation_id' => null, 'collection_id' => $collection->id, 'motif' => 'Test de modération']);
        $traite->auteur()->associate($membres->last());
        $traite->traitePar()->associate($admin);
        $traite->forceFill(['statut' => StatutSignalement::Rejete, 'traite_le' => now()])->save();

        // Règles 16 et 17 : un texte banni ici (appliqué) et un relevé chez un pair (proposé).
        TexteBanni::factory()->for($ici, 'origine')->create();
        TexteBanni::factory()->for($pairs->first(), 'origine')
            ->create(['statut' => StatutTexteBanni::Propose, 'decide_le' => null]);

        Recherche::factory()->count(3)->for($testeur)->create();

        // Règle 21 : des compteurs par jour sur la dernière semaine.
        foreach (range(6, 0) as $jours) {
            ActiviteJour::query()->create([
                'jour' => now()->subDays($jours)->toDateString(),
                'pages_lues' => fake()->numberBetween(20, 400),
                'recherches' => fake()->numberBetween(5, 80),
            ]);
        }
    }
}
