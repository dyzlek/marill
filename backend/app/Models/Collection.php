<?php

namespace App\Models;

use App\Models\Concerns\Signalable;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * COLLECTION (M4, M5) : des pages rangées dans l'ordre (règle 8), privée par défaut.
 * Attention au nom : Illuminate\Support\Collection existe aussi ; importer App\Models\Collection.
 */
#[Fillable(['nom', 'description', 'est_publique'])]
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, Signalable; // signalements(), nonSignalesPar(), estSignalePar() : V17, V18

    /** masque_le hors de #[Fillable] : seul un administrateur le pose (règle 14). */
    protected function casts(): array
    {
        return [
            'est_publique' => 'boolean',
            'masque_le' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Les pages dans l'ordre de lecture. Relation « plusieurs à plusieurs » par la table pivot
     * collection_pages ; la position est lue sur $adresse->rangement->position.
     * Ajouter : $collection->pages()->attach($adresse->id, ['position' => 3]).
     *
     * @return BelongsToMany<Adresse, $this, CollectionPage, 'rangement'>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Adresse::class, 'collection_pages')
            ->using(CollectionPage::class)
            ->as('rangement')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /** @return HasMany<LienCourt, $this> */
    public function liensCourts(): HasMany
    {
        return $this->hasMany(LienCourt::class);
    }
}
