<?php

namespace App\Models\Concerns;

use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un contenu publié qu'on peut signaler (V17) : annotation, collection, lien court. Jamais une page.
 *
 * V18 et règle 15 : un membre ne voit plus ce qu'il a signalé, quelle que soit la décision de
 * l'administrateur, sans table de plus : la liste se déduit de SIGNALEMENT (auteur_id).
 * Pour un visiteur, le serveur ne sait pas qui il est : c'est son navigateur qui retient ce qu'il a signalé.
 *
 * @phpstan-require-extends Model
 */
trait Signalable
{
    /** @return HasMany<Signalement, $this> */
    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    /**
     * Pour les listes à venir (#19, #20) : Annotation::query()->nonSignalesPar($membre)->…
     * Sans membre (visiteur), le filtre ne retire rien.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function nonSignalesPar(Builder $query, ?User $membre): void
    {
        if ($membre !== null) {
            $query->whereDoesntHave('signalements', fn (Builder $signalements) => $signalements->where('auteur_id', $membre->id));
        }
    }

    /** Pour un contenu seul, déjà chargé (ouvrir un lien court). */
    public function estSignalePar(?User $membre): bool
    {
        return $membre !== null && $this->signalements()->where('auteur_id', $membre->id)->exists();
    }
}
