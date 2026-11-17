<?php

namespace App\Babel;

use Babel\Moteur\Moteur;
use GMP;

/**
 * F7 : auHasard(). Tire une adresse de page valide, chaque page ayant la même chance de sortir.
 * Référence : docs/cahier-des-charges.md, livrable 2, F7.
 *
 * Le piège : il y a 29^3200 pages, un nombre de 15 546 bits. Un entier PHP (64 bits) ne toucherait que
 * les tout premiers hexagones, alors que 79 % des pages sont dans un hexagone au nom de 3 004 chiffres.
 * Donc : on tire un numéro de page entre 0 et 29^3200 − 1 avec GMP, puis on le découpe en adresse.
 */
final class Hasard
{
    public function auHasard(): string
    {
        return $this->adresseDuNumero($this->numeroAuHasard());
    }

    /**
     * Numéro de page uniforme dans [0, N[, N = 29^3200.
     *
     * random_bytes : le générateur cryptographique du système, sans graine à fournir ni à deviner
     * (gmp_random_range, lui, s'appuie sur une graine). On tire juste assez de bits pour écrire N − 1 ;
     * si le nombre obtenu dépasse, on recommence (« tirage avec rejet »). Garder seulement ceux qui
     * tombent dans [0, N[ laisse chacun aussi probable que les autres. Plus d'une chance sur deux de
     * réussir à chaque essai : en moyenne moins de deux essais.
     */
    private function numeroAuHasard(): GMP
    {
        $total = self::nombreDePages();
        $bits = strlen(gmp_strval(gmp_sub($total, 1), 2)); // 15 546
        $octets = max(1, intdiv($bits + 7, 8)); // random_bytes exige au moins 1 octet

        do {
            // On jette les bits de trop au début pour ne garder que $bits bits.
            $numero = gmp_div_q(gmp_import(random_bytes($octets)), gmp_pow(2, $octets * 8 - $bits));
        } while (gmp_cmp($numero, $total) >= 0);

        return $numero;
    }

    /**
     * Découpe un numéro de page en adresse, dans l'ordre de lecture : la page varie le plus vite,
     * puis le volume, l'étagère, le mur et enfin l'hexagone (après la page 410 vient la page 1
     * du volume suivant). Le numéro 0 est 0-w1-s1-v1:1 ; le dernier est la page unique du dernier hexagone.
     */
    public function adresseDuNumero(GMP $numero): string
    {
        [$numero, $page] = gmp_div_qr($numero, Moteur::PAGES);
        [$numero, $volume] = gmp_div_qr($numero, Moteur::VOLUMES);
        [$numero, $etagere] = gmp_div_qr($numero, Moteur::ETAGERES);
        [$hexagone, $mur] = gmp_div_qr($numero, Moteur::MURS);

        return sprintf('%s-w%d-s%d-v%d:%d',
            gmp_strval($hexagone, 36), // base 36 en minuscules, sans zéro devant : la seule écriture valide
            gmp_intval($mur) + 1, gmp_intval($etagere) + 1, gmp_intval($volume) + 1, gmp_intval($page) + 1,
        );
    }

    /** 29^3200, calculé une seule fois par processus. */
    private static function nombreDePages(): GMP
    {
        static $total = null;

        return $total ??= gmp_pow(strlen(Moteur::ALPHABET), Moteur::LONGUEUR_PAGE);
    }
}
