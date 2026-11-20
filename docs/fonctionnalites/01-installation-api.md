# 01 · Installer l'API et Sanctum (issue #6)

## Ce qui est fait

`php artisan install:api` a ajouté :

- `routes/api.php`, chargé par `bootstrap/app.php` avec le **préfixe `/api`** et le groupe de middlewares `api` (sans session ni CSRF par défaut) ;
- **Sanctum 4** et la table `personal_access_tokens` ;
- le trait `HasApiTokens` sur `User` (méthode `createToken()`).

Toutes nos routes sont dans un groupe `Route::name('api.')` : leurs noms commencent par `api.` (`api.pages.show`…).

## Pourquoi

- **Une API JSON, pas des pages Blade** : le front est en Angular, et les neuf autres bibliothèques appellent la même API. Blade ne sert qu'au back-office (Filament).
- **Préfixer les noms de routes** : `routes/web.php` a aussi des routes nommées (`home`, `dashboard`…). Sans préfixe, deux routes du même nom s'écraseraient sans prévenir.
- **Sanctum plutôt que Passport** : Sanctum donne deux choses dont on a besoin, la session par cookie pour notre front et des jetons simples pour Bruno ou les scripts. Passport (OAuth 2 complet) servirait si des applications tierces devaient demander l'accès au compte d'un membre. Ce n'est pas notre cas.
- Avant Laravel 11, ces réglages étaient dans `app/Http/Kernel.php`. Ils sont maintenant dans `bootstrap/app.php`.

## Pour l'oral

- *Où voir toutes les routes ?* `php artisan route:list --path=api`. C'est la première chose à faire devant une 404.
- *Pourquoi pas de CSRF sur l'API ?* Un client avec un jeton ne s'appuie pas sur un cookie : un autre site ne peut pas l'utiliser à sa place. Le CSRF revient pour notre front, qui utilise la session (voir [11](11-comptes.md)).
