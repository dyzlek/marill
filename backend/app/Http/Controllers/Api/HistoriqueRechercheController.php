<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecherchePasseeResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * M8 : revoir mes recherches passées (règle 12). Rien d'autre que le texte normalisé, le mode et la portée :
 * l'adresse n'est pas gardée, le front relance la recherche pour la retrouver.
 */
#[Middleware('auth:sanctum')]
class HistoriqueRechercheController extends Controller
{
    /** La plus récente d'abord, par 50. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return RecherchePasseeResource::collection(
            $this->membre($request)->recherches()->latest()->latest('id')->paginate(50)
        );
    }

    /**
     * Effacer une recherche de son historique. Pas demandé par M8, mais c'est une donnée personnelle :
     * pouvoir la retirer sans supprimer tout le compte. On ne cherche que dans SES recherches :
     * celle d'un autre donne 404, comme si elle n'existait pas (pas besoin de Policy).
     */
    public function destroy(Request $request, int $recherche): Response
    {
        $this->membre($request)->recherches()->findOrFail($recherche)->delete();

        return response()->noContent();
    }

    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
