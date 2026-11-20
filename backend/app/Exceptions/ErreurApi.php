<?php

namespace App\Exceptions;

use App\Babel\ResultatValidation;
use Babel\Moteur\Erreur\ErreurMoteur;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Une réponse d'erreur au format commun : { "erreurs": [ { "code", "champ", "message" }, … ] }.
 *
 * Toutes les bibliothèques répondent une erreur sous la même forme ; le nom de la clé « erreurs »
 * reste à confirmer par le contrat d'API commun : c'est le seul endroit à changer.
 * Référence : docs/cahier-des-charges.md, livrable 2, « Les codes d'erreur ».
 *
 * Exception « rendable » : Laravel appelle render() tout seul. Un contrôleur n'a qu'à la lancer.
 */
final class ErreurApi extends Exception
{
    /**
     * Statut HTTP de chaque code. Un code absent (ERR_INVALID_ALPHABET, ERR_LIBRARY_LIMIT…)
     * n'est pas une erreur du client : il ne doit jamais arriver jusqu'à une réponse.
     */
    private const STATUTS = [
        'ERR_INVALID_ADDRESS_SYNTAX' => 422,
        'ERR_INVALID_HEX_CHARS' => 422,
        'ERR_EMPTY_QUERY' => 422,
        'ERR_TEXT_TOO_LONG' => 422,
        'ERR_UNKNOWN_MODE' => 422,
        'ERR_INVALID_PAGE_LENGTH' => 422,
        'ERR_OUT_OF_BOUNDS' => 404,
        'ERR_TOO_MANY_REQUESTS' => 429,
        // Comptes (issue #16). Codes provisoires : le cahier des charges renvoie leurs noms au contrat
        // d'API commun (« inscriptions fermées, accès refusé… »).
        'ERR_INVALID_CREDENTIALS' => 401,
        'ERR_REGISTRATION_CLOSED' => 403,
        // Marque-pages (issue #18, règle 7). 409 Conflict : la requête est bien formée, mais elle contredit
        // l'état actuel (la page est déjà gardée). Code provisoire, comme les deux précédents.
        'ERR_ALREADY_BOOKMARKED' => 409,
        // Liens courts (issue #21, V12). Le CDC annonce ces trois cas sans les nommer (« lien court inconnu,
        // expiré ou masqué »). 410 Gone : le lien a existé mais ne mène plus nulle part, à la différence de 404.
        'ERR_LINK_NOT_FOUND' => 404,
        'ERR_LINK_EXPIRED' => 410,
        'ERR_LINK_HIDDEN' => 410,
        // Signalements (issue #22). Un membre ne signale qu'une fois le même contenu (choix du groupe).
        'ERR_ALREADY_REPORTED' => 409,
        // Textes bannis (issue #35). Jeton réseau absent ou faux sur la route des textes bannis (B5).
        'ERR_INVALID_NETWORK_TOKEN' => 401,
    ];

    /** @param  list<array{code: string, champ: ?string, message: string}>  $erreurs */
    public function __construct(public readonly array $erreurs)
    {
        // Le message de l'exception reste générique : il peut finir dans les logs, jamais l'entrée du client.
        parent::__construct('Erreur renvoyée au client');
    }

    public static function depuisMoteur(ErreurMoteur $erreur): self
    {
        return new self($erreur->versTableau());
    }

    public static function depuisValidation(ResultatValidation $resultat): self
    {
        return new self($resultat->erreurs);
    }

    /**
     * Une réponse ne porte qu'un statut : 422 dès qu'une erreur de forme figure dans la liste,
     * 404 quand il ne reste que ERR_OUT_OF_BOUNDS. null : la liste contient un code qui signale
     * un bug chez nous, la réponse sera un 500 ordinaire (et l'erreur ira dans les logs).
     */
    public function statut(): ?int
    {
        $statuts = array_map(fn (array $erreur) => self::STATUTS[$erreur['code']] ?? null, $this->erreurs);

        if ($statuts === [] || in_array(null, $statuts, true)) {
            return null;
        }

        return in_array(422, $statuts, true) ? 422 : max($statuts);
    }

    /** Appelée par Laravel. null : Laravel reprend la main et répond 500. */
    public function render(): ?JsonResponse
    {
        return $this->reponse();
    }

    /** @param  array<string, string>  $entetes  par exemple Retry-After pour un 429 */
    public function reponse(array $entetes = []): ?JsonResponse
    {
        $statut = $this->statut();
        if ($statut === null) {
            return null;
        }

        // Chaque erreur a les trois clés, champ à null quand il n'y en a pas (un quota dépassé, par exemple).
        $erreurs = array_map(fn (array $erreur) => [
            'code' => $erreur['code'],
            'champ' => $erreur['champ'] ?? null,
            'message' => $erreur['message'],
        ], $this->erreurs);

        return response()->json(['erreurs' => $erreurs], $statut, $entetes);
    }
}
