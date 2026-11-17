<?php

namespace App\Enums;

/** SIGNALEMENT.statut (A4). */
enum StatutSignalement: string
{
    case EnAttente = 'en_attente';
    case Traite = 'traite';
    case Rejete = 'rejete';
}
