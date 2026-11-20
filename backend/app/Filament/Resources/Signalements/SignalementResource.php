<?php

namespace App\Filament\Resources\Signalements;

use App\Enums\StatutSignalement;
use App\Filament\Resources\Signalements\Pages\ListSignalements;
use App\Filament\Resources\Signalements\Tables\SignalementsTable;
use App\Models\Signalement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * A4 : la file des signalements. Pas de création ni de modification à la main : un signalement vient
 * d'un visiteur ou d'un membre (V17), l'administrateur décide (masquer, rétablir, rejeter).
 */
class SignalementResource extends Resource
{
    protected static ?string $model = Signalement::class;

    protected static ?string $modelLabel = 'signalement';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    /** Le nombre de signalements en attente à côté du menu : la file se voit sans l'ouvrir. */
    public static function getNavigationBadge(): ?string
    {
        $enAttente = Signalement::query()->where('statut', StatutSignalement::EnAttente)->count();

        return $enAttente > 0 ? (string) $enAttente : null;
    }

    public static function table(Table $table): Table
    {
        return SignalementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSignalements::route('/'),
        ];
    }
}
