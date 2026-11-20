<?php

namespace App\Babel;

use App\Models\Bibliotheque;
use Babel\Moteur\Moteur;

/**
 * Fabrique l'identité de notre bibliothèque (B6). Le seul endroit qui sait d'où elle vient :
 * la ligne locale de la table BIBLIOTHEQUE (règle 22 : nom et description y sont, modifiés par l'admin, A8).
 * config/bibliotheque.php ne sert plus qu'à créer cette ligne la première fois.
 */
final class IdentiteLocale
{
    /** Le moteur est injecté par le conteneur, comme partout (jamais « new Moteur() »). */
    public function __construct(private readonly Moteur $moteur) {}

    public function lire(): Identite
    {
        $ici = Bibliotheque::locale();

        return new Identite(
            identifiant: $ici->identifiant,
            nom: $ici->nom,
            description: $ici->description,
            // F8 : 64 caractères hexadécimaux. Calculée par le moteur à chaque fois, jamais recopiée :
            // c'est la vraie clé en service qui compte, pas ce qu'on aurait noté en base.
            empreinte: $this->moteur->empreinte(),
        );
    }
}
