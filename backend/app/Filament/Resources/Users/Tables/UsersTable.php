<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * A1 voir et filtrer, A2 suspendre / rétablir / supprimer, A3 promouvoir.
 *
 * Les actions appellent les méthodes du modèle (User::suspendre()…) : la règle est écrite une fois, testée
 * sans passer par l'écran. Un administrateur n'agit pas sur son propre compte : il ne se suspend pas et ne
 * se supprime pas par erreur (la bibliothèque pourrait se retrouver sans admin).
 */
class UsersTable
{
    public static function configure(Table $table): Table
    {
        $pasMoi = fn (User $record): bool => $record->id !== Auth::id();

        return $table
            ->columns([
                TextColumn::make('pseudo')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('suspendu_le')->label('Suspendu le')->dateTime('d/m/Y H:i')->sortable()->placeholder('—'),
                TextColumn::make('derniere_connexion_le')->label('Dernière connexion')->dateTime('d/m/Y H:i')->sortable()->placeholder('jamais'),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')->options([
                    Role::Membre->value => 'Membre',
                    Role::Admin->value => 'Administrateur',
                ]),
                TernaryFilter::make('suspendu')
                    ->label('Suspendu')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('suspendu_le'),
                        false: fn (Builder $query) => $query->whereNull('suspendu_le'),
                    ),
            ])
            ->recordActions([
                Action::make('suspendre')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Ses sessions et ses jetons sont coupés tout de suite ; ses données restent.')
                    ->visible(fn (User $record): bool => $pasMoi($record) && ! $record->estSuspendu())
                    ->action(fn (User $record) => $record->suspendre()),
                Action::make('retablir')
                    ->label('Rétablir')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->estSuspendu())
                    ->action(fn (User $record) => $record->retablir()),
                Action::make('promouvoir')
                    ->label('Promouvoir admin')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! $record->isAdmin())
                    ->action(fn (User $record) => $record->promouvoir()),
                // Règle 19 : ses données partent avec lui (cascade), ses signalements restent sans auteur.
                DeleteAction::make()
                    ->modalDescription('Marque-pages, collections, annotations, votes, recherches et liens courts sont effacés. Irréversible.')
                    ->visible($pasMoi),
            ]);
    }
}
