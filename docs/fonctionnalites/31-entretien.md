# 31 · Entretien : liens expirés, avatar local, tâches planifiées

## Nettoyer les liens courts expirés

- Commande **`php artisan liens-courts:nettoyer`** (`app/Console/Commands/NettoyerLiensCourts.php`) : efface les liens **de visiteurs** dont `expire_le` est passé.
- Lancée **chaque nuit** (`routes/console.php`).
- **Pourquoi** : un lien expiré répond 410 et ne mènera plus jamais nulle part (règle 11). Le garder ne sert à rien. Ses signalements partent avec lui (cascade). Les liens des membres n'expirent pas (`expire_le` nul) et ne sont jamais touchés.

## Avatar du back-office dessiné ici

- `App\Filament\AvatarInitiales` : un rond avec les initiales du pseudo, en SVG dans une URL `data:`, branché par `->defaultAvatarProvider()` dans `AdminPanelProvider`.
- **Pourquoi** : par défaut, Filament demande l'image à ui-avatars.com en lui envoyant le nom de l'administrateur. Un pseudo est une donnée personnelle : on ne l'envoie pas à un tiers pour un dessin.
- Le pseudo passe par `htmlspecialchars` : un membre ne peut pas injecter du SVG par son pseudo.

## Les tâches planifiées

| Tâche | Quand | Fiche |
|---|---|---|
| `ReverifierPublications` (textes bannis, étape 2) | chaque nuit, et à chaque texte appliqué | [28](28-textes-bannis.md) |
| `liens-courts:nettoyer` | chaque nuit | ici |

Pour qu'elles tournent :

- **le planificateur** : `php artisan schedule:run` chaque minute (une tâche cron en production ; `php artisan schedule:work` en local) ;
- **la file d'attente** : `php artisan queue:work` (`QUEUE_CONNECTION=database`).

`php artisan schedule:list` montre ce qui est prévu.

## Tests

`tests/Feature/ModerationTest.php` : la commande efface les liens de visiteurs expirés et garde les autres ; l'avatar est une image `data:` avec les initiales.
