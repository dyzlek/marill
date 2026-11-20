# 09 · Le modèle de données (issues #1 et #15)

Le détail table par table est dans [`docs/correspondance-mld.md`](../correspondance-mld.md). Ici, les choix à défendre.

## Ce qui est fait

- **14 tables** (migrations `2026_11_18_*`), modèles dans `app/Models/`, enums dans `app/Enums/`.
- `users` est la table `UTILISATEUR` : `pseudo` remplace `name` dans tout le starter kit, plus `role`, `suspendu_le`, `derniere_connexion_le`, puis `github_id` ([12](12-connexion-github.md)).
- **Aucune table ne stocke le texte d'une page** (règle 1).
- `Adresse::enregistrer($bibliotheque, $adresse)` : F5, puis découpage en colonnes, puis empreintes SHA-256, puis `firstOrCreate`.
- `votes` et `collection_pages` ont une **clé composée** et passent par `belongsToMany` (`User::pagesVotees()`, `Collection::pages()`), avec des classes `Pivot`.
- Factories et `DatabaseSeeder` : comptes `admin@example.com` (admin) et `test@example.com` (membre), mot de passe `password`.

## Pourquoi

- **Noms de tables (décision #1)** : au pluriel, en français correct, comme le veut Laravel. `#[Table]` sur les trois modèles que Laravel met mal au pluriel (`liens_courts`, `textes_bannis`, `activites_jour`). `created_at` / `updated_at` / `password` à la place des noms du MLD : Laravel les remplit tout seul.
- **Unicité d'une adresse = (bibliothèque, empreinte SHA-256)** (règle 2) : un hexagone peut faire 3 004 caractères, et une base refuse un index unique aussi long. L'empreinte fait 64 caractères.
- **Les règles sont tenues deux fois** : par la base (index unique partiel pour la bibliothèque locale, `CHECK` nommés pour « une seule cible »), et par le modèle, qui lève `RegleNonRespectee` avec une phrase claire avant d'écrire.
- **`preventLazyLoading`** hors production : lire une relation non chargée lève une exception au lieu de lancer une requête par ligne (N+1).
- **Champs sensibles hors `#[Fillable]`** (`role`, `masque_le`, `est_locale`, `github_id`…) : un formulaire ne peut pas les remplir. Ils s'affectent à la main (`forceFill`).
- Laravel devine une clé étrangère depuis le nom du **modèle** ou de la relation, pas depuis le nom de la table : le choix des noms de tables ne change pas les relations.

## Tests

`tests/Feature/ModeleDeDonneesTest.php` (35 tests : chaque règle, cascades, clés composées).
