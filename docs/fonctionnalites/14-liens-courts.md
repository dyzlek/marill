# 14 · Liens courts (issue #21 : V11, V12)

## Ce qui est fait

- `POST /api/liens-courts` (public) : cible **une adresse** (page ou livre) **ou une collection** publique et non masquée. `passage` facultatif (texte à surligner), seulement avec une page.
- `GET /api/liens-courts/{slug}` : compte la visite (`increment`) et renvoie `LienCourtResource` (type page / livre / collection ; `ouvrir` = page 1 pour un livre).
- Auteur = `auth('sanctum')->user()` s'il y en a un. Sinon le lien **expire** au bout de 30 jours (`config('bibliotheque.jours_avant_expiration_lien_visiteur')`, choix du groupe).
- Slug de **8 symboles** sans `l`, `o`, `0`, `1`, tiré par `random_int`, avec un nouvel essai en cas de collision.
- Erreurs : 404 `ERR_LINK_NOT_FOUND`, 410 `ERR_LINK_EXPIRED` / `ERR_LINK_HIDDEN` (lien masqué, page marquée d'un texte banni, collection masquée ou redevenue privée).
- Limite de débit : depuis l'issue #26, quota « publications » réglable dans le back-office ([22](22-limite-de-debit.md)).

## Pourquoi

- **Règle 11** : exactement une cible ; sans compte, expiration obligatoire. Le modèle le vérifie avant d'écrire, et des `CHECK` le vérifient en base.
- **`random_int`** (générateur cryptographique) : on ne peut pas deviner les liens des autres en essayant les slugs voisins. 32^8, c'est plus de mille milliards de slugs.
- **Sans `l/1` ni `o/0`** : un lien se lit à l'oral ou sur un QR code imprimé.
- **410 Gone** plutôt que 404 : le lien a existé, mais il ne mène plus nulle part.
- **On garde le passage, pas sa position** : le navigateur la recalcule avec `localiser` (F4).

## Reste à faire

Fait depuis : la vérification des textes bannis à la création ([28](28-textes-bannis.md)) et le nettoyage nocturne des liens expirés ([31](31-entretien.md)).

## Tests

`tests/Feature/Api/LiensCourtsTest.php` (21 tests). Bruno : dossier `Liens courts`.
