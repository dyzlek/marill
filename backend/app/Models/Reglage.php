<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * REGLAGE (règle 22) : une paire clé/valeur. Clé primaire : la clé elle-même (chaîne).
 * Jamais la clé du moteur ni le jeton réseau ici : ils restent dans la configuration du serveur.
 *
 * @property string $cle
 * @property string $valeur
 */
#[Table(key: 'cle', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['cle', 'valeur'])]
class Reglage extends Model
{
    /** A8 : « 1 » ouvertes, « 0 » fermées. Le back-office (issue #25) écrira ce réglage. */
    public const INSCRIPTIONS_OUVERTES = 'inscriptions_ouvertes';

    /** La valeur d'un réglage, ou $parDefaut s'il n'a jamais été enregistré. */
    public static function valeur(string $cle, string $parDefaut): string
    {
        return static::query()->find($cle)->valeur ?? $parDefaut;
    }

    /** Sans réglage enregistré, les inscriptions sont ouvertes : une bibliothèque neuve accueille des membres. */
    public static function inscriptionsOuvertes(): bool
    {
        return static::valeur(self::INSCRIPTIONS_OUVERTES, '1') === '1';
    }
}
