# 17 · Annotations (issue #20 : M6, V14, règle 9)

## Ce qui est fait

- `apiResource('annotations')` pour le membre connecté : `index`, `store`, `show`, `update` (PATCH partiel), `destroy`.
- `AnnoterRequest` : `contenu` requis, ≤ `Annotation::LONGUEUR_MAX` (2 000) ; `est_publique` **requis**, sans valeur par défaut ; `bibliotheque` facultative (M13).
- F5 `validerPage` : on annote une page, jamais un livre.
- Plusieurs annotations sur la même page sont permises (le MLD ne met pas d'unicité).
- Réécrire une annotation masquée **ne la démasque pas**.
- **Lecture publique** `GET /api/pages/{adresse}/annotations` : les annotations publiques, non masquées, `nonSignalesPar` le lecteur (V18), avec le **pseudo** de l'auteur (`AnnotationPubliqueResource`), paginées par 50.

## Pourquoi

- **`est_publique` obligatoire** : publier est un choix, on ne le fait pas à la place du membre. Une valeur par défaut « publique » publierait sans le vouloir, et une valeur par défaut « privée » ferait croire au membre qu'il a publié.
- **Le pseudo, jamais l'email** : on ne publie pas de donnée personnelle.
- **Lecture sans rien écrire** : on cherche l'adresse sans l'enregistrer. Une page jamais annotée donne une liste vide, sans créer de ligne `adresses` pour chaque lecture.
- **Démasquer** est une décision de l'administrateur (A4), pas de l'auteur.
- Le texte d'une annotation n'est **pas vérifié automatiquement** contre les textes bannis (CDC) : il se signale (V17).

## Tests

`tests/Feature/Api/AnnotationsTest.php` (15 tests, dont le N+1). Bruno : dossier `Annotations`.
