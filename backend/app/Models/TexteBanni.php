<?php

namespace App\Models;

use App\Enums\StatutTexteBanni;
use Database\Factories\TexteBanniFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TEXTE_BANNI (règles 16 à 18) : le texte en clair et normalisé (pour le chercher),
 * son empreinte (pour éviter les doublons), sa bibliothèque d'origine et la décision locale.
 */
#[Table('textes_bannis')] // Laravel devinerait « texte_bannis »
#[Fillable(['texte', 'empreinte', 'motif', 'origine_id'])]
class TexteBanni extends Model
{
    /** @use HasFactory<TexteBanniFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    /** statut et decide_le hors de #[Fillable] : c'est l'administrateur qui décide (A5, A7). */
    protected function casts(): array
    {
        return [
            'statut' => StatutTexteBanni::class,
            'decide_le' => 'datetime',
        ];
    }

    /** SHA-256 du texte normalisé. */
    public static function empreinteDe(string $texte): string
    {
        return hash('sha256', $texte);
    }

    /** @return BelongsTo<Bibliotheque, $this> */
    public function origine(): BelongsTo
    {
        return $this->belongsTo(Bibliotheque::class, 'origine_id');
    }

    /**
     * Les adresses enregistrées dont la page contient ce texte (règle 18).
     *
     * @return HasMany<Adresse, $this>
     */
    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class);
    }
}
