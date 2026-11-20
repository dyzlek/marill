# 21 · Configurer la bibliothèque (issue #25 : A8)

## Ce qui est fait

Page Filament **Ma bibliothèque** (`/admin/reglages`, `app/Filament/Pages/ReglagesBibliotheque.php`) avec trois blocs :

1. **Identité publiée (B6)** : identifiant (affiché, non modifiable), **nom** (requis, ≤ 120), **description** (≤ 1 000) ;
2. **Comptes** : **inscriptions ouvertes ou fermées** ;
3. **Limite de débit (A10)** : les deux quotas ([22](22-limite-de-debit.md)).

Où chaque valeur est rangée (règle 22) :

| Valeur | Rangement |
|---|---|
| nom, description | ligne locale de `bibliotheques` (`est_locale = true`) |
| inscriptions, quotas | table `reglages` (clé / valeur) |
| clé du moteur, jeton réseau | **jamais en base** : `.env` du serveur |

Autres changements :

- `Bibliotheque::locale()` **crée la ligne locale au premier besoin** à partir de `config/bibliotheque.php`. Ensuite, c'est la base qui fait foi.
- `IdentiteLocale` lit cette ligne : `GET /api/identite` publie le nom choisi dans le back-office ([07](07-identite.md)).
- `Reglage::ecrire()`, `Reglage::valeur()` avec des valeurs par défaut (`Reglage::PAR_DEFAUT`) et un **cache** vidé à chaque enregistrement (événements `saved` et `deleted` du modèle).
- Inscriptions fermées : `POST /register` répond 403 `ERR_REGISTRATION_CLOSED`. Ça existait déjà, le réglage est maintenant modifiable.

## Pourquoi

- **Le nom et la description sur la ligne locale** (règle 22) : notre bibliothèque est une ligne comme les autres (règle 5). Les pairs qu'on découvrira par l'annuaire auront leurs nom et description dans la même table.
- **L'identifiant n'est pas modifiable** (règle 6) : c'est lui qui dit qui nous sommes sur le réseau. L'URL peut changer, l'identifiant jamais.
- **Une transaction** pour enregistrer : on n'enregistre pas le nom sans les réglages, ou l'inverse.
- **Le cache des réglages** : les quotas sont lus à **chaque** requête de l'API publique. Sans cache, chaque requête ferait une requête SQL de plus. Le cache se vide dès qu'on enregistre, donc une modification prend effet tout de suite.
- **`config/bibliotheque.php` reste** pour la première création : une bibliothèque neuve a une identité sans passer par le back-office.
- Une page Filament plutôt qu'une ressource : il n'y a qu'**un** jeu de réglages, pas une liste.

## Tests

`tests/Feature/BackOfficeTest.php`, bloc « A8 · A10 » : le formulaire enregistre tout, le nom est obligatoire, l'identité publiée suit la ligne locale, les inscriptions fermées donnent 403.
