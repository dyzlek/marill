<?php

namespace App\Http\Controllers\Api;

use App\Babel\Moderation;
use App\Babel\Validateur;
use App\Exceptions\ErreurApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreerLienCourtRequest;
use App\Http\Resources\LienCourtResource;
use App\Models\Adresse;
use App\Models\Bibliotheque;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Liens courts (V11, V12, règle 11). Routes publiques : un visiteur crée et ouvre des liens.
 */
class LienCourtController extends Controller
{
    /**
     * V11 : créer un lien court vers une page, un livre entier ou une collection publique. 201.
     *
     * Connecté (session du front ou jeton) : le lien a un auteur et n'expire pas. Sinon : pas d'auteur,
     * et il expire au bout de 30 jours (config bibliotheque.jours_avant_expiration_lien_visiteur).
     * auth('sanctum') et pas le middleware auth:sanctum : la route accepte aussi les visiteurs.
     */
    public function store(CreerLienCourtRequest $request, Validateur $validateur, Moderation $moderation): LienCourtResource
    {
        /** @var User|null $auteur */
        $auteur = auth('sanctum')->user();

        $lien = new LienCourt(['passage' => $request->input('passage')]);

        if ($request->filled('adresse')) {
            $lien->adresse()->associate($this->adresseVisee($request, $validateur));
        } else {
            $lien->collection()->associate($this->collectionVisee($request->integer('collection')));
        }

        if ($auteur !== null) {
            $lien->user()->associate($auteur);
        } else {
            $lien->expire_le = now()->addDays(config()->integer('bibliotheque.jours_avant_expiration_lien_visiteur'));
        }

        $this->enregistrerAvecUnSlugLibre($lien);

        // Étapes 3 et 4 de la modération : la page (ou les 410 pages du livre) est lue et comparée aux textes
        // bannis appliqués. Le passage figure dans la page : la vérification de la page le couvre.
        if ($lien->adresse !== null) {
            $moderation->verifierAvantPublication($lien->adresse);
        }

        return new LienCourtResource($lien->load('adresse.bibliotheque', 'collection'));
    }

    /**
     * V12 : ouvrir un lien court. Chaque ouverture compte une visite (A11 : pages les plus partagées).
     * Inconnu : 404. Expiré ou masqué : 410, chacun avec son code, pour un message clair dans le front.
     */
    public function show(string $slug): LienCourtResource
    {
        $lien = LienCourt::query()->with('adresse.bibliotheque', 'collection')->where('slug', $slug)->first();

        if ($lien === null) {
            throw new ErreurApi([['code' => 'ERR_LINK_NOT_FOUND', 'champ' => 'slug', 'message' => 'Ce lien court n\'existe pas.']]);
        }
        if ($lien->estExpire()) {
            throw new ErreurApi([['code' => 'ERR_LINK_EXPIRED', 'champ' => 'slug', 'message' => 'Ce lien court a expiré.']]);
        }
        if ($lien->estMasque()) {
            throw new ErreurApi([['code' => 'ERR_LINK_HIDDEN', 'champ' => 'slug', 'message' => 'Ce lien court a été retiré par la modération.']]);
        }
        // V18 : un membre ne revoit pas un lien qu'il a signalé, quelle que soit la décision de l'administrateur.
        // Pour lui seul : les autres l'ouvrent toujours. Même code (le lien lui est caché), autre message.
        /** @var User|null $membre */
        $membre = auth('sanctum')->user();
        if ($lien->estSignalePar($membre)) {
            throw new ErreurApi([['code' => 'ERR_LINK_HIDDEN', 'champ' => 'slug', 'message' => 'Vous avez signalé ce lien court.']]);
        }

        // increment() : un seul UPDATE … SET visites = visites + 1 en SQL. Deux visites simultanées comptent
        // bien pour deux, ce qui ne serait pas le cas avec « lire la valeur, ajouter 1 en PHP, enregistrer ».
        $lien->increment('visites');

        return new LienCourtResource($lien);
    }

    /**
     * Une page ou un livre (règle 4 : seul un lien court peut viser un livre), d'ici ou d'une autre
     * bibliothèque connue (M13). F5 décide, avec les codes ERR_… du cahier des charges.
     */
    private function adresseVisee(CreerLienCourtRequest $request, Validateur $validateur): Adresse
    {
        $ecriture = $request->string('adresse')->toString();

        $resultat = $validateur->valider($ecriture);
        if (! $resultat->valide) {
            throw ErreurApi::depuisValidation($resultat);
        }
        if ($resultat->niveau === 'livre' && $request->filled('passage')) {
            throw ValidationException::withMessages(['passage' => 'Un passage ne va qu\'avec une page, pas avec un livre entier.']);
        }

        $bibliotheque = $request->filled('bibliotheque')
            ? Bibliotheque::query()->where('identifiant', $request->string('bibliotheque')->toString())->firstOrFail()
            : Bibliotheque::locale();

        return Adresse::enregistrer($bibliotheque, $ecriture);
    }

    /**
     * Seule une collection publique et visible se partage : un lien est public, il ne doit pas ouvrir une
     * collection privée. Même message si elle n'existe pas : on ne révèle pas qu'une collection privée existe.
     */
    private function collectionVisee(int $id): Collection
    {
        $collection = Collection::query()->whereKey($id)->where('est_publique', true)->whereNull('masque_le')->first();

        if ($collection === null) {
            throw ValidationException::withMessages(['collection' => 'Aucune collection publique ne porte ce numéro.']);
        }

        return $collection;
    }

    /**
     * Le slug est tiré au hasard ; deux tirages identiques sont très improbables, mais c'est l'index unique
     * de la base qui le garantit. En cas de collision, on retire un slug (quelques essais suffisent).
     */
    private function enregistrerAvecUnSlugLibre(LienCourt $lien): void
    {
        for ($essai = 1; ; $essai++) {
            $lien->slug = LienCourt::nouveauSlug();
            try {
                $lien->save();

                return;
            } catch (UniqueConstraintViolationException $e) {
                if ($essai === 5) {
                    throw $e;
                }
            }
        }
    }
}
