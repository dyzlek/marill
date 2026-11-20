<?php

namespace App\Babel;

use App\Enums\EtatBibliotheque;
use App\Exceptions\ErreurApi;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * M12 · règle 20 : recréer, chez nous, ce qu'un fichier d'export apporte.
 *
 * Seuls les marque-pages, collections et annotations sont recréés (règle 20) ; votes, recherches et liens
 * courts restent dans le fichier. Collections et annotations arrivent PRIVÉES (migration, étape 5) : le membre
 * republie ce qu'il veut, et ce qu'il publie passe par notre modération.
 *
 * Chaque adresse garde sa bibliothèque d'origine. La conversion vers la nôtre (lire la page chez l'origine,
 * puis chercher(texte, 'page') ici) a besoin du réseau : elle viendra avec la lecture chez un pair (#34).
 * Une bibliothèque que nous ne connaissons pas encore est ajoutée par son identifiant, état « inconnu » :
 * l'annuaire (#31) lui donnera son URL, l'identifiant étant stable (règle 6).
 */
final class ImportCompte
{
    public function __construct(private readonly Validateur $validateur) {}

    /**
     * @param  array<string, mixed>  $fichier  déjà validé par ImporterCompteRequest (forme du contrat)
     * @return array{marque_pages: int, collections: int, annotations: int, doublons: int}
     */
    public function importer(User $membre, array $fichier): array
    {
        /** @var list<array{adresse: string, bibliotheque: string, titre?: ?string}> $marquePages */
        $marquePages = $fichier['marque_pages'] ?? [];
        /** @var list<array{nom: string, description?: ?string, pages?: list<array{adresse: string, bibliotheque: string}>}> $collections */
        $collections = $fichier['collections'] ?? [];
        /** @var list<array{adresse: string, bibliotheque: string, contenu: string}> $annotations */
        $annotations = $fichier['annotations'] ?? [];

        // 1. Toutes les adresses passent F5 AVANT d'écrire quoi que ce soit : une seule fausse, rien n'est importé.
        $this->verifier($marquePages, 'marque_pages');
        foreach ($collections as $i => $collection) {
            $this->verifier($collection['pages'] ?? [], "collections.$i.pages");
        }
        $this->verifier($annotations, 'annotations');

        // 2. Puis tout dans une transaction : un import à moitié fait n'existe pas.
        return DB::transaction(function () use ($membre, $marquePages, $collections, $annotations): array {
            $rapport = ['marque_pages' => 0, 'collections' => 0, 'annotations' => 0, 'doublons' => 0];

            foreach ($marquePages as $m) {
                $adresse = $this->enregistrer($m);
                // Règle 7 (et règle 20 : « fusionnés ») : déjà gardée, on n'en fait pas un second.
                if ($membre->marquePages()->where('adresse_id', $adresse->id)->exists()) {
                    $rapport['doublons']++;

                    continue;
                }
                $membre->marquePages()->create(['adresse_id' => $adresse->id, 'titre' => $m['titre'] ?? null]);
                $rapport['marque_pages']++;
            }

            foreach ($collections as $c) {
                $collection = $membre->collections()->create([
                    'nom' => $c['nom'],
                    'description' => $c['description'] ?? null,
                    'est_publique' => false,
                ]);
                // L'ordre du fichier fait foi : positions 1..n, une même page peut revenir (règle 8).
                foreach ($c['pages'] ?? [] as $position => $page) {
                    $collection->pages()->attach($this->enregistrer($page)->id, ['position' => $position + 1]);
                }
                $rapport['collections']++;
            }

            foreach ($annotations as $a) {
                $membre->annotations()->create([
                    'adresse_id' => $this->enregistrer($a)->id,
                    'contenu' => $a['contenu'],
                    'est_publique' => false,
                ]);
                $rapport['annotations']++;
            }

            return $rapport;
        });
    }

    /** @param  list<array{adresse: string}>  $lignes */
    private function verifier(array $lignes, string $champ): void
    {
        foreach ($lignes as $i => $ligne) {
            $resultat = $this->validateur->validerPage($ligne['adresse']);
            if (! $resultat->valide) {
                // Le champ dit où se trouve l'erreur dans le fichier : marque_pages.3.adresse.
                throw new ErreurApi(array_map(
                    fn (array $e) => [...$e, 'champ' => "$champ.$i.adresse"],
                    $resultat->erreurs,
                ));
            }
        }
    }

    /** @param  array{adresse: string, bibliotheque: string}  $ligne */
    private function enregistrer(array $ligne): Adresse
    {
        return Adresse::enregistrer($this->bibliotheque($ligne['bibliotheque']), $ligne['adresse']);
    }

    /** @var array<string, Bibliotheque> */
    private array $connues = [];

    private function bibliotheque(string $identifiant): Bibliotheque
    {
        return $this->connues[$identifiant] ??= Bibliotheque::query()->where('identifiant', $identifiant)->first()
            ?? Bibliotheque::query()->create([
                'identifiant' => $identifiant,
                'nom' => $identifiant,
                'url_actuelle' => '', // inconnue : l'annuaire la donnera
                'etat' => EtatBibliotheque::Inconnu,
                'echecs_consecutifs' => 0,
            ]);
    }
}
