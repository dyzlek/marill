# 12 · Se connecter avec GitHub (issue #4, décision du groupe du 18 novembre)

## Ce qui est fait

Email + mot de passe **et** « Se connecter avec GitHub » (Socialite 5).

- `Auth\GithubController` : `GET /auth/github/redirect` (aller) et `GET /auth/github/callback` (retour), dans `routes/web.php`, avec `throttle:10,1,oauth`.
- Rapprochement par **`users.github_id`** (nullable, unique, hors `#[Fillable]`), **jamais par email**.
- Un email déjà pris par un compte local : refus `ERR_OAUTH_EMAIL_TAKEN`.
- `password` nullable : un membre venu de GitHub n'a pas de mot de passe.
- Inscriptions fermées et suspension appliquées aussi ici.
- Retour toujours vers `config('app.frontend_url')`, échec en `/connexion?erreur=CODE`.

## Pourquoi

- **Flux par code d'autorisation** : le secret client ne passe jamais par le navigateur. Le code s'échange contre un jeton de serveur à serveur.
- **Le `state`** relie l'aller et le retour (protection contre un retour falsifié). C'est pour ça que les routes sont dans `web.php` : il faut la session pour garder le state. Jamais `->stateless()`.
- **Pas de rapprochement par email** : nos emails ne sont pas vérifiés. Quelqu'un pourrait créer chez nous un compte avec l'email d'un autre, puis se faire relier au GitHub de la vraie personne. L'identifiant GitHub, lui, est stable et sûr.
- **On ne garde pas le jeton GitHub** : une donnée qu'on ne stocke pas ne peut pas fuir.
- **Adresse de retour lue dans la config**, jamais dans la requête : sinon, redirection ouverte.
- *Si GitHub est en panne ?* Les comptes email + mot de passe se connectent toujours. Un compte GitHub peut aussi se choisir un mot de passe ([18](18-modifier-supprimer-compte.md)).

## Tests

`tests/Feature/Auth/GithubTest.php` (10 tests, Socialite simulé).
