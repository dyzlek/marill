<?php

namespace App\Http\Controllers\Api;

use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\GarderMarquePageRequest;
use App\Http\Resources\MarquePageResource;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\MarquePage;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * Marque-pages (M2, M3, M13, règle 7). Réservés aux membres connectés : session du front ou jeton.
 */
#[Middleware('auth:sanctum')]
class MarquePageController extends Controller
{
    /**
     * M3 : mes marque-pages, du plus récent au plus ancien, 50 par page de résultats.
     * with() charge les adresses et leurs bibliothèques en 2 requêtes, quel que soit le nombre de
     * marque-pages (sans lui : une requête par ligne, et preventLazyLoading lèverait une exception).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $marquePages = $this->membre($request)->marquePages()
            ->with('adresse.bibliotheque')
            ->latest()
            ->latest('id') // départage deux marque-pages créés dans la même seconde
            ->paginate(50);

        return MarquePageResource::collection($marquePages);
    }

    /**
     * M2 · M13 : garder une page (d'ici, ou d'une autre bibliothèque), avec un titre facultatif. 201.
     */
    public function store(GarderMarquePageRequest $request, Validateur $validateur): MarquePageResource
    {
        $adresse = $request->string('adresse')->toString();

        // F5 : une page seulement (règle 4 : un marque-page ne vise jamais un livre entier).
        // Même contrôle et mêmes codes que GET /api/pages/{adresse}.
        $resultat = $validateur->validerPage($adresse);
        if (! $resultat->valide) {
            throw ErreurApi::depuisValidation($resultat);
        }

        $bibliotheque = $request->filled('bibliotheque')
            ? Bibliotheque::query()->where('identifiant', $request->string('bibliotheque')->toString())->firstOrFail()
            : Bibliotheque::locale();

        // Règle 2 : l'adresse est enregistrée une fois, puis partagée par tout ce qui la désigne.
        $page = Adresse::enregistrer($bibliotheque, $adresse);

        // Règle 7 : une seule fois la même page par membre. L'index unique de la base tranche (même si deux
        // requêtes arrivent en même temps) ; on traduit son refus en une erreur claire.
        try {
            $marquePage = $this->membre($request)->marquePages()->create([
                'adresse_id' => $page->id,
                'titre' => $request->input('titre'),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new ErreurApi([[
                'code' => 'ERR_ALREADY_BOOKMARKED',
                'champ' => 'adresse',
                'message' => 'Cette page est déjà dans vos marque-pages.',
            ]]);
        }

        return new MarquePageResource($marquePage->load('adresse.bibliotheque'));
    }

    /**
     * M3 : rouvrir un marque-page. Le front lit ensuite le texte par GET /api/pages/{adresse}
     * (ou chez l'autre bibliothèque, M13). 403 si ce n'est pas le sien.
     */
    #[Authorize('view', 'marquePage')]
    public function show(MarquePage $marquePage): MarquePageResource
    {
        return new MarquePageResource($marquePage->load('adresse.bibliotheque'));
    }

    /**
     * M3 : retirer un marque-page. 204. L'adresse reste : d'autres membres ou contenus peuvent la désigner.
     */
    #[Authorize('delete', 'marquePage')]
    public function destroy(MarquePage $marquePage): Response
    {
        $marquePage->delete();

        return response()->noContent();
    }

    /** auth:sanctum garantit un membre connecté : on le dit au typage une fois pour toutes. */
    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
