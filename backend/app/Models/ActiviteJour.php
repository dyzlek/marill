<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * ACTIVITE_JOUR (règle 21, A11) : deux compteurs par jour. Clé primaire : le jour lui-même
 * ('2026-11-17'), une chaîne et non un entier auto-incrémenté ; aucune date de création.
 */
#[Table('activites_jour', key: 'jour', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['jour', 'pages_lues', 'recherches'])]
class ActiviteJour extends Model
{
    /**
     * Ajoute 1 au compteur du jour (pages_lues ou recherches). Une seule requête, sans lire avant
     * d'écrire : upsert crée la ligne du jour si elle manque, sinon incrémente. Deux requêtes en même
     * temps ne perdent donc pas de visite (pas de « lire 41, écrire 42 » à deux).
     *
     * @param  'pages_lues'|'recherches'  $compteur
     */
    public static function compter(string $compteur): void
    {
        static::query()->upsert(
            [['jour' => now()->toDateString(), 'pages_lues' => $compteur === 'pages_lues' ? 1 : 0, 'recherches' => $compteur === 'recherches' ? 1 : 0]],
            ['jour'],
            [$compteur => DB::raw($compteur.' + 1')],
        );
    }

    protected function casts(): array
    {
        return [
            'pages_lues' => 'integer',
            'recherches' => 'integer',
        ];
    }
}
