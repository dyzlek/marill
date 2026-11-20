# 11 · Comptes : inscription, connexion, jetons (issue #16)

## Ce qui est fait

**Notre front Angular** utilise la **session** (cookie HttpOnly), par les routes de **Fortify** en JSON :

1. `GET /sanctum/csrf-cookie` (dépose le cookie `XSRF-TOKEN`) ;
2. `POST /register` (pseudo, email, password, password_confirmation) ou `POST /login` ;
3. `GET /api/user` → `UtilisateurResource` (id, pseudo, email, role) ;
4. `POST /logout`.

**Les clients extérieurs** (Bruno, scripts) utilisent des **jetons** : `POST /api/tokens` (email, password, nom) → jeton montré **une seule fois** ; `DELETE /api/tokens/courant` le révoque.

Autres points :

- `CreateNewUser` refuse si les inscriptions sont fermées (403 `ERR_REGISTRATION_CLOSED`, réglage A8, [21](21-configurer-la-bibliotheque.md)).
- `VerifierIdentifiants` (branché sur `Fortify::authenticateUsing`) refuse un compte **suspendu**.
- L'écouteur `NoterDerniereConnexion` date `derniere_connexion_le` à chaque connexion (utile pour A1 et A11).
- Vérification de l'email et réinitialisation du mot de passe **retirées** : le CDC les écarte.

## Pourquoi

- **Session pour notre front, jeton pour les autres.** Un jeton gardé dans `localStorage` peut être volé par une faille XSS ; un cookie HttpOnly ne se lit pas en JavaScript. Le même `auth:sanctum` accepte les deux.
- Session = **CSRF** : le front renvoie l'en-tête `X-XSRF-TOKEN` à chaque requête qui modifie (sinon 419). CORS est restreint à `FRONTEND_URL` avec `supports_credentials`.
- `POST /api/tokens` est limité (`throttle:5,1,jetons`) et répond un **refus vague** (401 `ERR_INVALID_CREDENTIALS`) : on ne dit pas si c'est l'email ou le mot de passe qui est faux, pour ne pas aider à deviner les comptes.
- **Authentification ≠ autorisation** : `auth:sanctum` dit *qui* envoie la requête (401 sinon). Une Policy dit *s'il a le droit* (403).

## Tests

`tests/Feature/Api/ComptesTest.php` (16 tests). Bruno : dossier `Compte` (« Créer un jeton » range le jeton dans `{{jeton}}`).
