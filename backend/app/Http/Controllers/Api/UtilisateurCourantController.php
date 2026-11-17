<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UtilisateurResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * GET /api/user : qui est connecté ? Le front Angular le demande avant son premier affichage
 * (provideAppInitializer) au lieu de garder quoi que ce soit dans localStorage. 401 si personne.
 */
class UtilisateurCourantController extends Controller
{
    public function __invoke(Request $request): UtilisateurResource
    {
        /** @var User $user auth:sanctum garantit un User ici */
        $user = $request->user();

        return new UtilisateurResource($user);
    }
}
