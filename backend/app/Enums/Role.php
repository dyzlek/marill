<?php

namespace App\Enums;

/** UTILISATEUR.role. Un seul endroit décide qui est admin : User::isAdmin(). */
enum Role: string
{
    case Membre = 'membre';
    case Admin = 'admin';
}
