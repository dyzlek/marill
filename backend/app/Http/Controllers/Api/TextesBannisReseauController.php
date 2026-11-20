<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatutTexteBanni;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ExigerJetonReseau;
use App\Http\Resources\TexteBanniReseauResource;
use App\Models\Bibliotheque;
use App\Models\TexteBanni;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * A6 · B5 · règle 17 : la liste de NOS textes bannis, pour les autres bibliothèques du réseau.
 *
 * Seulement ceux d'origine locale et appliqués : un texte levé (rejete) n'est plus publié, et on ne
 * republie jamais un texte venu d'ailleurs (chaque bibliothèque ne publie que ses propres décisions).
 */
#[Middleware(ExigerJetonReseau::class)]
class TextesBannisReseauController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        return TexteBanniReseauResource::collection(
            TexteBanni::query()
                ->where('origine_id', Bibliotheque::locale()->id)
                ->where('statut', StatutTexteBanni::Applique)
                ->orderBy('id')
                ->get()
        );
    }
}
