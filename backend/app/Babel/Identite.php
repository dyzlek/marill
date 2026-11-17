<?php

namespace App\Babel;

/** Ce qu'une bibliothèque dit d'elle-même au réseau (B6). Jamais la clé : seulement son empreinte. */
final readonly class Identite
{
    public function __construct(
        public string $identifiant,
        public string $nom,
        public ?string $description,
        public string $empreinte,
    ) {}
}
