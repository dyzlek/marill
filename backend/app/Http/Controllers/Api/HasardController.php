<?php

namespace App\Http\Controllers\Api;

use App\Babel\Hasard;
use App\Babel\Page;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\ActiviteJour;
use Babel\Moteur\Moteur;
use Illuminate\Http\JsonResponse;

class HasardController extends Controller
{
    /**
     * V9 : ouvrir une page au hasard (F7). Renvoie l'adresse tirée et son texte, sous la même forme que
     * GET /api/pages/{adresse} : l'application web affiche la page en un seul appel.
     */
    public function __invoke(Hasard $hasard, Moteur $moteur): JsonResponse
    {
        $adresse = $hasard->auHasard();
        ActiviteJour::compter('pages_lues'); // A11 : une page au hasard est une page lue

        // no-store : chaque appel doit tirer une nouvelle page, ni le navigateur ni un proxy ne la gardent.
        return (new PageResource(new Page($adresse, $moteur->lire($adresse))))
            ->response()
            ->header('Cache-Control', 'no-store, private');
    }
}
