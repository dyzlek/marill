<?php

namespace App\Babel;

use Babel\Moteur\Moteur;
use Normalizer;

/**
 * F3 : normaliser(texte). Convertit n'importe quelle chaîne en texte écrit avec les 29 symboles du moteur.
 *
 * La règle fait partie du contrat commun : les dix bibliothèques doivent donner le même résultat,
 * sinon une même phrase ne mène pas à la même adresse sur tout le réseau.
 * Référence : docs/cahier-des-charges.md, livrable 2, F3.
 *
 * Pas de dépendance : le conteneur de Laravel sait le fabriquer tout seul (autowiring),
 * il suffit de le demander en paramètre d'un contrôleur ou d'un constructeur.
 */
final class Normaliseur
{
    /** Modes exact et bruit : les cinq étapes. */
    public function normaliser(string $texte): string
    {
        $texte = $this->etapes1a3($texte);

        // 4. Réduire les espaces qui se suivent à une seule.
        $texte = preg_replace('/ {2,}/', ' ', $texte);

        // 5. Retirer les espaces du début et de la fin (après l'étape 3, il ne reste plus d'autre blanc).
        return trim($texte, ' ');
    }

    /**
     * Mode page : seulement les étapes 1 à 3. Elles laissent intacte une page du moteur ;
     * réduire ou retirer des espaces en ferait un autre texte, donc une autre adresse.
     */
    public function normaliserPage(string $texte): string
    {
        return $this->etapes1a3($texte);
    }

    private function etapes1a3(string $texte): string
    {
        // La fonction accepte tout : les octets qui ne forment pas de l'UTF-8 valide sont d'abord
        // remplacés par « ? », qui devient une espace à l'étape 3 (sinon preg_replace renverrait null).
        $texte = mb_scrub($texte, 'UTF-8');

        // 1. Minuscules (version Unicode : « É » devient « é », pas seulement A-Z).
        $texte = mb_strtolower($texte, 'UTF-8');

        // 2. Accents et cédille : la forme NFD sépare la lettre de son signe (« é » = « e » + accent),
        //    puis on retire les signes combinants (\p{Mn}). Les lettres liées sont développées à la main.
        //    Ce qui n'est pas un accent (ß, ø, ł…) n'est pas converti : l'étape 3 en fait une espace.
        $texte = Normalizer::normalize($texte, Normalizer::FORM_D) ?: $texte;
        $texte = preg_replace('/\p{Mn}/u', '', $texte);
        $texte = strtr($texte, ['œ' => 'oe', 'æ' => 'ae']);

        // 3. Tout caractère hors alphabet devient une espace, saut de ligne compris.
        //    L'alphabet vient du moteur : une seule source de vérité, pas de copie à tenir à jour.
        return preg_replace('/[^'.preg_quote(Moteur::ALPHABET, '/').']/u', ' ', $texte);
    }
}
