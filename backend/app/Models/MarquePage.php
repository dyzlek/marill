<?php

namespace App\Models;

use Database\Factories\MarquePageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** MARQUE_PAGE (règle 7) : privé, unique par (membre, page). */
#[Fillable(['adresse_id', 'titre'])]
class MarquePage extends Model
{
    /** @use HasFactory<MarquePageFactory> */
    use HasFactory;

    const UPDATED_AT = null;

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
