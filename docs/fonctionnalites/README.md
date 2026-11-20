# Les fonctionnalités du back-end, une par fiche

Chaque fiche explique **ce qui est fait**, **comment** (les fichiers), **pourquoi** (les choix à défendre à l'oral), comment c'est **testé** et ce qui **reste à faire**. La référence reste [`docs/cahier-des-charges.md`](../cahier-des-charges.md) ; le passage du MLD aux tables est dans [`docs/correspondance-mld.md`](../correspondance-mld.md).

Les chemins d'API sont **provisoires** tant que le contrat d'API commun n'est pas publié (voir [27](27-contrat-api-commun.md)).

## Socle serveur (milestone 1)

| # | Fiche | Issue | Stories |
|---|---|---|---|
| 01 | [Installer l'API et Sanctum](01-installation-api.md) | #6 | — |
| 02 | [F3 normaliser](02-normaliser.md) | #8 | F3 |
| 03 | [F5 valider une adresse](03-valider.md) | #9 | F5 |
| 04 | [Le format des erreurs](04-format-des-erreurs.md) | #7 | — |
| 05 | [Lire une page](05-lire-une-page.md) | #10 | V1, B1 |
| 06 | [Chercher un texte](06-chercher.md) | #11 | V4, V5, V6, B2 |
| 07 | [L'identité de la bibliothèque](07-identite.md) | #12 | B6, F8 |
| 08 | [Une page au hasard](08-page-au-hasard.md) | #13 | V9, F7 |
| 09 | [Le modèle de données](09-modele-de-donnees.md) | #1, #15 | MLD |
| 10 | [L'intégration continue](10-integration-continue.md) | #14 | — |

## Données et comptes (milestone 2)

| # | Fiche | Issue | Stories |
|---|---|---|---|
| 11 | [Comptes : inscription, connexion, jetons](11-comptes.md) | #16 | V19, M1 |
| 12 | [Se connecter avec GitHub](12-connexion-github.md) | #4 | décision |
| 13 | [Marque-pages](13-marque-pages.md) | #18 | M2, M3, M13 |
| 14 | [Liens courts](14-liens-courts.md) | #21 | V11, V12 |
| 15 | [Signalements](15-signalements.md) | #22 | V17, V18 |
| 16 | [Collections](16-collections.md) | #19 | M4, M5, M13 |
| 17 | [Annotations](17-annotations.md) | #20 | M6, V14 |
| 18 | [Modifier et supprimer son compte](18-modifier-supprimer-compte.md) | #17 | M9, M10 |
| 19 | [Back-office : les comptes](19-back-office-comptes.md) | #23 | A1, A2, A3 |
| 20 | [Back-office : la file des signalements](20-file-des-signalements.md) | #24 | A4 |
| 21 | [Configurer la bibliothèque](21-configurer-la-bibliotheque.md) | #25 | A8 |
| 22 | [Limite de débit de l'API publique](22-limite-de-debit.md) | #26 | A10 |
| 23 | [Collections publiques, votes, pages populaires](23-collections-publiques-votes-populaires.md) | #27 | V13, B3, B4, M7 |
| 24 | [Historique des recherches](24-historique-des-recherches.md) | #28 | M8 |
| 25 | [Export et import du compte](25-export-import.md) | #29 | M11, M12 |
| 26 | [Statistiques par jour](26-statistiques.md) | #30 | A11 |
| 27 | [Le contrat d'API commun (en attente)](27-contrat-api-commun.md) | #5 | — |

## Réseau et évaluation (milestones 3 et 4), ce qui ne dépend que de nous

| # | Fiche | Issue | Stories |
|---|---|---|---|
| 28 | [Textes bannis](28-textes-bannis.md) | #35 | A5, A6, A7, B5 |
| 29 | [L'état du réseau dans le back-office](29-etat-du-reseau.md) | #32 (écran) | A9 |
| 30 | [Documenter l'API en OpenAPI](30-openapi.md) | #38 | — |
| 31 | [Entretien : liens expirés, avatar, tâches planifiées](31-entretien.md) | — | — |

## Vérifier soi-même

Dans `backend/` :

```bash
composer test                 # Pint + PHPStan + Pest, la même chaîne que la CI
php artisan route:list --path=api
php artisan migrate:fresh --seed   # comptes admin@example.com et test@example.com, mot de passe « password »
php artisan serve             # puis http://localhost:8000/admin pour le back-office
```

Les requêtes Bruno sont dans `bruno/` (un dossier par fonctionnalité).
