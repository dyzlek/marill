<?php

namespace App\Filament\Widgets;

use App\Models\Adresse;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** A11 : les pages les plus partagées, par visites de leurs liens courts. */
class PagesLesPlusPartagees extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pages les plus partagées';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Adresse::query()
                ->whereHas('liensCourts')
                ->with('bibliotheque')
                ->withSum('liensCourts', 'visites')
                ->withCount('liensCourts')
                ->orderByDesc('liens_courts_sum_visites'))
            ->paginated([5])
            ->columns([
                TextColumn::make('adresse')
                    ->state(fn (Adresse $record): string => $record->ecriture())
                    ->limit(60)
                    ->tooltip(fn (Adresse $record): string => $record->ecriture()),
                TextColumn::make('bibliotheque.identifiant')->label('Bibliothèque'),
                TextColumn::make('liens_courts_count')->label('Liens'),
                TextColumn::make('liens_courts_sum_visites')->label('Visites'),
            ]);
    }
}
