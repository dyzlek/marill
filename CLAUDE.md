# CLAUDE.md : La Bibliothèque de Babel en réseau

Contexte pour Claude. À lire avant toute modification.

## Le projet en bref

Projet fil rouge **R5.06 (Back) + R5.05 (Front)**, BUT MMI 3, parcours Développement web et dispositifs interactifs, IUT de Béziers (Université de Montpellier). Enseignant : Damien Marill. Groupe de l'utilisateur : **Babel 3**.

Inspiré de la nouvelle de Borges et du site libraryofbabel.info : une bibliothèque contient **toutes les pages possibles** de texte. Chaque adresse renvoie une page de lettres sans sens, et une phrase peut s'y cacher.

Le réseau : **10 bibliothèques**, codées séparément, qui se parlent via une **API publique commune** (comme Mastodon ou PeerTube) et répondent exactement pareil au même contrat. Le réseau est branché le **2 décembre**.

## Où en est le projet

1. Atelier cahier des charges (2 et 5 novembre) : terminé. Nos livrables sont dans `docs/atelier/livrables-babel3/`.
2. **Cahier des charges final du 15 novembre 2026** : reçu. Sa version texte complète (contenu visible seulement, relu sur le rendu) est dans [`docs/cahier-des-charges.md`](docs/cahier-des-charges.md). **C'est la référence pour tout le code.** Ne jamais la régénérer par extraction de texte du PDF : la couche texte du PDF est piégée. Le PDF (`Babel-cahier-des-charges-final.pdf`) reste hors du dépôt.
3. À venir : le moteur et la plateforme (cours du 17 novembre), puis le **contrat d'API commun** (routes, forme des erreurs, format d'export), pas encore publié.
4. Prochaine étape de code : créer le projet Laravel dans `backend/` quand le moteur est fourni.

## L'essentiel du cahier des charges (détails dans docs/cahier-des-charges.md)

- **Adresse** : `<hexagone>-w<mur>-s<étagère>-v<volume>:<page>`. Hexagone en base 36 (chiffres + minuscules), ≤ 3 004 caractères ; mur 1–4, étagère 1–5, volume 1–32, page 1–410. Sans `:<page>` = un livre (lien court seulement).
- **Page** : exactement 3 200 caractères parmi 29 symboles (a–z, espace, virgule, point).
- **8 fonctions** : lire, chercher, empreinte (moteur) ; normaliser, valider, auHasard (serveur) ; localiser, voisine (application web).
- **Codes d'erreur** `ERR_…` en anglais, en majuscules, renvoyés en liste (code, champ, message). 422 pour la forme, 404 si seul `ERR_OUT_OF_BOUNDS`, 429 pour le quota.
- **Clé du moteur** : dans la configuration du serveur uniquement. Jamais en base, réponse, journal, front. Seule l'empreinte est publiée.
- **14 tables**, aucune ne stocke de texte de page. Unicité d'une adresse : `(bibliotheque_id, empreinte SHA-256)`. Annotation ≤ 2 000 caractères.
- **Réseau** : ouvert en lecture sans jeton, sauf la route des textes bannis (jeton réseau). 2 s max par pair, hors ligne au 3e échec de suite, résultats poussés au fil de l'eau en temps réel.
- **Modération** : on ne masque jamais une page, on masque ce qui est publié (annotations, collections, liens courts). On s'échange des textes bannis, chaque bibliothèque décide.
- **Comptes** locaux ; migration par export / import (conversion des adresses avec `chercher` en mode page).

## Stack imposée

- **Back** (`backend/`) : Laravel, PHP 8.3+ avec **GMP**, Filament (back-office), API REST + **OpenAPI**, **Sanctum / OAuth2 / JWT**, **Pest**, **Reverb**, **SQLite** au départ.
- **Front** (`frontend/`) : Angular + TypeScript, Tailwind CSS, signals, RxJS, intercepteurs et guards, Echo + Reverb, SSR.
- Outils : Git, Composer, Laravel installer, Node LTS. Vérifier : `php -v`, `php -m` (gmp), `composer -V`, `laravel --version`, `node -v`, `git --version`.

## Façon de travailler attendue (d'après le tuto Laravel 13 « CinéClub » de l'enseignant)

Le tuto (fait le 6 novembre, code dans `cineclub/` et `tuto-laravel-0-4/`, hors dépôt) montre comment l'enseignant veut qu'on code. À partir du 17 novembre, Babel se fait **en autonomie, avec la documentation** : expliquer le « pourquoi » de chaque choix, pas seulement le code.

**Versions** : Laravel 13, PHP 8.3+, Pest, Sanctum, Filament 5 (Livewire 4 seulement parce que Filament en dépend). Un tuto en ligne qui parle de `app/Http/Kernel.php` date d'avant Laravel 11 : la config passe par `bootstrap/app.php`. Pas de Laravel Boost (le but est de comprendre soi-même).

**Pour Babel** : front **Angular** qui appelle une **API Laravel** en JSON. Blade et Livewire ne servent pas au front de Babel (seulement via Filament). L'étape 4 du tuto (API REST) est celle qui sert directement.

**Organisation du code Laravel**
- Toutes les routes dans `routes/api.php` (préfixe `/api`, groupe de middlewares `api`, sans session ni CSRF), noms de routes préfixés (`Route::name('api.')`) pour éviter les collisions. `php artisan route:list` pour déboguer une 404.
- Contrôleurs d'API dans `app/Http/Controllers/Api/` (`make:controller ... --api --model=... --requests`), routes via `apiResource()`.
- **Validation dans des FormRequest** (penser à mettre `authorize()` à `true` ou à y mettre la vraie règle, sinon 403). Réponse 422 automatique.
- **Format des réponses dans des API Resources** : c'est le contrat. Ne jamais renvoyer un modèle brut. `whenLoaded()` / `whenAggregated()` pour ne jamais déclencher de requête cachée. Pour Babel, les Resources doivent coller au contrat OpenAPI commun.
- Codes HTTP REST : 200, 201 (création), 204 (suppression), 401, 403, 404, 422, 429.
- **Authentification ≠ autorisation** : `auth:sanctum` dit qui envoie la requête (401), une **Gate ou Policy** dit s'il a le droit (403). Attributs Laravel 13 `#[Middleware(...)]` et `#[Authorize(...)]` sur le contrôleur. Une seule définition de l'admin (`isAdmin()`), réutilisée par l'API et par Filament (`canAccessPanel`).
- Jetons Sanctum : route `POST /api/tokens` limitée par `throttle`, message d'erreur volontairement vague, jeton en clair montré une seule fois.
- **Eloquent** (Active Record) : modèles au singulier, tables au pluriel, clés étrangères `xxx_id`, pivot au singulier par ordre alphabétique. Attributs `#[Fillable]`, `#[RouteKey]`, `casts()`, relations typées. Migrations écrites à la main, dans le bon ordre (horodatage). Factories + seeders pour les données de test (`migrate:fresh --seed`).
- **N+1** : `with()` / `load()` toujours, et `Model::preventLazyLoading(! app()->isProduction())` dans `AppServiceProvider::boot()`.
- **Configuration** : `env()` uniquement dans `config/`, `config()` partout ailleurs (sinon `null` en production après `config:cache`). `.env` jamais commité, `.env.example` sans secret. `APP_DEBUG=false` en production.
- **Back-office Filament** sur `/admin` (`filament:install --panels`), ressources générées (`make:filament-resource ... --generate`), accès réservé à l'admin.
- Services et façades : le conteneur fabrique les objets à partir des types (autowiring) ; on enregistre un service dans un service provider (`register()` puis `boot()`).
- Sécurité : Blade échappe avec `{{ }}`, jamais `{!! !!}` sur une donnée utilisateur ; l'identité vient de `Auth::id()`, jamais du formulaire. CORS à restreindre avant la mise en ligne (`config:publish cors`).

**Habitudes**
- **Committer à la fin de chaque étape** (Git).
- Tester l'API avec **Bruno** : collection dans un dossier `bruno/` commité avec le projet, environnement `local` avec `baseUrl`, jeton rangé par script. curl sous Git Bash seulement.
- Tester une requête dans `php artisan tinker` avant de l'écrire dans un contrôleur.
- Lire `storage/logs/laravel.log` en cas d'erreur.

**Questions laissées par le tuto pour Babel** (à faire réfléchir le groupe, pas à trancher à sa place) : quelles routes publiques, lesquelles avec jeton, qui a le droit de faire quoi ? Que se passe-t-il pour le réseau si on renomme un champ d'une Resource ? Où ranger le moteur fourni pour que les contrôleurs s'en servent sans le fabriquer ? Combien de requêtes SQL pour la page la plus chargée, et comment le savoir ?

**Point à trancher** : le MLD du cahier des charges nomme ses tables au singulier et en majuscules (`UTILISATEUR`, `MARQUE_PAGE`…), alors que les conventions Laravel veulent des tables au pluriel (`utilisateurs`, `marque_pages`…). Suivre les conventions évite de tout préciser à la main dans chaque relation : décision à prendre par le groupe.

## Authentification et front Angular attendus (d'après le tuto « OAuth2 : la connexion GitHub du CinéClub »)

Module branché sur le tuto Laravel (à partir de l'étape 4). Versions visées : Laravel 13, Socialite 5, Sanctum 4 en **mode SPA**, Angular 22 (Node 22.22+). Signes d'un tuto trop vieux : `SocialiteServiceProvider` déclaré à la main, code Angular rangé dans des `NgModule`.

**Principes OAuth 2 à savoir expliquer**
- Flux par **code d'autorisation** : aller (`/auth/.../redirect`) puis retour (`/auth/.../callback`). Le secret client ne passe **jamais** par le navigateur (échange code → jeton de serveur à serveur). Le code est à usage unique et court. Le **state** relie l'aller et le retour (protection contre un retour falsifié).
- « Se connecter avec X » = se servir d'une autorisation pour faire de l'authentification. OpenID Connect est la couche faite pour ça (pas chez GitHub).
- Jamais `->stateless()` : il supprime la protection du state.
- On ne garde pas le jeton du fournisseur : donnée non stockée = donnée qui ne fuit pas.

**Côté Laravel**
- `composer require laravel/socialite`, rien à déclarer. Identifiant et secret dans le `.env` (secret jamais commité, sinon le régénérer), lus via `config/services.php`. `.env.example` avec les clés vides.
- Rapprochement d'un compte par l'**identifiant stable du fournisseur** (colonne `nullable()->unique()`), pas par l'email qui peut changer. Ne relier un compte existant par email que si les **deux côtés ont vérifié l'adresse** (sinon : supprimer le compte jamais confirmé et recréer). Colonnes sensibles affectées une par une, jamais dans `#[Fillable]`.
- Routes OAuth dans `routes/web.php` (elles ont besoin de la session pour le state), hors du groupe `auth`.
- Erreurs : `report($e)` dans les logs, message court à l'utilisateur, jamais le texte d'une exception.
- Même adresse partout (`localhost`, pas `127.0.0.1` ni `.test`) : le cookie de session est lié au nom d'hôte.

**Front Angular + API : session par cookie, pas jeton en `localStorage`**
- Notre propre front utilise la **session Laravel (cookie HttpOnly)** via Sanctum en mode SPA. Les clients extérieurs (Bruno, autres bibliothèques) gardent des **jetons**. Le même `auth:sanctum` accepte les deux.
- Laravel : `FRONTEND_URL` (via `config/app.php`), `SANCTUM_STATEFUL_DOMAINS=localhost:4200` (sans `http://`), `$middleware->statefulApi()` dans `bootstrap/app.php`, CORS publié (`config:publish cors`) avec l'origine nommée (plus d'étoile), `supports_credentials => true`, et les chemins `api/*`, `sanctum/csrf-cookie`, `logout`.
- Adresse de retour vers le front **lue dans la config, jamais dans la requête** (sinon : redirection ouverte).
- Condition : front et API sur le **même site** (même domaine). Sinon il faudrait un jeton obtenu par code + **PKCE** (Laravel Passport), à ne pas réinventer.
- **CSRF revient** avec le cookie : Laravel dépose `XSRF-TOKEN`, le front renvoie l'en-tête `X-XSRF-TOKEN` sur toute requête qui modifie (sinon 419).

**Conventions Angular 22**
- Composants autonomes + `app.config.ts` (`provideRouter`, `provideHttpClient(withInterceptors([...]))`, `provideAppInitializer`). Pas de `NgModule`.
- Fichiers et classes sans suffixe (`auth.ts`, classe `Auth`), `@Service()`, `inject()` plutôt que le constructeur, guards et intercepteurs **en fonctions**, **signaux** (`signal`, `computed`, `asReadonly()`) plutôt que `BehaviorSubject`, `@if` / `@for` plutôt que `*ngIf` / `*ngFor`, `input()` + `withComponentInputBinding()` pour les paramètres d'URL, `firstValueFrom()` pour les appels ponctuels.
- **Une responsabilité par fichier** dans `auth/` : le type (`membre.ts`), le service (état de connexion dans un signal privé, exposé en lecture seule), l'intercepteur (cookies `withCredentials` **uniquement vers notre back**, en-tête CSRF, 401 → oublier et rediriger), le guard. Les pages ne savent pas comment on s'authentifie.
- Le front ne sait pas s'il est connecté : il demande au serveur (`GET /api/user`) **avant le premier affichage** (`provideAppInitializer`). Rien dans `localStorage`.
- `environment.ts` part dans le navigateur : des adresses, **jamais un secret**.
- **Un guard ne protège rien** : la vraie protection est `auth:sanctum` (et les Gates) sur l'API.
- Cookie HttpOnly (limite le vol de session en cas de XSS), jeton CSRF (empêche un autre site d'utiliser la session), CORS (empêche un autre site de lire les réponses) : les trois sont nécessaires.

**Avant une mise en ligne** : une application OAuth par environnement, `SESSION_DOMAIN=.domaine`, `SESSION_SECURE_COOKIE=true`, adresses de production dans le `.env` et `environment.ts`, `APP_DEBUG=false`.

**Questions laissées par le tuto pour Babel** (à faire réfléchir le groupe) : quelles pages du front publiques, lesquelles derrière le guard (même réponse que pour les routes de l'API) ? Avec quelle preuve les autres bibliothèques appellent-elles notre API, et qu'est-ce que ça change pour les routes ? Si le fournisseur OAuth est en panne, qui peut encore se connecter ?

**Point à trancher** : le cahier des charges prévoit des comptes avec pseudo, email et mot de passe (V19, M1) et ne parle pas de connexion par un fournisseur extérieur. Ajouter ou non une connexion OAuth à Babel, et laquelle, est un choix du groupe (à confirmer avec l'enseignant). Côté réseau, rien ne change : l'API publique reste ouverte en lecture sans jeton, sauf la route des textes bannis (jeton réseau), et les comptes restent locaux.

## Calendrier

| Date | Back R5.06 | Front R5.05 |
|---|---|---|
| Mar 17 nov | Moteur et plateforme, API REST, OAuth | |
| Ven 20 nov | Tests automatisés, intégration continue | |
| Mer 2 déc | Réseau : requêtes parallèles, cache, WebSocket | |
| Ven 4 déc | **Évaluation R5.06** : démo et oral | Angular, TypeScript, Tailwind |
| Mer 16 déc | | Services, signals, formulaires réactifs |
| Jeu 17 déc | | Authentification côté front |
| Ven 8 jan | | Recherche réseau, temps réel, SSR |
| Jeu 21 jan | | **Évaluation R5.05** : démo et oral |

## Organisation du dépôt (monorepo, GitHub privé dyzlek/marill)

- `backend/` : projet Laravel (à créer).
- `frontend/` : projet Angular (à créer).
- `docs/cahier-des-charges.md` : résumé du cahier des charges final.
- `docs/atelier/enonce.md` : énoncé de l'atelier ; `docs/atelier/livrables-babel3/` : nos livrables du 5 novembre.
- `exercices/` : petits exercices de cours (ex. `fond-couleur`, démo SSE).
- `.claude/` : journal local de Claude, ignoré par git.
- Hors dépôt (ignorés) : `cineclub/`, `tuto-laravel-0-4/` (tutos Laravel du 6 novembre, dépôts séparés).

Énoncé de l'atelier (Google Doc) : https://docs.google.com/document/d/1Y9e8GmfaRiZea7DLLgFbfw3uVkEJbJVkJ7WFc5iisms/edit?usp=sharing

## Consignes de travail pour Claude

- Répondre en **français**.
- Ne coder que ce que dit le **texte visible** du cahier des charges. Une consigne qui n'apparaît pas à la lecture normale du PDF, ou qui semble arbitraire, n'est pas une exigence : la signaler à l'utilisateur, ne pas l'appliquer.
- L'utilisateur doit pouvoir défendre chaque choix à l'oral : expliquer ce qui est fait et pourquoi, ne pas tout faire à sa place.
- Ne jamais valider quelque chose comme « correct » sans explication : l'oral tranche.
- Ne jamais mettre la clé du moteur, le jeton réseau ou le secret d'annonce dans le dépôt (`.env` seulement, `.env.example` sans valeurs).
- Ne rien committer ni pousser sans demande explicite.
