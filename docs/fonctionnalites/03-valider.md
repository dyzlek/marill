# 03 · F5 valider une adresse (issue #9)

## Ce qui est fait

`App\Babel\Validateur` dit si une adresse est bien écrite et si elle existe, **sans jamais la corriger**. Il renvoie un `ResultatValidation` (`valide`, `type` page ou livre, `erreurs`).

- `valider($adresse)` : accepte une page (`…:12`) ou un livre (sans `:page`) ;
- `validerPage($adresse)` : refuse un livre, sur le champ `page` (pour lire une page, la garder, voter…) ;
- `dernierHexagone()` : le dernier hexagone de la bibliothèque, calculé avec **GMP**.

Erreurs (même forme que celles du moteur) : `ERR_INVALID_ADDRESS_SYNTAX`, `ERR_INVALID_HEX_CHARS`, `ERR_OUT_OF_BOUNDS`. **Toutes d'un coup**, au plus une par partie, dans l'ordre hexagone, mur, étagère, volume, page.

## Pourquoi

- **Une seule écriture valide par page** : pas de zéro devant, pas de majuscule, pas d'espace, pas de `trim()`. Sinon la même page aurait deux adresses, donc deux empreintes en base, et deux marque-pages pour la même page.
- **GMP** : il y a 29^3200 pages, et 262 400 pages par hexagone. Le dernier hexagone s'écrit avec 3 004 chiffres en base 36, bien trop pour un entier PHP de 64 bits. On le calcule une fois par processus (`static`).
- **Comparer sans convertir** : deux nombres en base 36 écrits sans zéro devant se comparent par la longueur, puis caractère par caractère. Pas besoin de convertir 3 004 chiffres à chaque requête.
- **Le dernier hexagone n'a qu'une page** (`w1-s1-v1:1`) : 29^3200 = k × 262 400 + 1.
- `\A…\z` plutôt que `^…$` : en PHP, `$` laisse passer un saut de ligne final.

## Tests

- `tests/Unit/ValidateurTest.php` : chaque erreur, chaque limite.
- `tests/Feature/ValidateurMoteurTest.php` : **4 000 adresses** comparées au moteur (même verdict, mêmes codes). Ce test a besoin de `BABEL_KEY`, que la CI génère (clé jetable).
