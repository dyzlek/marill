# 10 · L'intégration continue (issue #14)

## Ce qui est fait

`.github/workflows/tests.yml`, **à la racine du dépôt**, se lance à chaque push sur `main` et à chaque pull request :

1. PHP 8.5 avec **gmp** et **intl** ;
2. un `.env` neuf avec une `APP_KEY` et une `BABEL_KEY` **jetables** (`php artisan babel:cle` dans le job) ;
3. build Vite ;
4. `composer ci:check` : **Pint** (style), **PHPStan niveau 7** (types), **Pest** (tests).

Dependabot met à jour les actions épinglées.

## Pourquoi

- **À la racine** : GitHub ne lit que `.github/` à la racine. Celui du starter kit (`backend/.github/`) a été déplacé.
- **Clé jetable, jamais la vraie** : la vraie `BABEL_KEY` ne doit sortir ni du serveur ni du gestionnaire de mots de passe. Pas même dans un secret GitHub : la CI n'a besoin que d'une clé, pas de *la* clé.
- **La même chaîne en local** : `composer test` dans `backend/` avant de pousser. Si ça passe chez soi, ça passe en CI.
- **PHPStan** attrape avant l'exécution ce que les tests ne couvrent pas (propriété inexistante, mauvais type). Exemple : il a repéré des `$recherche->mode->value` sans le type de l'enum déclaré sur le modèle.

## Pour l'oral

*Que se passe-t-il si un test échoue ?* La PR est marquée en rouge sur GitHub, on ne fusionne pas.
