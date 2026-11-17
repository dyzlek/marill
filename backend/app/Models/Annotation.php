<?php

namespace App\Models;

use App\Models\Concerns\Signalable;
use Database\Factories\AnnotationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ANNOTATION (règle 9) : au plus 2 000 caractères, publique ou privée au choix de son auteur. */
#[Fillable(['adresse_id', 'contenu', 'est_publique'])]
class Annotation extends Model
{
    /** @use HasFactory<AnnotationFactory> */
    use HasFactory, Signalable; // signalements(), nonSignalesPar(), estSignalePar() : V17, V18

    /** Règle 9. La FormRequest de l'issue #20 s'appuiera sur cette constante. */
    public const LONGUEUR_MAX = 2000;

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

    /** @return BelongsTo<Adresse, $this> */
    public function adresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class);
    }
}
