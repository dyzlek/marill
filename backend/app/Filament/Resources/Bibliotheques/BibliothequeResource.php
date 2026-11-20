<?php

namespace App\Filament\Resources\Bibliotheques;

use App\Enums\EtatBibliotheque;
use App\Filament\Resources\Bibliotheques\Pages\ListBibliotheques;
use App\Models\Bibliotheque;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * A9 : voir l'état du réseau. Lecture seule : les lignes sont remplies par l'annuaire et par le contrôle des
 * pairs en tâche de fond (issues #31 et #32, qui attendent la plateforme). L'écran est prêt à les montrer :
 * bibliothèques qui répondent, échecs de suite, dernier contact, clés qui ont changé (règle 6).
 *
 * Pas de bouton « bloquer » : le CDC écarte toute liste blanche ou noire, le réseau est ouvert en lecture.
 */
class BibliothequeResource extends Resource
{
    protected static ?string $model = Bibliotheque::class;

    protected static ?string $slug = 'reseau';

    protected static ?string $modelLabel = 'bibliothèque';

    protected static ?string $navigationLabel = 'État du réseau';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('identifiant')->searchable(),
                TextColumn::make('nom'),
                IconColumn::make('est_locale')->label('Ici')->boolean(),
                TextColumn::make('etat')->badge()->color(fn (EtatBibliotheque $state): string => match ($state) {
                    EtatBibliotheque::EnLigne => 'success',
                    EtatBibliotheque::HorsLigne => 'danger',
                    EtatBibliotheque::Inconnu => 'gray',
                }),
                TextColumn::make('echecs_consecutifs')->label('Échecs de suite'),
                TextColumn::make('dernier_contact_le')->label('Dernier contact')->since()->placeholder('jamais'),
                TextColumn::make('url_actuelle')->label('URL')->placeholder('inconnue'),
                TextColumn::make('cle_changee_le')->label('Clé changée le')->dateTime('d/m/Y H:i')
                    ->placeholder('—')->color('danger'),
            ])
            ->defaultSort('est_locale', 'desc')
            ->filters([
                SelectFilter::make('etat')->options([
                    EtatBibliotheque::EnLigne->value => 'En ligne',
                    EtatBibliotheque::HorsLigne->value => 'Hors ligne',
                    EtatBibliotheque::Inconnu->value => 'Inconnu',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBibliotheques::route('/'),
        ];
    }
}
