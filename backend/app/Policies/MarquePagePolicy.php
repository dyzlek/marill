<?php

namespace App\Policies;

use App\Models\MarquePage;
use App\Models\User;

/**
 * Autorisation (règle 7 : un marque-page est privé). auth:sanctum dit QUI envoie la requête (401) ;
 * cette Policy dit s'il a le DROIT (403). Laravel la trouve tout seul : App\Policies\{Modèle}Policy.
 *
 * Pas d'exception pour l'administrateur : il modère ce qui est publié, et un marque-page ne l'est jamais.
 */
class MarquePagePolicy
{
    public function view(User $user, MarquePage $marquePage): bool
    {
        return $marquePage->user_id === $user->id;
    }

    public function delete(User $user, MarquePage $marquePage): bool
    {
        return $marquePage->user_id === $user->id;
    }
}
