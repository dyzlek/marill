<?php

namespace App\Models;

use App\Enums\ModeRecherche;
use App\Enums\PorteeRecherche;
use Database\Factories\RechercheFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RECHERCHE (règle 12, M8) : l'historique d'un membre. L'adresse trouvée n'est pas stockée. */
#[Fillable(['texte', 'mode', 'portee'])]
class Recherche extends Model
{
    /** @use HasFactory<RechercheFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'mode' => ModeRecherche::class,
            'portee' => PorteeRecherche::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
