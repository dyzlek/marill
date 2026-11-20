<?php

namespace App\Http\Controllers\Api;

use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnnotationPubliqueResource;
use App\Models\Adresse;
use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * V14 : lire les annotations publiques d'une page de notre bibliothèque. Publique, sans compte.
 */
class AnnotationsDePageController extends Controller
{
    public function __invoke(string $adresse, Validateur $validateur): AnonymousResourceCollection
    {
        // Mêmes contrôles et mêmes codes que GET /api/pages/{adresse}.
        $resultat = $validateur->validerPage($adresse);
        if (! $resultat->valide) {
            throw ErreurApi::depuisValidation($resultat);
        }

        // On cherche l'adresse sans l'enregistrer : lire ne doit rien écrire en base. Une page que
        // personne n'a jamais annotée n'a pas de ligne, et la réponse est une liste vide (200).
        $page = Adresse::query()
            ->where('bibliotheque_id', Bibliotheque::locale()->id)
            ->where('empreinte', Adresse::empreinteDe($adresse))
            ->first();

        // V18 : un membre connecté ne voit plus ce qu'il a signalé. auth('sanctum') et pas le middleware :
        // la route reste ouverte aux visiteurs, mais reconnaît un membre s'il y en a un.
        $lecteur = auth('sanctum')->user();

        $annotations = Annotation::query()
            ->where('adresse_id', $page->id ?? 0)
            ->where('est_publique', true)
            ->whereNull('masque_le') // règle 14 : une annotation masquée n'est plus montrée
            ->nonSignalesPar($lecteur instanceof User ? $lecteur : null)
            ->with('user:id,pseudo') // le pseudo seulement, en une requête pour toute la liste
            ->latest()
            ->latest('id')
            ->paginate(50);

        return AnnotationPubliqueResource::collection($annotations);
    }
}
