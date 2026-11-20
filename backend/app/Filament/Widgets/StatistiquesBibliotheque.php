<?php

namespace App\Filament\Widgets;

use App\Babel\Statistiques;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** A11 : les chiffres en tête du tableau de bord. Le calcul est dans App\Babel\Statistiques. */
class StatistiquesBibliotheque extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = app(Statistiques::class);

        return [
            Stat::make('Comptes', $stats->comptes())
                ->description($stats->membresActifs().' actifs sur '.Statistiques::JOURS_ACTIF.' jours'),
            Stat::make('Pages lues (7 jours)', $stats->pagesLues())
                ->chart($stats->pagesLuesParJour()),
            Stat::make('Recherches (7 jours)', $stats->recherches()),
            Stat::make('Signalements en attente', $stats->signalementsEnAttente())
                ->color($stats->signalementsEnAttente() > 0 ? 'warning' : 'success'),
        ];
    }
}
