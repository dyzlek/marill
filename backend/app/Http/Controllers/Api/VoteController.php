<?php

namespace App\Http\Controllers\Api;

use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoterRequest;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

/**
 * M7 : voter pour (+1) ou contre (−1) une page. Règle 10 : un vote par membre et par page, la clé
 * composée de la table votes l'impose. Revoter change la valeur, on ne cumule pas.
 *
 * PUT plutôt que POST : on pose « mon vote sur cette page », et le même PUT envoyé deux fois donne le même
 * état (idempotent). DELETE retire mon vote.
 */
#[Middleware('auth:sanctum')]
class VoteController extends Controller
{
    public function update(VoterRequest $request, string $adresse, Validateur $validateur): JsonResponse
    {
        $page = $this->page($validateur, $adresse, $request->input('bibliotheque'));
        $valeur = $request->integer('valeur');

        // upsert : UNE requête qui crée la ligne si elle manque, sinon change la valeur (clé composée
        // user_id + adresse_id, règle 10). created_at garde la date du premier vote. On passe par la table
        // et non par la relation : syncWithoutDetaching voudrait dater une colonne updated_at que votes n'a pas.
        DB::table('votes')->upsert(
            [['user_id' => $this->membre($request)->id, 'adresse_id' => $page->id, 'valeur' => $valeur]],
            ['user_id', 'adresse_id'],
            ['valeur'],
        );

        return response()->json(['data' => ['adresse' => $adresse, 'valeur' => $valeur]]);
    }

    public function destroy(Request $request, string $adresse, Validateur $validateur): Response
    {
        $page = $this->page($validateur, $adresse, $request->input('bibliotheque'));
        $this->membre($request)->pagesVotees()->detach($page->id);

        return response()->noContent();
    }

    /** F5 en mode page (règle 4 : on ne vote pas pour un livre), puis l'adresse enregistrée une fois (règle 2). */
    private function page(Validateur $validateur, string $adresse, mixed $bibliotheque): Adresse
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

    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
