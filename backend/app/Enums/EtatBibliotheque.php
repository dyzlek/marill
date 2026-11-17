<?php

namespace App\Enums;

/** BIBLIOTHEQUE.etat (A9) : hors ligne au 3e échec de suite. */
enum EtatBibliotheque: string
{
    case EnLigne = 'en_ligne';
    case HorsLigne = 'hors_ligne';
    case Inconnu = 'inconnu';
}
