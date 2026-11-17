<?php

namespace App\Models;

use App\Exceptions\RegleNonRespectee;
use Carbon\CarbonImmutable;
use Database\Factories\LienCourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LIEN_COURT (règle 11) : exactement une cible (une adresse, page ou livre, ou une collection).
 * Sans auteur (visiteur), il expire obligatoirement. Le passage à surligner ne va qu'avec une adresse.
 *
 * @property int|null $user_id
 * @property int|null $adresse_id
 * @property int|null $collection_id
 * @property string|null $passage
 * @property CarbonImmutable|null $expire_le
 */
#[Table('liens_courts')] // Laravel devinerait « lien_courts »
#[Fillable(['slug', 'adresse_id', 'collection_id', 'passage', 'expire_le'])]
class LienCourt extends Model
{
    /** @use HasFactory<LienCourtFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'visites' => 'integer',
            'expire_le' => 'datetime',
            'masque_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Mêmes règles que les contraintes CHECK de la migration, dites en français avant d'écrire.
        static::saving(function (LienCourt $lien): void {
            if (($lien->adresse_id === null) === ($lien->collection_id === null)) {
                throw new RegleNonRespectee('Règle 11 : un lien court vise exactement une cible, une adresse ou une collection.');
            }
            if ($lien->user_id === null && $lien->expire_le === null) {
                throw new RegleNonRespectee('Règle 11 : un lien court créé sans compte expire obligatoirement.');
            }
            if ($lien->passage !== null && $lien->adresse_id === null) {
                throw new RegleNonRespectee('Règle 11 : un passage à surligner ne va qu\'avec une adresse.');
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Adresse, $this> */
    public function adresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class);
    }

    /** @return BelongsTo<Collection, $this> */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /** @return HasMany<Signalement, $this> */
    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }
}
