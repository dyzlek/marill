<?php

namespace App\Enums;

/** RECHERCHE.portee (règle 12) : recherche locale ou lancée sur le réseau. */
enum PorteeRecherche: string
{
    case Locale = 'locale';
    case Reseau = 'reseau';
}
