<?php

namespace App\Babel;

use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\MarquePage;
use App\Models\Recherche;
use App\Models\User;

/**
 * M11 · règle 20 : tout ce que le membre a enregistré, dans un fichier. Le profil sans le mot de passe,
 * les marque-pages, collections, annotations, votes, recherches et liens courts, chaque adresse avec
 * l'identifiant de sa bibliothèque (une adresse ne veut rien dire sans elle : chaque clé donne d'autres textes).
 *
 * PROVISOIRE : le format du fichier fait partie du contrat commun (toutes les bibliothèques lisent le fichier
 * des autres). En attendant, « format » porte une version : l'import refuse ce qu'il ne connaît pas.
 */
final class ExportCompte
{
    public const FORMAT = 'babel-export/1';

    /** @return array<string, mixed> */
    public function pour(User $membre): array
    {
        // Tout est chargé en une fois, relations comprises (with) : quelques requêtes, pas une par ligne.
        $membre->load([
            'marquePages.adresse.bibliotheque',
            'collections.pages.bibliotheque',
            'annotations.adresse.bibliotheque',
            'pagesVotees.bibliotheque',
            'recherches',
            'liensCourts.adresse.bibliotheque',
            'liensCourts.collection',
        ]);

        return [
            'format' => self::FORMAT,
            'bibliotheque' => Bibliotheque::locale()->identifiant,
            'exporte_le' => now()->toIso8601String(),
            'profil' => [
                'pseudo' => $membre->pseudo,
                'email' => $membre->email,
                'cree_le' => $membre->created_at?->toIso8601String(),
            ],
            'marque_pages' => $membre->marquePages->map(fn (MarquePage $m) => [
                ...$this->adresse($m->adresse),
                'titre' => $m->titre,
                'cree_le' => $m->created_at?->toIso8601String(),
            ])->all(),
            'collections' => $membre->collections->map(fn (Collection $c) => [
                'nom' => $c->nom,
                'description' => $c->description,
                'est_publique' => $c->est_publique,
                'pages' => $c->pages->map(fn (Adresse $a) => ['position' => $a->rangement->position, ...$this->adresse($a)])->all(),
                'cree_le' => $c->created_at?->toIso8601String(),
            ])->all(),
            'annotations' => $membre->annotations->map(fn (Annotation $a) => [
                ...$this->adresse($a->adresse),
                'contenu' => $a->contenu,
                'est_publique' => $a->est_publique,
                'cree_le' => $a->created_at?->toIso8601String(),
            ])->all(),
            'votes' => $membre->pagesVotees->map(fn (Adresse $a) => [...$this->adresse($a), 'valeur' => $a->vote->valeur])->all(),
            'recherches' => $membre->recherches->map(fn (Recherche $r) => [
                'texte' => $r->texte,
                'mode' => $r->mode->value,
                'portee' => $r->portee->value,
                'cree_le' => $r->created_at?->toIso8601String(),
            ])->all(),
            'liens_courts' => $membre->liensCourts->map(fn (LienCourt $l) => [
                'slug' => $l->slug,
                'cible' => $l->adresse !== null ? $this->adresse($l->adresse) : ['collection' => $l->collection?->nom],
                'passage' => $l->passage,
                'visites' => $l->visites,
                'cree_le' => $l->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /** @return array{adresse: string, bibliotheque: string} */
    private function adresse(Adresse $adresse): array
    {
        return ['adresse' => $adresse->ecriture(), 'bibliotheque' => $adresse->bibliotheque->identifiant];
    }
}
