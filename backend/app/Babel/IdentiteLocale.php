<?php

namespace App\Babel;

use Babel\Moteur\Moteur;

/**
 * Fabrique l'identité de notre bibliothèque. Le seul endroit qui sait d'où elle vient :
 * aujourd'hui la configuration (config/bibliotheque.php), demain la ligne locale de la table BIBLIOTHEQUE (A8).
 */
final class IdentiteLocale
{
    /** Le moteur est injecté par le conteneur, comme partout (jamais « new Moteur() »). */
    public function __construct(private readonly Moteur $moteur) {}

    public function lire(): Identite
    {
        return new Identite(
            identifiant: config('bibliotheque.identifiant'),
            nom: config('bibliotheque.nom'),
            description: config('bibliotheque.description'),
            // F8 : 64 caractères hexadécimaux. On ne peut pas en déduire la clé, mais elle change avec elle.
            empreinte: $this->moteur->empreinte(),
        );
    }
}
