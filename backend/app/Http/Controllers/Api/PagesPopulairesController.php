<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PagePopulaireResource;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * V13 · B4 : nos pages les plus populaires, publiques. Règle 10 : calculées à chaque fois à partir des
 * votes et du nombre de marque-pages, jamais stockées. Un total anonyme : on ne dit ni qui a voté, ni qui
 * garde la page.
 *
 * Score = somme des votes (+1 / −1) + nombre de marque-pages (choix du groupe : un marque-page vaut un
 * vote pour). Seulement des pages de chez nous : celles d'une autre bibliothèque sont à elle (V21, #36).
 * Une page marquée d'un texte banni appliqué n'est pas montrée (règle 18).
 */
class PagesPopulairesController extends Controller
{
    public const NOMBRE = 20;

    public function __invoke(): AnonymousResourceCollection
    {
        // Deux sous-requêtes (withSum, withCount) dans UNE requête SQL, quel que soit le nombre de pages.
        $pages = Adresse::query()
            ->where('bibliotheque_id', Bibliotheque::locale()->id)
            ->whereNotNull('page')
            ->whereNull('texte_banni_id')
            ->where(fn (Builder $q) => $q->has('votes')->orHas('marquePages'))
            ->withSum('votes', 'valeur')
            ->withCount('marquePages')
            ->with('bibliotheque')
            ->orderByRaw('coalesce(votes_sum_valeur, 0) + marque_pages_count desc')
            ->orderBy('id')
            ->limit(self::NOMBRE)
            ->get();

        return PagePopulaireResource::collection($pages);
    }
}
