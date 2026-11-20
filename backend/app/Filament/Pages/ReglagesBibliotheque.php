<?php

namespace App\Filament\Pages;

use App\Models\Bibliotheque;
use App\Models\Reglage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * A8 : configurer la bibliothèque (nom, description, inscriptions ouvertes ou fermées) et A10 : les quotas.
 *
 * Règle 22 : le nom et la description vont sur la ligne locale de BIBLIOTHEQUE (c'est elle que publie
 * GET /api/identite, B6) ; les inscriptions et les quotas sont des réglages clé/valeur.
 * L'identifiant n'est pas modifiable : il est stable sur le réseau (règle 6), la plateforme le remet.
 *
 * @property-read Schema $form
 */
class ReglagesBibliotheque extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Ma bibliothèque';

    protected static ?string $title = 'Configurer ma bibliothèque';

    protected static ?string $slug = 'reglages';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $ici = Bibliotheque::locale();

        $this->form->fill([
            'identifiant' => $ici->identifiant,
            'nom' => $ici->nom,
            'description' => $ici->description,
            'inscriptions_ouvertes' => Reglage::inscriptionsOuvertes(),
            'quota_api' => Reglage::quotaApi(),
            'quota_publications' => Reglage::quotaPublications(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité publiée sur le réseau (B6)')->schema([
                    TextInput::make('identifiant')->disabled()->dehydrated(false)
                        ->helperText('Stable sur le réseau : il ne change pas.'),
                    TextInput::make('nom')->required()->maxLength(120),
                    Textarea::make('description')->maxLength(1000)->rows(3),
                ]),
                Section::make('Comptes')->schema([
                    Toggle::make('inscriptions_ouvertes')->label('Inscriptions ouvertes')
                        ->helperText('Fermées : POST /register répond 403 ERR_REGISTRATION_CLOSED. Les membres existants se connectent toujours.'),
                ]),
                Section::make('Limite de débit de l’API publique (A10)')->schema([
                    TextInput::make('quota_api')->label('Requêtes par minute et par client')
                        ->integer()->required()->minValue(1)->maxValue(10000),
                    TextInput::make('quota_publications')->label('Liens courts et signalements par minute, sans compte')
                        ->integer()->required()->minValue(1)->maxValue(1000),
                ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Enregistrer')->submit('save')]),
                ]),
        ]);
    }

    public function save(): void
    {
        /** @var array{nom: string, description: ?string, inscriptions_ouvertes: bool, quota_api: int|string, quota_publications: int|string} $data */
        $data = $this->form->getState();

        // Une transaction : on n'enregistre pas le nom sans les réglages, ou l'inverse.
        DB::transaction(function () use ($data): void {
            Bibliotheque::locale()->update(['nom' => $data['nom'], 'description' => $data['description'] ?: null]);
            Reglage::ecrire(Reglage::INSCRIPTIONS_OUVERTES, $data['inscriptions_ouvertes'] ? '1' : '0');
            Reglage::ecrire(Reglage::QUOTA_API, (string) (int) $data['quota_api']);
            Reglage::ecrire(Reglage::QUOTA_PUBLICATIONS, (string) (int) $data['quota_publications']);
        });

        Notification::make()->success()->title('Réglages enregistrés')->send();
    }
}
