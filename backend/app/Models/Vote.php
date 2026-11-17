<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * VOTE (règle 10) : une ligne de la table pivot entre users et adresses, avec sa valeur (+1 ou −1).
 *
 * Clé primaire composée (user_id, adresse_id), comme au MLD : c'est elle qui empêche un second vote.
 * On écrit par la relation User::pagesVotees() (attach, updateExistingPivot, detach) ; on lit par
 * Adresse::votes() pour additionner les valeurs (pages populaires, calculées, jamais stockées).
 * La base date le vote (created_at par défaut CURRENT_TIMESTAMP) : timestamps désactivés ici.
 */
#[Table('votes', timestamps: false)]
class Vote extends Pivot
{
    protected function casts(): array
    {
        return [
            'valeur' => 'integer',
            'created_at' => 'datetime',
        ];
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
}
