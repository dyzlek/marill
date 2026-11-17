<?php

namespace App\Http\Controllers\Api;

use App\Babel\Page;
use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use Babel\Moteur\Moteur;

class PageController extends Controller
{
    /**
     * V1 · B1 : lire une page par son adresse (F1). Route publique, sans jeton.
     *
     * Le moteur et le validateur sont injectés par le conteneur : on ne fait jamais « new Moteur() »
     * (il a besoin de la clé, que seul le service provider du paquet sait lire dans la configuration).
     */
    public function show(string $adresse, Validateur $validateur, Moteur $moteur): PageResource
    {
        // Notre validateur décide (règle du contrat commun), avant tout appel au moteur.
        // validerPage : une adresse de livre est refusée ici, sur le champ page.
        $resultat = $validateur->validerPage($adresse);
        if (! $resultat->valide) {
            throw ErreurApi::depuisValidation($resultat); // 422 ou 404 au format commun
        }

        return new PageResource(new Page($adresse, $moteur->lire($adresse)));
    }
}
