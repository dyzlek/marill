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

    /**
     * La base met visites à 0 par défaut, mais un modèle tout juste créé ne relit pas la ligne : sans ceci,
     * la réponse de création dirait « visites: null ».
     */
    protected $attributes = ['visites' => 0];

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

    /**
     * Les symboles d'un slug : minuscules et chiffres, sans ceux qu'on confond à l'oral ou sur un QR code
     * imprimé (l et 1, o et 0). 32 symboles, 8 caractères : 32^8, plus de mille milliards de slugs.
     */
    public const SYMBOLES_SLUG = 'abcdefghijkmnpqrstuvwxyz23456789';

    /**
     * Un slug tiré au hasard. random_int : le générateur cryptographique du système, pour qu'on ne puisse
     * pas deviner les liens des autres en essayant les slugs voisins. L'unicité est vérifiée par la base.
     */
    public static function nouveauSlug(): string
    {
        $slug = '';
        for ($i = 0; $i < 8; $i++) {
            $slug .= self::SYMBOLES_SLUG[random_int(0, strlen(self::SYMBOLES_SLUG) - 1)];
        }

        return $slug;
    }

    public function estExpire(): bool
    {
        return $this->expire_le !== null && $this->expire_le->isPast();
    }

    /**
     * On ne montre plus le lien au public (règles 14 et 18) : il est masqué, ou sa cible l'est. Une page
     * n'est jamais masquée, mais un lien vers une page qui contient un texte banni appliqué ne la montre
     * plus. Les relations adresse et collection doivent être chargées (with / load) : pas de requête cachée.
     */
    public function estMasque(): bool
    {
        return $this->masque_le !== null
            || $this->adresse?->texte_banni_id !== null
            || ($this->collection !== null && ($this->collection->masque_le !== null || ! $this->collection->est_publique));
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
