# Du MLD à la base : noms des tables et des colonnes

Décision du groupe du 17 novembre 2026 (issue #1). Le MLD du cahier des charges reste le modèle **logique** : ses entités et ses colonnes ne changent pas. Seuls les noms **physiques** suivent les conventions de Laravel, pour écrire moins de code de réglage et rester lisible pour quelqu'un qui connaît Laravel.

## Les trois choix

1. **Tables au pluriel, en minuscules, en français correct.** Laravel devine le nom de la table à partir du modèle (`Adresse` → `adresses`), mais il met au pluriel en anglais et seulement le dernier mot. Pour trois modèles, il se trompe : on écrit le bon nom avec l'attribut `#[Table('…')]`.
2. **On garde la table `users` du starter kit pour l'entité `UTILISATEUR`.** Les tables `passkeys` et `sessions` du starter kit y sont déjà reliées par `user_id`, et Fortify s'appuie sur le modèle `User`. On y ajoutera les colonnes du MLD (pseudo, rôle…).
3. **Colonnes de dates et mot de passe aux noms de Laravel** : `created_at`, `updated_at`, `password`. Laravel remplit les dates tout seul et hache le mot de passe avec le cast `hashed`. Toutes les autres colonnes gardent leur nom du MLD.

## Ce que ça ne change pas

Laravel devine une clé étrangère à partir du **nom du modèle** ou de la méthode de relation (`Adresse` → `adresse_id`), jamais à partir du nom de la table. Choisir le singulier ou le pluriel pour les tables ne change donc rien aux relations : seulement une ligne `#[Table]` par modèle quand le nom deviné ne convient pas.

## Tables

| MLD | Table | Modèle | Réglage dans le modèle |
|---|---|---|---|
| `UTILISATEUR` | `users` | `User` | aucun (starter kit) |
| `BIBLIOTHEQUE` | `bibliotheques` | `Bibliotheque` | aucun |
| `ADRESSE` | `adresses` | `Adresse` | aucun |
| `MARQUE_PAGE` | `marque_pages` | `MarquePage` | aucun |
| `COLLECTION` | `collections` | `Collection` | aucun (attention : `Illuminate\Support\Collection` existe, importer le bon) |
| `COLLECTION_PAGE` | `collection_pages` | `CollectionPage` | aucun |
| `ANNOTATION` | `annotations` | `Annotation` | aucun |
| `VOTE` | `votes` | `Vote` | aucun |
| `LIEN_COURT` | `liens_courts` | `LienCourt` | `#[Table('liens_courts')]` (Laravel devinerait `lien_courts`) |
| `SIGNALEMENT` | `signalements` | `Signalement` | aucun |
| `TEXTE_BANNI` | `textes_bannis` | `TexteBanni` | `#[Table('textes_bannis')]` (Laravel devinerait `texte_bannis`) |
| `RECHERCHE` | `recherches` | `Recherche` | aucun |
| `ACTIVITE_JOUR` | `activites_jour` | `ActiviteJour` | `#[Table('activites_jour')]` (Laravel devinerait `activite_jours`) |
| `REGLAGE` | `reglages` | `Reglage` | aucun |

## Colonnes renommées

| MLD | Base | Où |
|---|---|---|
| `cree_le` | `created_at` | toutes les tables qui l'ont |
| `maj_le` | `updated_at` | `COLLECTION`, `ANNOTATION` |
| `mot_de_passe_hache` | `password` | `users` |
| `utilisateur_id` | `user_id` | `MARQUE_PAGE`, `COLLECTION`, `ANNOTATION`, `VOTE`, `LIEN_COURT`, `RECHERCHE` |

Conséquences à connaître :

- `user_id` suit la table `users` : c'est la convention de Laravel, et le nom que `passkeys` et `sessions` utilisent déjà. `User::marquePages()` (`hasMany`) n'a ainsi besoin d'aucune clé précisée.
- `auteur_id` et `traite_par_id` (`SIGNALEMENT`) et `origine_id` (`TEXTE_BANNI`) gardent leur nom du MLD : la relation précise la clé, puisqu'elle ne s'appelle pas comme le modèle visé (`belongsTo(User::class, 'auteur_id')`).
- Une table du MLD qui a `cree_le` sans `maj_le` (`ADRESSE`, `MARQUE_PAGE`, `VOTE`, `LIEN_COURT`, `SIGNALEMENT`, `TEXTE_BANNI`, `RECHERCHE`) n'a pas de `updated_at` : son modèle désactive cette colonne (`const UPDATED_AT = null;`).
- `COLLECTION_PAGE`, `ACTIVITE_JOUR` et `REGLAGE` n'ont aucune date : `#[Table(timestamps: false)]` (combiné au nom quand il faut).

## Décisions des migrations (issue #15, 17 novembre 2026)

Le MLD et Eloquent ne se rencontrent pas tout seuls sur trois points. Le groupe a choisi :

### 1. `pseudo` remplace `name` dans `users`

La colonne s'appelle `pseudo` (50 caractères, unique), comme dans le MLD : une seule notion, un seul nom. Le starter kit a été adapté : la migration `users`, le modèle `User` (`#[Fillable]`, `initials()`), `CreateNewUser`, `ProfileValidationRules::pseudoRules()` (`max:50`, `unique`), les pages d'inscription et de profil, les menus et leurs tests. On a modifié la migration d'origine plutôt qu'en ajouter une : rien n'est en production, et `migrate:fresh` reconstruit la base.

`users` garde aussi `email_verified_at`, `remember_token`, `updated_at` et les colonnes de double authentification du starter kit : Fortify en a besoin. `role` (`membre` par défaut), `suspendu_le` et `derniere_connexion_le` viennent du MLD. `role` n'est pas dans `#[Fillable]` : un formulaire ne doit jamais pouvoir créer un admin. `User::isAdmin()` est la seule définition de l'admin.

### 2. Clés composées : la vraie clé du MLD, et des relations « plusieurs à plusieurs »

- `votes` a la clé primaire (`user_id`, `adresse_id`) et `collection_pages` la clé primaire (`collection_id`, `position`), comme au MLD. C'est la base qui interdit un second vote, ou deux pages à la même position.
- Eloquent ne sait pas retrouver **un** modèle par une clé composée (`find()`, `save()` d'une ligne seule). Ces deux tables sont donc des **tables pivots**, qu'on écrit par les relations `belongsToMany` : `User::pagesVotees()` (`attach($adresse->id, ['valeur' => 1])`) et `Collection::pages()` (`attach($adresse->id, ['position' => 3])`, triées par `orderByPivot('position')`). `Vote` et `CollectionPage` sont des classes `Pivot` (`->using(...)`), pas des modèles autonomes. On lit les votes d'une page par `Adresse::votes()` pour les additionner.
- `votes.created_at` est rempli par la base (`useCurrent()`), car une table pivot n'est pas datée comme un modèle. Il n'est pas dans `withPivot` : sinon Laravel daterait la ligne avec les colonnes de `User`, dont un `updated_at` que `votes` n'a pas.
- `activites_jour` (`jour`) et `reglages` (`cle`) ont une clé simple qui n'est pas un entier : `#[Table(key: …, keyType: 'string', incrementing: false)]`, ce qu'Eloquent gère très bien.

### 3. Les règles tenues deux fois : par la base, et par le modèle

| Règle | Dans la base | Dans le modèle |
|---|---|---|
| 5 : une seule bibliothèque `est_locale` | index unique **partiel** `bibliotheques_une_seule_locale` (`WHERE est_locale`), écrit en SQL : le schema builder ne sait pas faire | `Bibliotheque` (`saving`) |
| 11 : un lien court a exactement une cible ; un visiteur expire ; un passage va avec une adresse | contraintes `CHECK` nommées `lien_court_une_seule_cible`, `lien_court_visiteur_expire`, `lien_court_passage_vers_une_adresse` | `LienCourt` (`saving`) |
| 13 : un signalement vise exactement un contenu | `CHECK` `signalement_une_seule_cible` | `Signalement` (`saving`) |
| 10 : un vote vaut +1 ou −1 | `CHECK` `vote_plus_ou_moins_un` | (validation de la requête, issue #27) |

- **Pourquoi deux fois.** La base est la dernière barrière : elle refuse une ligne fausse même si elle vient de tinker, de Filament ou d'une requête SQL. Le modèle vérifie avant d'écrire pour donner un message en français qui nomme la règle (`App\Exceptions\RegleNonRespectee`), au lieu d'une « Integrity constraint violation ». C'est une erreur de programmation (un contrôleur doit valider avant) : elle reste un 500 journalisé, pas un 422.
- **Comment, sans `->check()`.** Laravel 13 n'a pas de méthode pour une contrainte `CHECK`. On l'écrit dans la définition d'une colonne avec `rawColumn()`, comme Laravel le fait lui-même pour `enum()` sous SQLite. SQLite accepte qu'une telle contrainte lise les autres colonnes de la ligne. La clé étrangère de cette colonne se déclare alors à part (`$table->foreign(...)`).
- **Portabilité.** Le SQL écrit est accepté par SQLite et PostgreSQL. MySQL n'a pas d'index partiel : à revoir si on change de base.

### Suppressions (règles 13, 18, 19)

- Supprimer un compte (`cascadeOnDelete` sur `user_id`) efface ses marque-pages, collections (et leurs pages, leurs liens courts), annotations, votes, recherches et liens courts. Ses signalements restent, sans auteur (`auteur_id` en `nullOnDelete`).
- Supprimer un contenu signalé supprime ses signalements (`cascadeOnDelete` sur les trois cibles).
- Supprimer un texte banni retire sa marque des adresses (`texte_banni_id` en `nullOnDelete`) sans les supprimer.
- Une adresse ou une bibliothèque encore citée ne se supprime pas (`restrictOnDelete`) : une adresse est partagée par tout ce qui la désigne.

### Enregistrer une adresse

`Adresse::enregistrer($bibliotheque, $adresse)` passe l'adresse par F5 (`Validateur`), la découpe en colonnes et calcule `empreinte` (SHA-256 de l'adresse) et `empreinte_livre` (SHA-256 de l'adresse sans `:page`). Elle retrouve la ligne si elle existe déjà (règle 2). `$adresse->ecriture()` redonne l'adresse écrite.
