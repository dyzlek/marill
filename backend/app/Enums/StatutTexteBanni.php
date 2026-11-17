<?php

namespace App\Enums;

/** TEXTE_BANNI.statut (règle 17) : déclaré ici = applique ; relevé ailleurs = propose. */
enum StatutTexteBanni: string
{
    case Propose = 'propose';
    case Applique = 'applique';
    case Rejete = 'rejete';
}
