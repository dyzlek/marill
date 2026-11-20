<?php

namespace App\Babel;

use App\Enums\StatutSignalement;
use App\Models\ActiviteJour;
use App\Models\Adresse;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * A11 : les chiffres du back-office, calculés à la demande. Règle 21 : pages lues et recherches viennent
 * des compteurs par jour (ACTIVITE_JOUR), jamais d'une ligne par lecture. Le reste se compte dans les tables.
 *
 * Séparé du widget Filament : on teste les chiffres sans écran, et l'écran ne fait qu'afficher.
 */
final class Statistiques
{
    /** « Actif » = connecté dans les 30 derniers jours. Le CDC ne le définit pas : choix du groupe. */
    public const JOURS_ACTIF = 30;

    public function comptes(): int
    {
        return User::query()->count();
    }

    public function membresActifs(): int
    {
        return User::query()->where('derniere_connexion_le', '>=', now()->subDays(self::JOURS_ACTIF))->count();
    }

    /** Total sur les $jours derniers jours, aujourd'hui compris. */
    public function pagesLues(int $jours = 7): int
    {
        return (int) $this->periode($jours)->sum('pages_lues');
    }

    public function recherches(int $jours = 7): int
    {
        return (int) $this->periode($jours)->sum('recherches');
    }

    /** @return list<float> un nombre par jour, du plus ancien au plus récent (0 pour un jour sans ligne), pour un graphique */
    public function pagesLuesParJour(int $jours = 7): array
    {
        $parJour = $this->periode($jours)->pluck('pages_lues', 'jour');

        return array_map(
            fn (int $i) => (float) ($parJour[now()->subDays($i)->toDateString()] ?? 0),
            range($jours - 1, 0),
        );
    }

    public function signalementsEnAttente(): int
    {
        return Signalement::query()->where('statut', StatutSignalement::EnAttente)->count();
    }

    /**
     * Pages les plus partagées : les adresses dont les liens courts ont reçu le plus de visites.
     *
     * @return Collection<int, Adresse>
     */
    public function pagesLesPlusPartagees(int $nombre = 5): Collection
    {
        return Adresse::query()
            ->whereHas('liensCourts')
            ->withSum('liensCourts', 'visites')
            ->withCount('liensCourts')
            ->orderByDesc('liens_courts_sum_visites')
            ->limit($nombre)
            ->get();
    }

    /** @return Builder<ActiviteJour> */
    private function periode(int $jours): Builder
    {
        return ActiviteJour::query()->where('jour', '>=', now()->subDays($jours - 1)->toDateString());
    }
}
