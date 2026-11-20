<?php

namespace App\Filament\Resources\TextesBannis;

use App\Babel\Moderation;
use App\Enums\StatutTexteBanni;
use App\Exceptions\ErreurApi;
use App\Filament\Resources\TextesBannis\Pages\ListTextesBannis;
use App\Models\TexteBanni;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * A5 déclarer un texte banni ou lever un bannissement ; A7 examiner les textes proposés par les autres
 * bibliothèques et décider. Le travail est dans App\Babel\Moderation ; l'écran ne fait que l'appeler.
 */
class TexteBanniResource extends Resource
{
    protected static ?string $model = TexteBanni::class;

    protected static ?string $slug = 'textes-bannis';

    protected static ?string $modelLabel = 'texte banni';

    protected static ?string $pluralModelLabel = 'textes bannis';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    /** Les propositions venues du réseau qui attendent une décision (A7). */
    public static function getNavigationBadge(): ?string
    {
        $proposes = TexteBanni::query()->where('statut', StatutTexteBanni::Propose)->count();

        return $proposes > 0 ? (string) $proposes : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('origine')->withCount('adresses'))
            ->columns([
                TextColumn::make('texte')->limit(80)->wrap()->searchable(),
                TextColumn::make('origine.identifiant')->label('Origine')
                    ->formatStateUsing(fn (string $state, TexteBanni $record): string => $record->origine->est_locale ? 'ici' : $state),
                TextColumn::make('statut')->badge(),
                TextColumn::make('adresses_count')->label('Pages marquées'),
                TextColumn::make('motif')->limit(60)->placeholder('—'),
                TextColumn::make('decide_le')->label('Décidé le')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('statut')->options([
                    StatutTexteBanni::Propose->value => 'Proposé (réseau)',
                    StatutTexteBanni::Applique->value => 'Appliqué',
                    StatutTexteBanni::Rejete->value => 'Rejeté ou levé',
                ]),
            ])
            ->headerActions([
                Action::make('declarer')
                    ->label('Déclarer un texte banni')
                    ->schema([
                        Textarea::make('texte')->required()->maxLength(3200)
                            ->helperText('Il sera normalisé (minuscules, sans accents…) comme une recherche.'),
                        Textarea::make('motif')->maxLength(1000),
                    ])
                    ->action(function (array $data, Moderation $moderation): void {
                        try {
                            $moderation->declarer($data['texte'], $data['motif'] ?: null);
                            Notification::make()->success()->title('Texte banni appliqué')->send();
                        } catch (ErreurApi $e) {
                            Notification::make()->danger()->title($e->erreurs[0]['message'])->send();
                        }
                    }),
            ])
            ->recordActions([
                // A7 : avant d'appliquer, le serveur montre ce qui serait masqué (pages désignées qui le contiennent).
                Action::make('appliquer')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(fn (TexteBanni $record, Moderation $moderation): string => 'Pages publiées chez nous qui le contiennent : '
                        .$moderation->apercu($record).'. Leur contenu publié ne sera plus montré ; les pages restent lisibles.')
                    ->visible(fn (TexteBanni $record): bool => $record->statut !== StatutTexteBanni::Applique)
                    ->action(fn (TexteBanni $record, Moderation $moderation) => $moderation->appliquer($record)),
                Action::make('rejeter')
                    ->label(fn (TexteBanni $record): string => $record->statut === StatutTexteBanni::Applique ? 'Lever' : 'Rejeter')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (TexteBanni $record): bool => $record->statut !== StatutTexteBanni::Rejete)
                    ->action(fn (TexteBanni $record, Moderation $moderation) => $moderation->rejeter($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTextesBannis::route('/'),
        ];
    }
}
