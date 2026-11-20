# 23 · Collections publiques, votes et pages populaires (issue #27 : V13, B3, B4, M7)

## Ce qui est fait

### V13 · B3 : collections publiques (sans compte)

- `GET /api/collections-publiques` : les collections **publiques et non masquées**, la plus récemment modifiée d'abord, par 50, avec le **pseudo** de l'auteur et le nombre de pages.
- `GET /api/collections-publiques/{id}` : une collection avec ses pages dans l'ordre. Privée, masquée ou inconnue : **404**, sans dire laquelle.
- `CollectionPubliqueController`, `CollectionPubliqueResource`.

Ce qui n'est jamais montré : une collection privée ou masquée (règle 14) ; une collection que le lecteur a signalée (V18) ; **dans** une collection, une page marquée d'un texte banni appliqué (règle 18 : seule cette page disparaît, le reste reste visible).

### M7 : voter pour ou contre une page (membre)

- `PUT /api/pages/{adresse}/vote` avec `{"valeur": 1}` ou `{"valeur": -1}` (et `bibliotheque` facultative, M13) → 200.
- `DELETE /api/pages/{adresse}/vote` → 204.
- F5 `validerPage` : on ne vote pas pour un livre.

### B4 : pages populaires (sans compte)

- `GET /api/pages-populaires` : les **20** pages de chez nous au meilleur score.
- **score = somme des votes (+1 / −1) + nombre de marque-pages**. La réponse donne `adresse`, `votes`, `marque_pages`, `score`.

## Pourquoi

- **Une route à part** (`collections-publiques`) plutôt que d'ouvrir `GET /api/collections` : celle-ci montre *mes* collections (privées comprises), avec une Policy « auteur seulement ». Mélanger les deux dans une même route ferait dépendre la réponse de qui la demande, avec un risque de fuite. Le chemin définitif viendra du contrat commun.
- **Le pseudo, jamais l'email ni l'id de l'auteur** : `CollectionPubliqueResource` est différente de `CollectionResource`.
- **404 pour une collection privée**, comme pour une inconnue : on ne révèle pas qu'elle existe.
- **PUT pour voter** : on pose « mon vote sur cette page ». Envoyé deux fois, il donne le même état (idempotent). Revoter change la valeur, on ne cumule pas (règle 10 : clé composée `(user_id, adresse_id)`).
- **`upsert` pour voter** : **une** requête qui crée la ligne si elle manque, sinon change la valeur. On passe par la table plutôt que par `syncWithoutDetaching`, qui voulait dater une colonne `updated_at` que `votes` n'a pas.
- **Les pages populaires sont calculées, jamais stockées** (règle 10) : `withSum('votes', 'valeur')` + `withCount('marquePages')` dans **une seule** requête SQL. Un total anonyme : on ne dit ni qui a voté, ni qui garde la page.
- **Un marque-page compte comme un vote pour** : le CDC dit « à partir des votes et du nombre de marque-pages » sans donner la formule. **Choix du groupe**, à défendre ou à changer (une ligne dans `PagesPopulairesController`).
- **Seulement nos pages** : les pages populaires d'une autre bibliothèque sont à elle (V21, issue #36).
- **N+1** : la liste des collections publiques se fait en un nombre fixe de requêtes, quel que soit le nombre de collections (test).

## Reste à faire

Les collections et pages populaires **des autres bibliothèques** (V21, #36), en relisant leurs pages pour écarter celles qui contiennent un texte banni chez nous.

## Tests

`tests/Feature/Api/DecouverteTest.php` (9 tests). Bruno : dossier `Decouverte`.
