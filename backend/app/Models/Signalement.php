<?php

namespace App\Models;

use App\Enums\StatutSignalement;
use App\Exceptions\RegleNonRespectee;
use Database\Factories\SignalementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SIGNALEMENT (règles 13 à 15) : vise exactement un contenu publié, par une vraie clé étrangère.
 * Auteur nul = visiteur (ou compte supprimé, règle 19). traite_par_id garde l'administrateur qui a décidé.
 *
 * @property int|null $annotation_id
 * @property int|null $collection_id
 * @property int|null $lien_court_id
 */
#[Fillable(['annotation_id', 'collection_id', 'lien_court_id', 'motif'])]
class Signalement extends Model
{
    /** @use HasFactory<SignalementFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    /** statut, traite_par_id, traite_le hors de #[Fillable] : c'est l'administrateur qui les pose (A4). */
    protected function casts(): array
    {
        return [
            'statut' => StatutSignalement::class,
            'traite_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Signalement $signalement): void {
            $cibles = array_filter([$signalement->annotation_id, $signalement->collection_id, $signalement->lien_court_id],
                fn (?int $id): bool => $id !== null);

            if (count($cibles) !== 1) {
                throw new RegleNonRespectee('Règle 13 : un signalement vise exactement un contenu (annotation, collection ou lien court).');
            }
        });
    }

    /** @return BelongsTo<Annotation, $this> */
    public function annotation(): BelongsTo
    {
        return $this->belongsTo(Annotation::class);
    }

    /** @return BelongsTo<Collection, $this> */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /** @return BelongsTo<LienCourt, $this> */
    public function lienCourt(): BelongsTo
    {
        return $this->belongsTo(LienCourt::class);
    }

    /**
     * La clé ne s'appelle pas comme le modèle (user_id) : on la précise.
     *
     * @return BelongsTo<User, $this>
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    /** @return BelongsTo<User, $this> */
    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par_id');
    }
}
