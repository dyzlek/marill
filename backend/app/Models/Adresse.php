<?php

namespace App\Models;

use App\Babel\Validateur;
use App\Exceptions\RegleNonRespectee;
use Database\Factories\AdresseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ADRESSE (règles 1 à 4) : une page (ou un livre, page nulle) désignée par quelqu'un, dans une bibliothèque.
 * Jamais le texte : le moteur le recalcule à partir de l'adresse (règle 1).
 *
 * @property int $id
 * @property int $bibliotheque_id
 * @property string $hexagone
 * @property int $mur
 * @property int $etagere
 * @property int $volume
 * @property int|null $page
 * @property string $empreinte
 * @property string $empreinte_livre
 * @property int|null $texte_banni_id
 * @property-read CollectionPage $rangement La ligne de collection_pages, présente seulement quand l'adresse est chargée par Collection::pages().
 */
#[Fillable(['bibliotheque_id', 'hexagone', 'mur', 'etagere', 'volume', 'page', 'empreinte', 'empreinte_livre'])]
class Adresse extends Model
{
    /** @use HasFactory<AdresseFactory> */
    use HasFactory;

    /** Le MLD a cree_le mais pas de date de mise à jour : une adresse ne change pas. */
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'mur' => 'integer',
            'etagere' => 'integer',
            'volume' => 'integer',
            'page' => 'integer',
        ];
    }

    /**
     * Retrouve ou enregistre une adresse (règle 2 : une seule ligne par bibliothèque et par adresse).
     *
     * L'adresse doit être écrite sous sa seule forme valide : sinon la même page aurait deux empreintes.
     * On la passe donc par F5 (Validateur) avant de la découper.
     */
    public static function enregistrer(Bibliotheque $bibliotheque, string $adresse): self
    {
        // F5 d'abord ; une fois l'adresse valide, le motif ne sert plus qu'à la découper
        // (le 5e groupe, :page, est absent pour un livre).
        if (! app(Validateur::class)->valider($adresse)->valide
            || preg_match('/\A([0-9a-z]+)-w(\d)-s(\d)-v(\d+)(?::(\d+))?\z/', $adresse, $m) !== 1) {
            throw new RegleNonRespectee('Règle 2 : seule une adresse valide (F5) peut être enregistrée.');
        }

        return static::query()->firstOrCreate(
            ['bibliotheque_id' => $bibliotheque->id, 'empreinte' => self::empreinteDe($adresse)],
            [
                'hexagone' => $m[1],
                'mur' => (int) $m[2],
                'etagere' => (int) $m[3],
                'volume' => (int) $m[4],
                'page' => isset($m[5]) ? (int) $m[5] : null,
                'empreinte_livre' => self::empreinteDe(self::livreDe($adresse)),
            ],
        );
    }

    /** SHA-256 de l'adresse écrite (règles 2 et 3) : 64 caractères hexadécimaux. */
    public static function empreinteDe(string $adresse): string
    {
        return hash('sha256', $adresse);
    }

    /** L'adresse du livre qui contient cette page : la même, sans « :page ». */
    public static function livreDe(string $adresse): string
    {
        return explode(':', $adresse, 2)[0];
    }

    /** L'adresse écrite, telle que le moteur la lit : hexagone-wM-sE-vV[:page]. */
    public function ecriture(): string
    {
        $livre = sprintf('%s-w%d-s%d-v%d', $this->hexagone, $this->mur, $this->etagere, $this->volume);

        return $this->page === null ? $livre : $livre.':'.$this->page;
    }

    /** Règle 4 : un livre entier est une adresse sans page. */
    public function estUnLivre(): bool
    {
        return $this->page === null;
    }

    /** @return BelongsTo<Bibliotheque, $this> */
    public function bibliotheque(): BelongsTo
    {
        return $this->belongsTo(Bibliotheque::class);
    }

    /**
     * Règle 18 : le texte banni appliqué trouvé dans la page, s'il y en a un.
     *
     * @return BelongsTo<TexteBanni, $this>
     */
    public function texteBanni(): BelongsTo
    {
        return $this->belongsTo(TexteBanni::class);
    }

    /** @return HasMany<MarquePage, $this> */
    public function marquePages(): HasMany
    {
        return $this->hasMany(MarquePage::class);
    }

    /** @return HasMany<Annotation, $this> */
    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class);
    }

    /**
     * Pour compter les votes (pages populaires, règle 10).
     *
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /** @return HasMany<LienCourt, $this> */
    public function liensCourts(): HasMany
    {
        return $this->hasMany(LienCourt::class);
    }

    /** @return BelongsToMany<Collection, $this, CollectionPage, 'rangement'> */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_pages')
            ->using(CollectionPage::class)
            ->as('rangement')
            ->withPivot('position');
    }
}
