<?php

namespace App\Babel;

/**
 * Résultat d'une recherche locale : le texte réellement cherché (normalisé, V6), le mode et l'adresse trouvée.
 * Pas de position : l'application web la calcule avec localiser (F4) sur la page qu'elle lit.
 *
 * Pas nommé « Recherche » : ce nom revient au futur modèle Eloquent de la table RECHERCHE (historique des membres).
 */
final readonly class ResultatRecherche
{
    public function __construct(
        public string $texte,
        public string $mode,
        public string $adresse,
    ) {}
}
