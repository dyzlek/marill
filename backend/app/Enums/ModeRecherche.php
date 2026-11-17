<?php

namespace App\Enums;

/** RECHERCHE.mode : les deux modes qu'un membre lance (le mode page sert à la migration, pas à l'historique). */
enum ModeRecherche: string
{
    case Exact = 'exact';
    case Bruit = 'bruit';
}
