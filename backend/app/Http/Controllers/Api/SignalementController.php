<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignalerRequest;
use App\Http\Resources\SignalementResource;
use App\Models\Annotation;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Signalements (V17, V18, règles 13 à 15). Le traitement par l'administrateur est la file A4 (issue #24).
 */
class SignalementController extends Controller
{
    /**
     * V17 : signaler un contenu publié. 201. Auteur nul pour un visiteur (règle 13).
     * Un membre ne signale qu'une fois le même contenu : 409 ERR_ALREADY_REPORTED (choix du groupe).
     */
    public function store(SignalerRequest $request): SignalementResource
    {
        /** @var User|null $auteur */
        $auteur = auth('sanctum')->user();

        $cible = $this->contenuPublie($request);

        if ($cible->estSignalePar($auteur)) {
            throw new ErreurApi([[
                'code' => 'ERR_ALREADY_REPORTED',
                'champ' => null,
                'message' => 'Vous avez déjà signalé ce contenu.',
            ]]);
        }

        $signalement = new Signalement(['motif' => $request->string('motif')->toString()]);
        // La clé étrangère de la cible : annotation_id, collection_id ou lien_court_id (règle 13).
        $signalement->setAttribute($cible->getForeignKey(), $cible->getKey());
        $signalement->auteur()->associate($auteur);
        $signalement->save();

        return new SignalementResource($signalement->load('lienCourt'));
    }

    /**
     * V18 · règle 15 : mes signalements, pour que le front cache ces contenus. Réservé au membre connecté ;
     * un visiteur garde sa liste dans son navigateur.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $membre */
        $membre = $request->user();

        return SignalementResource::collection(
            $membre->signalements()->with('lienCourt')->latest()->latest('id')->paginate(100)
        );
    }

    /**
     * Le contenu visé, s'il est publié. Une annotation ou une collection privée n'est pas publiée : même
     * réponse que si elle n'existait pas, pour ne pas révéler son existence.
     *
     * @return Annotation|Collection|LienCourt
     */
    private function contenuPublie(SignalerRequest $request): Model
    {
        [$champ, $contenu] = match (true) {
            $request->filled('annotation') => ['annotation', Annotation::query()
                ->whereKey($request->integer('annotation'))->where('est_publique', true)->first()],
            $request->filled('collection') => ['collection', Collection::query()
                ->whereKey($request->integer('collection'))->where('est_publique', true)->first()],
            default => ['lien_court', LienCourt::query()
                ->where('slug', $request->string('lien_court')->toString())->first()],
        };

        if ($contenu === null) {
            throw ValidationException::withMessages([$champ => 'Aucun contenu publié ne correspond.']);
        }

        return $contenu;
    }
}
