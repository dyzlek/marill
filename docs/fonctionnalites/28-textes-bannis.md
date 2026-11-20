# 28 · Textes bannis (issue #35 : A5, A6, A7, B5, règles 16 à 18)

## Ce qui est fait (tout ce qui ne dépend que de nous)

Le travail est dans **`App\Babel\Moderation`** :

| Méthode | Rôle |
|---|---|
| `declarer($texte, $motif)` | **A5** : normalise (F3), refuse un texte vide ou de plus de 3 200 caractères, réutilise le texte s'il existe déjà (même empreinte), origine = notre bibliothèque, puis `appliquer()` |
| `appliquer($banni)` | statut `applique`. **Étape 1** : `chercher` en modes `exact` et `bruit`, marque les adresses obtenues si elles sont enregistrées. **Étape 2** : lance la tâche `ReverifierPublications` |
| `rejeter($banni)` | **A5 lever** (texte d'ici) ou **A7 rejeter** (texte proposé). Statut `rejete`, retire ses marques, revérifie ces pages contre les autres textes (**étape 5**) |
| `texteBanniDans($adresse)` | lit la page (les **410** pages pour un livre, **étape 4**) et cherche chaque texte appliqué dedans |
| `verifier($adresse)` | pose ou retire la marque `texte_banni_id` |
| `apercu($banni)` | **A7** : combien de nos pages publiées seraient touchées, sans rien écrire |

**Étape 3, au moment de publier** : les contrôleurs appellent `verifierAvantPublication()` :

- annotation créée ou modifiée en **publique** ;
- page ajoutée à une collection **publique**, liste rangée, ou collection qui **devient** publique ;
- **lien court** vers une page ou un livre.

**Ce qu'une marque cache** (règle 18) : les annotations de la page (`GET /api/pages/{adresse}/annotations` renvoie une liste vide), la page dans une collection publique (le reste de la collection reste visible), les pages populaires, les liens courts (410 `ERR_LINK_HIDDEN`). **La page elle-même reste lisible.**

**Tâche de fond** `App\Jobs\ReverifierPublications` : relit toutes les pages désignées par ce qui est publié. Elle est lancée à chaque application et **chaque nuit** (`routes/console.php`, `Schedule::job(...)->daily()`).

**Back-office** (`/admin/textes-bannis`) : déclarer (formulaire texte + motif), appliquer, lever ou rejeter, filtre par statut, badge du nombre de propositions en attente. Avant d'appliquer, la confirmation affiche l'aperçu (A7, étape 4 de la modération en réseau).

**A6 · B5** : `GET /api/reseau/textes-bannis`, la seule route publique qui exige le **jeton réseau** (`Authorization: Bearer …`, middleware `ExigerJetonReseau`). Elle renvoie nos textes **d'origine locale et appliqués** : texte, empreinte, motif, date. Sinon : 401 `ERR_INVALID_NETWORK_TOKEN` (code provisoire).

## Pourquoi

- **On masque ce qui est publié, jamais une page.** Un texte figure dans un nombre astronomique de pages : on vérifie celles que quelqu'un a désignées. C'est la méthode du CDC, étape par étape.
- **Le texte est stocké en clair, normalisé** (règle 16) : une empreinte seule ne permettrait pas de le chercher. L'empreinte sert à éviter les doublons.
- **Rejeter ne supprime rien** : on garde la trace de la décision (statut, `decide_le`).
- **Une tâche en file d'attente** (`ShouldQueue`) : relire des centaines de pages prend du temps, l'admin n'attend pas. `ShouldBeUnique` : dix décisions de suite ne lancent pas dix relectures en même temps. Le moteur est injecté dans `handle()` (jamais dans le constructeur : il refuse d'être sérialisé).
- **Le jeton réseau** : commun aux dix bibliothèques, dans le `.env` (`BABEL_JETON_RESEAU`), jamais dans le dépôt ni en base. Ce n'est pas un jeton Sanctum : il ne désigne personne, il prouve qu'on fait partie du réseau.
- **`hash_equals`** pour comparer le jeton : comparaison en temps constant. Avec `===`, le temps de réponse trahirait combien de caractères du début sont justes.
- **Jeton non configuré = personne n'entre** : on ne compare jamais avec une chaîne vide.
- **On ne republie pas un texte venu d'ailleurs** (règle 17) : chaque bibliothèque ne publie que ses propres décisions.
- **Le texte d'une annotation n'est pas vérifié** (CDC) : il se signale (V17).

## Reste à faire (milestone 3, besoin du réseau)

- **Relever les textes bannis des pairs** (B5 côté client) : une tâche qui appelle leur route avec le jeton et enregistre les nouveaux en `propose`. Il faut l'annuaire (#31) et le chemin définitif (contrat).
- **Vérifier les pages d'une autre bibliothèque** : il faut les lire chez elles (#34). Pour l'instant, `verifier()` les laisse de côté. Elles seront revues au passage nocturne, une fois la lecture distante en place.
- **V21** : filtrer les collections et pages populaires des pairs avant de les afficher (#36).

## Tests

`tests/Feature/ModerationTest.php` (16 tests) : déclarer, doublon, tâche de fond, lever, les trois cas de publication (annotation, lien vers une page ou un livre, collection), la route à jeton (sans, faux, non configuré, contenu), le back-office (déclarer, appliquer une proposition, aperçu).
