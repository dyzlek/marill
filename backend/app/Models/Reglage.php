<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * REGLAGE (règle 22) : une paire clé/valeur. Clé primaire : la clé elle-même (chaîne).
 * Jamais la clé du moteur ni le jeton réseau ici : ils restent dans la configuration du serveur.
 */
#[Table(key: 'cle', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['cle', 'valeur'])]
class Reglage extends Model {}
