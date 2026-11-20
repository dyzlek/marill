<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * A1 · A2 · A3 : les comptes. Une seule page, la liste : l'administrateur ne crée pas de compte et ne
 * modifie ni le pseudo, ni l'email, ni le mot de passe d'un membre (M9 est l'affaire du membre).
 * Il filtre, suspend, rétablit, promeut ou supprime, par des actions sur chaque ligne.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'compte';

    protected static ?string $pluralModelLabel = 'comptes';

    protected static ?string $recordTitleAttribute = 'pseudo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
