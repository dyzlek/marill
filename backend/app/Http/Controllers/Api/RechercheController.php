<?php

namespace App\Http\Controllers\Api;

use App\Babel\Normaliseur;
use App\Babel\ResultatRecherche;
use App\Enums\PorteeRecherche;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChercherTexteRequest;
use App\Http\Resources\RechercheResource;
use App\Models\ActiviteJour;
use App\Models\User;
use Babel\Moteur\Moteur;

class RechercheController extends Controller
{
    /**
     * V4 · V5 · V6 · B2 : chercher un texte dans cette bibliothèque (F2). Route publique, sans jeton.
     *
     * Contrôleur à une seule action (__invoke) : une recherche n'est pas une ressource qu'on liste ou modifie.
     * La FormRequest a déjà vérifié l'entrée avant d'arriver ici (sinon 422 au format commun).
     */
    public function __invoke(ChercherTexteRequest $request, Normaliseur $normaliseur, Moteur $moteur): RechercheResource
    {
        // Toujours normaliser sur le serveur, même si la demande vient d'une autre bibliothèque :
        // c'est la règle commune qui garantit qu'une phrase mène à la même page partout.
        $texte = $normaliseur->normaliser($request->validated('texte'));
        $mode = $request->validated('mode');

        // Vide ou trop long après conversion : le moteur lève ERR_EMPTY_QUERY ou ERR_TEXT_TOO_LONG,
        // traduits en 422 par bootstrap/app.php.
        $adresse = $moteur->chercher($texte, $mode);

        // Règle 12 : on ne garde que les recherches des membres (M8), le texte normalisé, sans l'adresse
        // (la même recherche la redonne). Un visiteur, ou une autre bibliothèque, ne laisse qu'un compteur.
        // La route est publique : auth('sanctum') regarde s'il y a quand même un membre (session ou jeton).
        /** @var User|null $membre */
        $membre = auth('sanctum')->user();
        $membre?->recherches()->create(['texte' => $texte, 'mode' => $mode, 'portee' => PorteeRecherche::Locale]);
        ActiviteJour::compter('recherches');

        return new RechercheResource(new ResultatRecherche($texte, $mode, $adresse));
    }
}
