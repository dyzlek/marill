<?php

namespace App\Http\Controllers\Api;

use App\Babel\IdentiteLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\IdentiteResource;

class IdentiteController extends Controller
{
    /** B6 : qui nous sommes. Route publique, sans jeton : la plateforme la lit pour vérifier notre présence. */
    public function __invoke(IdentiteLocale $identite): IdentiteResource
    {
        return new IdentiteResource($identite->lire());
    }
}
