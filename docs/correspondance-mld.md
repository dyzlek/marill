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

## Restent à régler pendant les migrations (milestone 2)

Ce ne sont pas des questions de nom, mais le MLD et Eloquent ne se rencontrent pas tout seuls :

- **Clés primaires qui ne sont pas un `id`** : `VOTE` (`user_id`, `adresse_id`), `COLLECTION_PAGE` (`collection_id`, `position`), `ACTIVITE_JOUR` (`jour`), `REGLAGE` (`cle`). Eloquent ne gère pas bien les clés composées.
- **`name` du starter kit et `pseudo` du MLD** dans `users`.
