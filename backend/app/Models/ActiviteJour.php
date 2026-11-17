<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * ACTIVITE_JOUR (règle 21, A11) : deux compteurs par jour. Clé primaire : le jour lui-même
 * ('2026-11-17'), une chaîne et non un entier auto-incrémenté ; aucune date de création.
 */
#[Table('activites_jour', key: 'jour', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['jour', 'pages_lues', 'recherches'])]
class ActiviteJour extends Model
{
    protected function casts(): array
    {
        return [
            'pages_lues' => 'integer',
            'recherches' => 'integer',
        ];
    }
}
