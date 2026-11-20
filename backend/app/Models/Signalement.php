<?php

namespace App\Models;

use App\Enums\StatutSignalement;
use App\Exceptions\RegleNonRespectee;
use Database\Factories\SignalementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * SIGNALEMENT (règles 13 à 15) : vise exactement un contenu publié, par une vraie clé étrangère.
 * Auteur nul = visiteur (ou compte supprimé, règle 19). traite_par_id garde l'administrateur qui a décidé.
 *
 * @property int|null $annotation_id
 * @property int|null $collection_id
 * @property int|null $lien_court_id
 * @property StatutSignalement $statut
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

    /*
     * A4 : traiter la file. Les trois décisions passent par ici, que ce soit le back-office ou un test :
     * le contenu change (règle 14 : masquer, c'est dater) et le signalement garde qui a décidé, et quand.
     * Une transaction : jamais un contenu masqué avec un signalement resté « en attente ».
     */

    /** Masquer le contenu signalé : il n'est plus montré au public. Signalement « traité ». */
    public function masquerContenu(User $admin): void
    {
        $this->decider($admin, StatutSignalement::Traite, now());
    }

    /** Rétablir le contenu (déjà masqué par une décision précédente) : il redevient visible. « traité ». */
    public function retablirContenu(User $admin): void
    {
        $this->decider($admin, StatutSignalement::Traite, null);
    }

    /** Le signalement n'est pas fondé : le contenu ne bouge pas. « rejeté ». */
    public function rejeter(User $admin): void
    {
        $this->decider($admin, StatutSignalement::Rejete, false);
    }

    /** @param  \DateTimeInterface|null|false  $masqueLe  false : ne pas toucher au contenu */
    private function decider(User $admin, StatutSignalement $statut, \DateTimeInterface|null|false $masqueLe): void
    {
        DB::transaction(function () use ($admin, $statut, $masqueLe): void {
            if ($masqueLe !== false) {
                $contenu = $this->contenu();
                $contenu->forceFill(['masque_le' => $masqueLe])->save(); // masque_le hors #[Fillable]
            }

            $this->traitePar()->associate($admin);
            $this->forceFill(['statut' => $statut, 'traite_le' => now()])->save();
        });
    }

    /** Le contenu visé (règle 13 : exactement un des trois). */
    public function contenu(): Annotation|Collection|LienCourt
    {
        /** @var Annotation|Collection|LienCourt */
        return $this->annotation ?? $this->collection ?? $this->lienCourt;
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
