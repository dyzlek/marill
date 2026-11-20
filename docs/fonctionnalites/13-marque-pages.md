# 13 · Marque-pages (issue #18 : M2, M3, M13)

## Ce qui est fait

`apiResource('marque-pages')` réservé aux membres : `index`, `store`, `show`, `destroy` (pas de modification, le CDC n'en prévoit pas).

- `GarderMarquePageRequest` : `adresse` requise ; `bibliotheque` facultative (identifiant connu, la nôtre si absente : M13) ; `titre` ≤ 120.
- **F5 `validerPage` d'abord** : un livre est refusé (règle 4).
- `Adresse::enregistrer()` : l'adresse n'est enregistrée qu'une fois (règle 2).
- Doublon (même page déjà gardée) : l'index unique de la base le refuse, traduit en **409 `ERR_ALREADY_BOOKMARKED`**.
- Liste paginée par 50, avec `with('adresse.bibliotheque')`.
- `AdresseResource` (adresse, bibliotheque, locale) : réutilisée partout ailleurs.

## Pourquoi

- **`#[Middleware('auth:sanctum')]` et `#[Authorize]`** (attributs Laravel 13) sur le contrôleur, et une **`MarquePagePolicy`** : 403 si ce n'est pas le sien, **sans exception pour l'admin**. Un marque-page est privé (règle 7), même l'administrateur n'a pas à le lire.
- **409 Conflict** : la requête est bien formée, mais elle contredit l'état actuel.
- **On laisse la base trancher le doublon** (index unique) plutôt que de vérifier avant d'écrire : deux requêtes simultanées ne passent pas toutes les deux.
- **M13** : une page d'une autre bibliothèque se garde comme une page d'ici. Seule la colonne `bibliotheque_id` change.

## Tests

`tests/Feature/Api/MarquePagesTest.php` (17 tests, dont le N+1). Bruno : dossier `Marque-pages`.
