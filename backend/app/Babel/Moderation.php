<?php

namespace App\Babel;

use App\Enums\StatutTexteBanni;
use App\Exceptions\ErreurApi;
use App\Jobs\ReverifierPublications;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\TexteBanni;
use Babel\Moteur\Moteur;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Les textes bannis (A5, A7, règles 16 à 18, « Comment un texte banni masque ce qui est publié » du CDC).
 *
 * Idée : un même texte figure dans un nombre astronomique de pages, on ne peut pas toutes les trouver.
 * On vérifie donc les pages que quelqu'un a DÉSIGNÉES (enregistrées dans ADRESSE et visées par un contenu
 * publié). Une page n'est jamais masquée ni supprimée : on marque l'adresse (texte_banni_id), et ce qui est
 * publié vers elle n'est plus montré au public (règle 18).
 *
 * Limite assumée : une page d'une AUTRE bibliothèque ne peut être lue que chez elle (lecture chez un pair,
 * issue #34). En attendant, seules nos pages sont vérifiées ; les pages d'ailleurs le seront au passage
 * de la tâche de fond suivante, une fois la lecture distante en place.
 */
final class Moderation
{
    public function __construct(
        private readonly Moteur $moteur,
        private readonly Normaliseur $normaliseur,
    ) {}

    /**
     * A5 : déclarer un texte banni ici. Normalisé (règle 16 : stocké en clair, normalisé), appliqué tout
     * de suite (règle 17), avec notre bibliothèque pour origine. S'il existe déjà (même empreinte), on
     * l'applique au lieu d'en créer un second.
     */
    public function declarer(string $texte, ?string $motif = null): TexteBanni
    {
        $normalise = $this->normaliseur->normaliser($texte);
        if ($normalise === '' || mb_strlen($normalise) > Moteur::LONGUEUR_PAGE) {
            throw new ErreurApi([[
                'code' => $normalise === '' ? 'ERR_EMPTY_QUERY' : 'ERR_TEXT_TOO_LONG',
                'champ' => 'texte',
                'message' => 'Un texte banni fait de 1 à 3 200 caractères une fois normalisé.',
            ]]);
        }

        $banni = TexteBanni::query()->firstOrNew(['empreinte' => TexteBanni::empreinteDe($normalise)]);
        if (! $banni->exists) {
            $banni->fill(['texte' => $normalise, 'motif' => $motif, 'origine_id' => Bibliotheque::locale()->id]);
        }

        $this->appliquer($banni);

        return $banni;
    }

    /**
     * A5 (déclaré ici) ou A7 (proposé par un pair) : appliquer.
     *  1. le chercher avec le moteur, modes exact et bruit, et marquer les adresses obtenues si elles sont
     *     enregistrées (étape 1 du CDC) ;
     *  2. une tâche de fond relit les pages déjà désignées par du contenu publié (étape 2).
     */
    public function appliquer(TexteBanni $banni): void
    {
        $banni->forceFill(['statut' => StatutTexteBanni::Applique, 'decide_le' => now()])->save();

        $ici = Bibliotheque::locale();
        $empreintes = array_map(
            fn (string $mode) => Adresse::empreinteDe($this->moteur->chercher($banni->texte, $mode)),
            ['exact', 'bruit'],
        );
        Adresse::query()
            ->where('bibliotheque_id', $ici->id)
            ->whereIn('empreinte', $empreintes)
            ->whereNull('texte_banni_id') // règle 18 : le premier texte trouvé suffit
            ->update(['texte_banni_id' => $banni->id]);

        ReverifierPublications::dispatch();
    }

    /**
     * A5 lever un bannissement déclaré ici, ou A7 rejeter un texte proposé. Statut « rejete » : il n'est
     * plus publié (règle 17). Ses marques sont retirées, puis les pages concernées sont revérifiées contre
     * les autres textes appliqués (étape 5 du CDC). Rien n'est supprimé : on garde la trace de la décision.
     */
    public function rejeter(TexteBanni $banni): void
    {
        DB::transaction(function () use ($banni): void {
            $banni->forceFill(['statut' => StatutTexteBanni::Rejete, 'decide_le' => now()])->save();

            $marquees = Adresse::query()->where('texte_banni_id', $banni->id)->with('bibliotheque')->get();
            Adresse::query()->where('texte_banni_id', $banni->id)->update(['texte_banni_id' => null]);

            foreach ($marquees as $adresse) {
                $adresse->texte_banni_id = null;
                $this->verifier($adresse);
            }
        });
    }

    /**
     * Revérifie une adresse : la marque du premier texte appliqué trouvé dans sa page, ou retire la marque.
     * Une page d'une autre bibliothèque n'est pas vérifiable ici pour l'instant (#34) : laissée telle quelle.
     */
    public function verifier(Adresse $adresse): void
    {
        if (! $adresse->bibliotheque->est_locale) {
            return;
        }

        $trouve = $this->texteBanniDans($adresse);
        if ($trouve?->id !== $adresse->texte_banni_id) {
            $adresse->forceFill(['texte_banni_id' => $trouve?->id])->save();
        }
    }

    /**
     * Étape 3 du CDC : au moment de publier, lire la page visée (les 410 pages pour un livre, étape 4) et
     * y chercher les textes bannis appliqués. Renvoie le premier trouvé, ou null.
     */
    public function texteBanniDans(Adresse $adresse): ?TexteBanni
    {
        $appliques = $this->appliques();
        if ($appliques->isEmpty()) {
            return null; // le cas le plus fréquent : rien à lire
        }

        $livre = Adresse::livreDe($adresse->ecriture());
        $pages = $adresse->estUnLivre() ? range(1, Moteur::PAGES) : [$adresse->page];

        foreach ($pages as $numero) {
            $texte = $this->moteur->lire($livre.':'.$numero);
            foreach ($appliques as $banni) {
                if (str_contains($texte, $banni->texte)) {
                    return $banni;
                }
            }
        }

        return null;
    }

    /**
     * A7 : avant d'appliquer un texte proposé, montrer ce qui serait masqué (étape 4 de la modération en
     * réseau) : le nombre de nos pages désignées par un contenu publié qui le contiennent. Rien n'est écrit.
     */
    public function apercu(TexteBanni $banni): int
    {
        return $this->adressesPubliees()
            ->filter(function (Adresse $adresse) use ($banni): bool {
                $livre = Adresse::livreDe($adresse->ecriture());
                $pages = $adresse->estUnLivre() ? range(1, Moteur::PAGES) : [$adresse->page];
                foreach ($pages as $numero) {
                    if (str_contains($this->moteur->lire($livre.':'.$numero), $banni->texte)) {
                        return true;
                    }
                }

                return false;
            })
            ->count();
    }

    /**
     * Étape 2 du CDC : les adresses de chez nous désignées par ce qui est publié (annotation publique,
     * page d'une collection publique, lien court vers une page ou un livre). Lues une fois chacune.
     *
     * @return Collection<int, Adresse>
     */
    public function adressesPubliees(): Collection
    {
        $ici = Bibliotheque::locale();

        return Adresse::query()
            ->where('bibliotheque_id', $ici->id)
            ->where(fn (Builder $q) => $q
                ->whereHas('annotations', fn (Builder $a) => $a->where('est_publique', true))
                ->orWhereHas('collections', fn (Builder $c) => $c->where('est_publique', true))
                ->orWhereHas('liensCourts'))
            ->with('bibliotheque')
            ->get();
    }

    /** @return Collection<int, TexteBanni> */
    private function appliques(): Collection
    {
        return TexteBanni::query()->where('statut', StatutTexteBanni::Applique)->orderBy('id')->get();
    }

    /** Pour les contrôleurs : vérifier ce qu'on publie vers une adresse (annotation, collection, lien court). */
    public function verifierAvantPublication(Adresse $adresse): void
    {
        $adresse->loadMissing('bibliotheque');
        $this->verifier($adresse);
    }

    /** Pratique pour la tâche de fond et les tests. */
    public function toutReverifier(): int
    {
        $adresses = $this->adressesPubliees();
        foreach ($adresses as $adresse) {
            $this->verifier($adresse);
        }

        return $adresses->count();
    }
}
