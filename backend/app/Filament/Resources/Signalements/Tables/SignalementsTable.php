<?php

namespace App\Filament\Resources\Signalements\Tables;

use App\Enums\StatutSignalement;
use App\Models\Annotation;
use App\Models\Collection;
use App\Models\Signalement;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * A4 : masquer ou rétablir le contenu publié, ou rejeter le signalement.
 *
 * La file s'ouvre sur les signalements en attente (filtre par défaut). Les trois contenus possibles sont
 * chargés avec la liste (with) : sans ça, chaque ligne lancerait ses propres requêtes (N+1), et
 * preventLazyLoading lèverait une exception hors production.
 */
class SignalementsTable
{
    public static function configure(Table $table): Table
    {
        $admin = function (): User {
            /** @var User */
            return Auth::user();
        };

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['annotation', 'collection', 'lienCourt', 'auteur', 'traitePar']))
            ->columns([
                TextColumn::make('type')
                    ->state(fn (Signalement $record): string => match (true) {
                        $record->annotation_id !== null => 'annotation',
                        $record->collection_id !== null => 'collection',
                        default => 'lien court',
                    })
                    ->badge(),
                TextColumn::make('contenu')
                    ->label('Contenu signalé')
                    ->state(fn (Signalement $record): string => self::apercu($record))
                    ->wrap(),
                TextColumn::make('motif')->limit(80)->wrap(),
                TextColumn::make('auteur.pseudo')->label('Signalé par')->placeholder('visiteur'),
                TextColumn::make('masque')
                    ->label('Contenu masqué')
                    ->state(fn (Signalement $record): string => $record->contenu()->masque_le !== null ? 'oui' : 'non'),
                TextColumn::make('statut')->badge(),
                TextColumn::make('traitePar.pseudo')->label('Décidé par')->placeholder('—'),
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at')
            ->filters([
                SelectFilter::make('statut')
                    ->options([
                        StatutSignalement::EnAttente->value => 'En attente',
                        StatutSignalement::Traite->value => 'Traité',
                        StatutSignalement::Rejete->value => 'Rejeté',
                    ])
                    ->default(StatutSignalement::EnAttente->value),
            ])
            ->recordActions([
                Action::make('masquer')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Le contenu n’est plus montré au public. Il n’est pas supprimé : on peut le rétablir.')
                    ->visible(fn (Signalement $record): bool => $record->contenu()->masque_le === null)
                    ->action(fn (Signalement $record) => $record->masquerContenu($admin())),
                Action::make('retablir')
                    ->label('Rétablir')
                    ->color('success')
                    ->visible(fn (Signalement $record): bool => $record->contenu()->masque_le !== null)
                    ->action(fn (Signalement $record) => $record->retablirContenu($admin())),
                Action::make('rejeter')
                    ->color('gray')
                    ->visible(fn (Signalement $record): bool => $record->statut === StatutSignalement::EnAttente)
                    ->action(fn (Signalement $record) => $record->rejeter($admin())),
            ]);
    }

    /** Ce que l'administrateur doit lire pour décider : le texte de l'annotation, le nom de la collection, le slug. */
    private static function apercu(Signalement $signalement): string
    {
        $contenu = $signalement->contenu();

        return match (true) {
            $contenu instanceof Annotation => Str::limit($contenu->contenu, 200),
            $contenu instanceof Collection => 'Collection « '.$contenu->nom.' »',
            default => 'Lien /'.$contenu->slug,
        };
    }
}
