<?php

namespace App\Models;

use App\Enums\EtatBibliotheque;
use App\Exceptions\RegleNonRespectee;
use Database\Factories\BibliothequeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BIBLIOTHEQUE (règles 5 et 6). Notre bibliothèque est la ligne est_locale : une page d'ici et une page
 * d'ailleurs se manipulent de la même façon (M13).
 */
#[Table(timestamps: false)]
#[Fillable(['identifiant', 'nom', 'description', 'url_actuelle', 'etat', 'echecs_consecutifs', 'dernier_contact_le', 'vue_annuaire_le', 'empreinte_cle', 'cle_changee_le'])]
class Bibliotheque extends Model
{
    /** @use HasFactory<BibliothequeFactory> */
    use HasFactory;

    /**
     * est_locale n'est pas dans #[Fillable] : on ne devient pas « la bibliothèque locale » par un
     * formulaire. Elle s'affecte à la main (seeder, back-office A8).
     */
    protected function casts(): array
    {
        return [
            'est_locale' => 'boolean',
            'etat' => EtatBibliotheque::class,
            'echecs_consecutifs' => 'integer',
            'dernier_contact_le' => 'datetime',
            'vue_annuaire_le' => 'datetime',
            'cle_changee_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Règle 5 : une seule ligne est_locale. La base le garantit (index unique partiel) ; ici, on
        // le dit en clair avant d'écrire.
        static::saving(function (Bibliotheque $bibliotheque): void {
            if ($bibliotheque->est_locale && static::query()
                ->where('est_locale', true)
                ->whereKeyNot($bibliotheque->getKey())
                ->exists()) {
                throw new RegleNonRespectee('Règle 5 : une seule bibliothèque peut être la bibliothèque locale.');
            }
        });
    }

    /** La ligne de notre bibliothèque. */
    public static function locale(): self
    {
        return static::query()->where('est_locale', true)->firstOrFail();
    }

    /** @return HasMany<Adresse, $this> */
    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class);
    }

    /**
     * Textes que cette bibliothèque a bannis en premier (règle 16).
     *
     * @return HasMany<TexteBanni, $this>
     */
    public function textesBannis(): HasMany
    {
        return $this->hasMany(TexteBanni::class, 'origine_id');
    }
}
