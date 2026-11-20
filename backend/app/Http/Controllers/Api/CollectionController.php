<?php

namespace App\Http\Controllers\Api;

use App\Babel\Moderation;
use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\AjouterPageRequest;
use App\Http\Requests\CreerCollectionRequest;
use App\Http\Requests\ModifierCollectionRequest;
use App\Http\Requests\RangerPagesRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

/**
 * Collections du membre (M4, M5, M13, règle 8) : créer, renommer, rendre publique, supprimer,
 * ranger des pages dans l'ordre voulu. Réservées aux membres connectés (session du front ou jeton).
 *
 * Textes bannis (#35) : une page qui arrive dans une collection publique, ou une collection qui devient
 * publique, est vérifiée (App\Babel\Moderation).
 */
#[Middleware('auth:sanctum')]
class CollectionController extends Controller
{
    /**
     * Mes collections, la plus récemment modifiée d'abord, avec leur nombre de pages (withCount :
     * une seule requête en plus, quel que soit le nombre de collections).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $collections = $this->membre($request)->collections()
            ->withCount('pages')
            ->latest('updated_at')
            ->latest('id')
            ->paginate(50);

        return CollectionResource::collection($collections);
    }

    /** M4 : créer une collection, vide et privée par défaut (M5). 201. */
    public function store(CreerCollectionRequest $request): CollectionResource
    {
        $collection = $this->membre($request)->collections()->create($request->validated());

        return new CollectionResource($collection->load('pages.bibliotheque'));
    }

    /** Une de mes collections avec ses pages dans l'ordre. 403 si ce n'est pas la mienne. */
    #[Authorize('view', 'collection')]
    public function show(Collection $collection): CollectionResource
    {
        return new CollectionResource($collection->load('pages.bibliotheque'));
    }

    /** M4 renommer, M5 rendre publique ou privée. */
    #[Authorize('update', 'collection')]
    public function update(ModifierCollectionRequest $request, Collection $collection, Moderation $moderation): CollectionResource
    {
        $collection->update($request->validated());
        $this->verifierSiPublique($collection, $moderation);

        return new CollectionResource($collection->load('pages.bibliotheque'));
    }

    /**
     * M4 supprimer. 204. Les lignes de collection_pages partent avec elle (cascade) ; les adresses restent,
     * d'autres contenus peuvent les désigner. Ses liens courts et signalements aussi (règle 13, cascade).
     */
    #[Authorize('delete', 'collection')]
    public function destroy(Collection $collection): Response
    {
        $collection->delete();

        return response()->noContent();
    }

    /** M4 · M13 : ajouter une page (d'ici ou d'ailleurs) à la fin. 200 avec la collection : on la modifie, on ne crée pas de ressource adressable. */
    #[Authorize('update', 'collection')]
    public function ajouterPage(AjouterPageRequest $request, Collection $collection, Validateur $validateur, Moderation $moderation): CollectionResource
    {
        $page = $this->enregistrerPage(
            $validateur,
            $request->string('adresse')->toString(),
            $request->input('bibliotheque'),
        );

        // La position suivante se calcule et s'écrit dans une transaction : deux ajouts en même temps
        // ne prennent pas la même position (la clé (collection_id, position) le refuserait de toute façon).
        DB::transaction(function () use ($collection, $page) {
            $derniere = (int) DB::table('collection_pages')->where('collection_id', $collection->id)->max('position');
            $collection->pages()->attach($page->id, ['position' => $derniere + 1]);
            $collection->touch(); // la collection est modifiée : updated_at avance
        });

        if ($collection->est_publique) {
            $moderation->verifierAvantPublication($page);
        }

        return new CollectionResource($collection->load('pages.bibliotheque'));
    }

    /**
     * M4 : ranger les pages. Le front renvoie la liste complète dans l'ordre voulu ; elle remplace
     * l'ancienne. Positions 1, 2, 3… sans trou. Une même page peut revenir deux fois (règle 8).
     */
    #[Authorize('update', 'collection')]
    public function rangerPages(RangerPagesRequest $request, Collection $collection, Validateur $validateur, Moderation $moderation): CollectionResource
    {
        /** @var list<array{adresse: string, bibliotheque?: string|null}> $demandees */
        $demandees = $request->validated('pages');

        // Toutes les adresses sont vérifiées AVANT de toucher à la collection : une seule fausse et rien ne change.
        $ids = array_map(
            fn (array $p) => $this->enregistrerPage($validateur, $p['adresse'], $p['bibliotheque'] ?? null)->id,
            $demandees,
        );

        DB::transaction(function () use ($collection, $ids) {
            $collection->pages()->detach();
            foreach ($ids as $i => $id) {
                $collection->pages()->attach($id, ['position' => $i + 1]);
            }
            $collection->touch();
        });
        $this->verifierSiPublique($collection, $moderation);

        return new CollectionResource($collection->load('pages.bibliotheque'));
    }

    /**
     * Étape 3 de la modération : une collection publique est publiée, chacune de ses pages est vérifiée
     * contre les textes bannis. Une page marquée disparaît de la lecture publique, le reste reste visible.
     */
    private function verifierSiPublique(Collection $collection, Moderation $moderation): void
    {
        if (! $collection->est_publique) {
            return;
        }
        foreach ($collection->pages()->with('bibliotheque')->get()->unique('id') as $page) {
            $moderation->verifier($page);
        }
    }

    /**
     * F5 en mode page (règle 4 : une collection ne range jamais un livre entier), puis l'adresse
     * enregistrée une fois (règle 2), dans sa bibliothèque (la nôtre si absente, M13 sinon).
     */
    private function enregistrerPage(Validateur $validateur, string $adresse, mixed $bibliotheque): Adresse
    {
        $resultat = $validateur->validerPage($adresse);
        if (! $resultat->valide) {
            throw ErreurApi::depuisValidation($resultat);
        }

        $bibliotheque = is_string($bibliotheque) && $bibliotheque !== ''
            ? Bibliotheque::query()->where('identifiant', $bibliotheque)->firstOrFail()
            : Bibliotheque::locale();

        return Adresse::enregistrer($bibliotheque, $adresse);
    }

    /** auth:sanctum garantit un membre connecté. */
    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
