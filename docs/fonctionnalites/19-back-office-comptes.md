# 19 · Back-office : les comptes (issue #23 : A1, A2, A3)

## Ce qui est fait

**Filament 5** est installé (`composer require filament/filament`, `php artisan filament:install --panels`). Le back-office est sur **`/admin`** (`app/Providers/Filament/AdminPanelProvider.php`).

- **Accès** : `User` implémente `FilamentUser::canAccessPanel()` → `isAdmin() && ! estSuspendu()`. Un membre reçoit 403, un visiteur est renvoyé vers `/admin/login`.
- `User` implémente aussi `HasName` (`getFilamentName()` renvoie le pseudo) : Filament cherche sinon une colonne `name`, que nous n'avons pas.
- Ressource **Comptes** (`app/Filament/Resources/Users/`), une seule page, la liste :
  - **A1** : colonnes pseudo, email, rôle, suspendu le, dernière connexion, créé le ; recherche par pseudo ou email ; filtres **rôle** et **suspendu** ;
  - **A2** : actions **Suspendre** (avec confirmation), **Rétablir**, **Supprimer** ;
  - **A3** : action **Promouvoir admin**.
- Les actions appellent des méthodes du modèle : `User::suspendre()`, `retablir()`, `promouvoir()`.

## Pourquoi

- **Une seule définition de l'admin** (`isAdmin()`), réutilisée par l'API et par Filament. Si la règle change, elle change partout.
- **Suspendre coupe tout de suite** : on date `suspendu_le` (les prochaines connexions sont refusées par `VerifierIdentifiants` et par la connexion GitHub), **et** on supprime ses jetons et ses sessions en base. Sinon un membre déjà connecté continuerait comme si de rien n'était.
- **Pas de création ni de modification de compte** par l'admin : le pseudo, l'email et le mot de passe sont l'affaire du membre (M9). L'admin gère l'accès, pas le contenu du compte.
- **Un admin n'agit pas sur lui-même** (Suspendre et Supprimer sont cachés sur sa propre ligne) : la bibliothèque ne doit pas se retrouver sans admin par un clic malheureux.
- **Supprimer = règle 19**, comme M10 : les cascades effacent ses données, ses signalements restent sans auteur.
- **La logique dans le modèle, pas dans l'écran** : elle se teste sans Filament, et un autre écran (ou une commande artisan) pourra la réutiliser.
- `role` n'est pas dans `#[Fillable]` : `promouvoir()` l'affecte à la main (`forceFill`).

## À savoir

L'avatar par défaut de Filament (ui-avatars.com) envoyait le pseudo de l'admin à un service extérieur. Il est remplacé par un avatar dessiné ici ([31](31-entretien.md)).

## Tests

`tests/Feature/BackOfficeTest.php`, bloc « accès » et « A1 · A2 · A3 » : méthodes du modèle, et une fois à travers l'écran (`Livewire::test(ListUsers::class)->callAction(TestAction::make('suspendre')->table($membre))`).
