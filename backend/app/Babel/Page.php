<?php

namespace App\Babel;

/**
 * Une page lue : son adresse et son texte. Rien n'est stocké en base (aucune table ne garde de texte de page) :
 * cet objet n'existe que le temps de la réponse. Il sert de « ressource » à PageResource, à la place d'un modèle.
 */
final readonly class Page
{
    public function __construct(
        public string $adresse,
        public string $texte,
    ) {}
}
