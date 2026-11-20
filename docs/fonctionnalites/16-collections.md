# 16 · Collections (issue #19 : M4, M5, M13, règle 8)

## Ce qui est fait

`apiResource('collections')` pour le membre connecté :

- `index` (avec `withCount('pages')`), `store`, `show`, `update` (PATCH partiel : nom, description, `est_publique`), `destroy` ;
- `POST /api/collections/{id}/pages` : ajoute une page **à la fin** (position max + 1, dans une transaction) ;
- `PUT /api/collections/{id}/pages` : la **liste complète** dans le nouvel ordre remplace l'ancienne (positions 1..n, 500 pages au plus, liste vide permise).

Toutes les adresses passent F5 (`validerPage`) **avant** d'écrire. `CollectionResource` : `masquee`, `nombre_pages` (`whenCounted`), `pages` [{position, page}] (`whenLoaded`).

## Pourquoi

- **Règle 8** : clé `(collection_id, position)`. Un texte découpé sur plusieurs pages se relit dans l'ordre, et la même page peut revenir deux fois.
- **Réordonner = renvoyer toute la liste** (PUT) : plus simple et plus sûr qu'un « déplacer la page 3 avant la page 1 » qui demanderait de décaler les autres positions une par une.
- **Tout vérifier avant d'écrire** : une seule adresse fausse, et rien ne change.
- **Transaction** sur l'ajout : deux ajouts simultanés ne prennent pas la même position.
- **`CollectionPolicy`** : seul l'auteur gère sa collection, même publique, sans exception pour l'admin. L'admin modère par la file des signalements (masquer), il ne modifie pas.
- **`whenLoaded` / `whenCounted`** : la Resource ne lance jamais de requête cachée. C'est au contrôleur de charger.

## Suite

La lecture publique des collections (V13, B3) est dans [23](23-collections-publiques-votes-populaires.md).

## Tests

`tests/Feature/Api/CollectionsTest.php` (18 tests, dont le N+1). Bruno : dossier `Collections`.
