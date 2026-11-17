<?php

namespace App\Babel;

use Babel\Moteur\Moteur;

/**
 * F5 : valider(adresse). Dit si une adresse est bien écrite et existe, sans jamais la corriger.
 *
 * Forme : <hexagone>-w<mur>-s<étagère>-v<volume>:<page> ; sans « :<page> », c'est un livre.
 * Une seule écriture valide par page (pas de zéro devant, pas de majuscule, pas d'espace) :
 * deux marque-pages sur la même page ont ainsi exactement la même adresse.
 * Référence : docs/cahier-des-charges.md, livrable 2, F5.
 *
 * Pas de moteur ni de clé : c'est du calcul sur la chaîne. Les erreurs ont la même forme que celles
 * du moteur (code, champ, message), au plus une par partie, dans l'ordre hexagone, mur, etagere, volume, page.
 */
final class Validateur
{
    /**
     * Découpage : hexagone, mur et étagère sans tiret ; le volume va jusqu'au « : » ; la page est tout le reste.
     * \A et \z plutôt que ^ et $ : en PHP, $ accepte un saut de ligne final, et « 0-w1-s1-v1:1\n » passerait.
     * s : le point accepte aussi les sauts de ligne (ils seront refusés ensuite, sur le bon champ).
     */
    private const FORME = '/\A([^-]*)-w([^-]*)-s([^-]*)-v([^:]*)(?::(.*))?\z/s';

    private const SYNTAXE = 'ERR_INVALID_ADDRESS_SYNTAX';

    private const CARACTERES = 'ERR_INVALID_HEX_CHARS';

    private const HORS_LIMITES = 'ERR_OUT_OF_BOUNDS';

    /** Accepte une adresse de page ou de livre. */
    public function valider(string $adresse): ResultatValidation
    {
        return $this->analyser($adresse, pageObligatoire: false);
    }

    /** Variante pour lire une page (F1) : une adresse de livre est refusée sur le champ page. */
    public function validerPage(string $adresse): ResultatValidation
    {
        return $this->analyser($adresse, pageObligatoire: true);
    }

    /**
     * Le dernier hexagone, en base 36. Il y a 29^3200 pages en tout, et chaque hexagone en contient
     * 4 × 5 × 32 × 410 = 262 400. Or 29^3200 = k × 262 400 + 1 : le dernier hexagone (numéro k) n'a qu'une page.
     * Le nombre fait 3 004 chiffres en base 36 : bien trop grand pour un entier PHP (64 bits), d'où GMP.
     * Calculé une seule fois par processus (variable statique).
     */
    public static function dernierHexagone(): string
    {
        static $dernier = null;

        return $dernier ??= gmp_strval(gmp_div_q(
            gmp_sub(gmp_pow(strlen(Moteur::ALPHABET), Moteur::LONGUEUR_PAGE), 1),
            Moteur::MURS * Moteur::ETAGERES * Moteur::VOLUMES * Moteur::PAGES,
        ), 36);
    }

    private function analyser(string $adresse, bool $pageObligatoire): ResultatValidation
    {
        if (preg_match(self::FORME, $adresse, $parties, PREG_UNMATCHED_AS_NULL) !== 1) {
            return $this->invalide([$this->erreur(self::SYNTAXE, 'adresse',
                'L\'adresse doit avoir la forme <hexagone>-w<mur>-s<étagère>-v<volume>:<page>.')]);
        }
        [, $hexagone, $mur, $etagere, $volume, $page] = $parties;

        // Toutes les erreurs d'un coup, au plus une par partie.
        $erreurs = array_values(array_filter([
            $this->verifierHexagone($hexagone),
            $this->verifierNombre($mur, 'mur', 'Le mur', Moteur::MURS),
            $this->verifierNombre($etagere, 'etagere', 'L\'étagère', Moteur::ETAGERES),
            $this->verifierNombre($volume, 'volume', 'Le volume', Moteur::VOLUMES),
            $page === null
                ? ($pageObligatoire ? $this->erreur(self::SYNTAXE, 'page', 'Il manque le numéro de page.') : null)
                : $this->verifierNombre($page, 'page', 'La page', Moteur::PAGES),
        ]));
        if ($erreurs !== []) {
            return $this->invalide($erreurs);
        }

        // Bien écrite, chaque partie dans ses limites : reste le dernier hexagone, qui n'a que w1-s1-v1:1.
        if ($hexagone === self::dernierHexagone() && [$mur, $etagere, $volume, $page ?? '1'] !== ['1', '1', '1', '1']) {
            return $this->invalide([$this->erreur(self::HORS_LIMITES, 'adresse',
                'Le dernier hexagone ne contient qu\'une page : w1-s1-v1:1.')]);
        }

        return new ResultatValidation(true, $page === null ? 'livre' : 'page', []);
    }

    private function verifierHexagone(string $hexagone): ?array
    {
        if ($hexagone === '') {
            return $this->erreur(self::SYNTAXE, 'hexagone', 'L\'hexagone est vide.');
        }
        if (preg_match('/\A[0-9a-z]+\z/', $hexagone) !== 1) {
            return $this->erreur(self::CARACTERES, 'hexagone',
                'L\'hexagone s\'écrit en base 36 : chiffres et lettres minuscules seulement.');
        }
        if (strlen($hexagone) > 1 && $hexagone[0] === '0') {
            return $this->erreur(self::SYNTAXE, 'hexagone', 'L\'hexagone ne commence pas par un zéro.');
        }
        if ($this->auDelaDuDernier($hexagone)) {
            return $this->erreur(self::HORS_LIMITES, 'hexagone', 'Cet hexagone est au-delà du dernier de la bibliothèque.');
        }

        return null;
    }

    /**
     * Deux nombres en base 36 écrits sans zéro devant : le plus long est le plus grand ;
     * à longueur égale, l'ordre des caractères (0-9 puis a-z en ASCII) donne l'ordre des nombres.
     * Pas besoin de convertir 3 004 chiffres pour comparer.
     */
    private function auDelaDuDernier(string $hexagone): bool
    {
        $dernier = self::dernierHexagone();
        if (strlen($hexagone) !== strlen($dernier)) {
            return strlen($hexagone) > strlen($dernier); // couvre aussi la limite Moteur::LONGUEUR_MAX_HEXAGONE
        }

        return strcmp($hexagone, $dernier) > 0;
    }

    private function verifierNombre(string $valeur, string $champ, string $nom, int $max): ?array
    {
        // Chiffres ASCII seulement, sans zéro devant ni signe : « 04 », « +4 », « ４ » sont refusés.
        if (preg_match('/\A(0|[1-9][0-9]*)\z/', $valeur) !== 1) {
            return $this->erreur(self::SYNTAXE, $champ, "$nom s'écrit en chiffres, sans zéro devant.");
        }
        // strlen d'abord : un nombre de 20 chiffres déborderait d'un entier PHP.
        if (strlen($valeur) > strlen((string) $max) || (int) $valeur < 1 || (int) $valeur > $max) {
            return $this->erreur(self::HORS_LIMITES, $champ, "$nom va de 1 à $max.");
        }

        return null;
    }

    /** Message en français, qui ne recopie jamais l'entrée (rien à échapper côté affichage). */
    private function erreur(string $code, string $champ, string $message): array
    {
        return ['code' => $code, 'champ' => $champ, 'message' => $message];
    }

    private function invalide(array $erreurs): ResultatValidation
    {
        return new ResultatValidation(false, null, $erreurs);
    }
}
