<?php

namespace App\Babel;

/**
 * Sortie de F5 : « valide » avec le niveau de l'adresse, sinon la liste de toutes les erreurs.
 * readonly : un résultat ne se modifie pas après coup.
 */
final readonly class ResultatValidation
{
    /**
     * @param  'page'|'livre'|null  $niveau  null quand l'adresse n'est pas valide
     * @param  list<array{code: string, champ: string, message: string}>  $erreurs  même forme que ErreurMoteur::versTableau()
     */
    public function __construct(
        public bool $valide,
        public ?string $niveau,
        public array $erreurs,
    ) {}
}
