<?php

namespace App\Http\Controllers\Api;

use App\Babel\Moderation;
use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnnoterRequest;
use App\Http\Requests\ModifierAnnotationRequest;
use App\Http\Resources\AnnotationResource;
use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * Annotations du membre (M6, règle 9) : annoter une page, modifier, supprimer, choisir publique ou
 * privée. Seuls les membres annotent : tout ce contrôleur est derrière auth:sanctum.
 * La lecture publique d'une page (V14) est dans AnnotationsDePageController, sans compte.
 *
 * Pas de vérification automatique du texte : le CDC dit qu'une annotation se signale (V17). La
 * vérification des textes bannis dans la PAGE annotée, à la publication, viendra avec #35.
 */
#[Middleware('auth:sanctum')]
class AnnotationController extends Controller
{
    /** Mes annotations, publiques et privées, la plus récente d'abord, 50 par page de résultats. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $annotations = $this->membre($request)->annotations()
            ->with('adresse.bibliotheque')
            ->latest()
            ->latest('id')
            ->paginate(50);

        return AnnotationResource::collection($annotations);
    }

    /** M6 : annoter une page (d'ici, ou d'ailleurs comme un marque-page, M13). 201. */
    public function store(AnnoterRequest $request, Validateur $validateur, Moderation $moderation): AnnotationResource
    {
        $adresse = $request->string('adresse')->toString();

        // Règle 4 : une annotation vise toujours une page, jamais un livre entier.
        $resultat = $validateur->validerPage($adresse);
        if (! $resultat->valide) {
            throw ErreurApi::depuisValidation($resultat);
        }

        $bibliotheque = $request->filled('bibliotheque')
            ? Bibliotheque::query()->where('identifiant', $request->string('bibliotheque')->toString())->firstOrFail()
            : Bibliotheque::locale();

        // Plusieurs annotations du même membre sur la même page sont permises : le MLD n'a pas d'unicité.
        $annotation = $this->membre($request)->annotations()->create([
            'adresse_id' => Adresse::enregistrer($bibliotheque, $adresse)->id,
            'contenu' => $request->string('contenu')->toString(),
            'est_publique' => $request->boolean('est_publique'),
        ]);

        // Étape 3 de la modération : publier, c'est faire vérifier la page visée contre les textes bannis.
        if ($annotation->est_publique) {
            $moderation->verifierAvantPublication($annotation->adresse);
        }

        return new AnnotationResource($annotation->load('adresse.bibliotheque'));
    }

    #[Authorize('view', 'annotation')]
    public function show(Annotation $annotation): AnnotationResource
    {
        return new AnnotationResource($annotation->load('adresse.bibliotheque'));
    }

    /**
     * M6 : modifier le texte ou la visibilité. Une annotation masquée par l'administrateur le reste
     * (masque_le n'est pas modifiable ici) : la réécrire ne la fait pas réapparaître.
     */
    #[Authorize('update', 'annotation')]
    public function update(ModifierAnnotationRequest $request, Annotation $annotation, Moderation $moderation): AnnotationResource
    {
        $annotation->update($request->validated());

        if ($annotation->est_publique) { // rendue publique ou réécrite en public : on revérifie la page
            $moderation->verifierAvantPublication($annotation->load('adresse')->adresse);
        }

        return new AnnotationResource($annotation->load('adresse.bibliotheque'));
    }

    /** M6 : supprimer. 204. Ses signalements partent avec elle (règle 13, cascade). */
    #[Authorize('delete', 'annotation')]
    public function destroy(Annotation $annotation): Response
    {
        $annotation->delete();

        return response()->noContent();
    }

    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
