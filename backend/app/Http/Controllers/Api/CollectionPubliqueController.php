<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionPubliqueResource;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * V13 · B3 : les collections publiques, lisibles sans compte, par un visiteur comme par une autre
 * bibliothèque (le réseau est ouvert en lecture, sans jeton).
 *
 * Ce qui n'est jamais montré : une collection privée ou masquée (règle 14), une collection que le lecteur
 * a signalée (V18), et dans une collection, une page marquée d'un texte banni appliqué (règle 18 : seule
 * cette page disparaît, le reste de la collection reste visible).
 */
class CollectionPubliqueController extends Controller
{
    /** La plus récemment modifiée d'abord, par 50, avec le pseudo de l'auteur et le nombre de pages. */
    public function index(): AnonymousResourceCollection
    {
        $collections = $this->visibles()
            ->with('user')
            ->withCount(['pages' => fn (Builder $pages) => $pages->whereNull('adresses.texte_banni_id')])
            ->latest('updated_at')
            ->latest('id')
            ->paginate(50);

        return CollectionPubliqueResource::collection($collections);
    }

    /** Une collection publique avec ses pages dans l'ordre. Privée, masquée ou inconnue : 404, sans dire laquelle. */
    public function show(int $collection): CollectionPubliqueResource
    {
        $trouvee = $this->visibles()
            ->with([
                'user',
                'pages' => fn ($pages) => $pages->whereNull('adresses.texte_banni_id'),
                'pages.bibliotheque',
            ])
            ->findOrFail($collection);

        return new CollectionPubliqueResource($trouvee);
    }

    /** @return Builder<Collection> */
    private function visibles(): Builder
    {
        /** @var User|null $lecteur */
        $lecteur = auth('sanctum')->user();

        return Collection::query()
            ->where('est_publique', true)
            ->whereNull('masque_le')
            ->nonSignalesPar($lecteur);
    }
}
