<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * REGLAGE (règle 22) : une paire clé/valeur. Clé primaire : la clé elle-même (chaîne).
 * Jamais la clé du moteur ni le jeton réseau ici : ils restent dans la configuration du serveur.
 *
 * Les réglages sont lus à chaque requête de l'API publique (le quota, A10) : on les garde en cache,
 * et le cache est vidé dès qu'un réglage est enregistré ou supprimé (événements du modèle).
 *
 * @property string $cle
 * @property string $valeur
 */
#[Table(key: 'cle', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['cle', 'valeur'])]
class Reglage extends Model
{
    /** A8 : « 1 » ouvertes, « 0 » fermées. */
    public const INSCRIPTIONS_OUVERTES = 'inscriptions_ouvertes';

    /** A10 : requêtes par minute et par client sur l'API publique (lecture, recherche, identité…). */
    public const QUOTA_API = 'quota_api_par_minute';

    /** A10 : créations par minute et par client pour les actions sans compte (liens courts, signalements). */
    public const QUOTA_PUBLICATIONS = 'quota_publications_par_minute';

    /**
     * Valeurs d'une bibliothèque neuve. Le CDC ne fixe aucun chiffre : choix du groupe (issue #26).
     * 60 lectures par minute laissent un humain lire et une bibliothèque voisine relayer une recherche ;
     * 10 créations par minute suffisent à un visiteur honnête et freinent un robot.
     */
    public const PAR_DEFAUT = [
        self::INSCRIPTIONS_OUVERTES => '1',
        self::QUOTA_API => '60',
        self::QUOTA_PUBLICATIONS => '10',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Reglage $reglage) => Cache::forget('reglage.'.$reglage->cle));
        static::deleted(fn (Reglage $reglage) => Cache::forget('reglage.'.$reglage->cle));
    }

    /** La valeur d'un réglage, ou $parDefaut s'il n'a jamais été enregistré. */
    public static function valeur(string $cle, ?string $parDefaut = null): string
    {
        // Rangé dans un tableau : le cache ne garde pas null, et un réglage jamais enregistré serait relu
        // en base à chaque requête. ['v' => null] se garde, lui.
        $enregistree = Cache::rememberForever('reglage.'.$cle, fn () => ['v' => static::query()->find($cle)?->valeur])['v'];

        return $enregistree ?? $parDefaut ?? self::PAR_DEFAUT[$cle] ?? '';
    }

    /** Écrit (ou crée) un réglage. Le cache est vidé par l'événement saved. */
    public static function ecrire(string $cle, string $valeur): void
    {
        static::query()->updateOrCreate(['cle' => $cle], ['valeur' => $valeur]);
    }

    /** Sans réglage enregistré, les inscriptions sont ouvertes : une bibliothèque neuve accueille des membres. */
    public static function inscriptionsOuvertes(): bool
    {
        return static::valeur(self::INSCRIPTIONS_OUVERTES) === '1';
    }

    public static function quotaApi(): int
    {
        return max(1, (int) static::valeur(self::QUOTA_API));
    }

    public static function quotaPublications(): int
    {
        return max(1, (int) static::valeur(self::QUOTA_PUBLICATIONS));
    }
}
