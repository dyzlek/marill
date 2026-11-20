# 08 · Une page au hasard (issue #13)

## Ce qui est fait

`GET /api/hasard` (publique) → une page tirée au hasard, même forme que la lecture (`PageResource`), avec `Cache-Control: no-store`.

`App\Babel\Hasard` :

1. tire un **numéro de page uniforme** dans [0, 29^3200[ ;
2. le découpe en adresse **dans l'ordre de lecture** (`adresseDuNumero()`) : la page varie le plus vite, puis le volume, l'étagère, le mur et enfin l'hexagone, écrit en base 36.

## Pourquoi

- **Le piège** : 29^3200 est un nombre de 15 546 bits. Avec un entier PHP (`random_int`), on ne tomberait que dans les tout premiers hexagones, alors que 79 % des pages sont dans un hexagone dont le nom fait 3 004 chiffres. D'où **GMP**.
- **`random_bytes`** : le générateur cryptographique du système, sans graine à fournir ni à deviner.
- **Tirage avec rejet** : on tire juste assez de bits ; si le nombre dépasse, on recommence. Toutes les pages restent également probables. On réussit plus d'une fois sur deux, donc il faut en moyenne moins de deux essais.
- **`no-store`** : chaque appel doit donner une nouvelle page, ni le navigateur ni un proxy ne doivent garder la réponse.

## Tests

`tests/Unit/HasardTest.php` (numéro 0 = `0-w1-s1-v1:1`, dernier numéro = page unique du dernier hexagone, adresses tirées toujours valides) et `tests/Feature/Api/PageAuHasardTest.php`.
