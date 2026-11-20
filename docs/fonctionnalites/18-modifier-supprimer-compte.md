# 18 · Modifier et supprimer son compte (issue #17 : M9, M10, règle 19)

## Ce qui est fait

Trois routes réservées au membre connecté, dans `Api\CompteController` :

| Route | Rôle | Réponse |
|---|---|---|
| `PATCH /api/user` | M9 : changer de pseudo et/ou d'email | 200 + `UtilisateurResource` |
| `PUT /api/user/password` | M9 : changer de mot de passe | 204 |
| `DELETE /api/user` | M10 : supprimer son compte | 204 |

- `ModifierCompteRequest` : **les mêmes règles qu'à l'inscription** (`ProfileValidationRules`, partagées avec Fortify), en ignorant son propre compte pour l'unicité, et `sometimes` : on peut n'envoyer que le pseudo.
- `ChangerMotDePasseRequest` : l'**ancien mot de passe** (`current_password:sanctum`) et le nouveau, confirmé. Un compte GitHub sans mot de passe n'a pas d'ancien à donner : il en choisit un.
- Après un changement de mot de passe : **tous les jetons et les autres sessions sont coupés**, la session en cours est régénérée.
- `SupprimerCompteRequest` : confirmer avec son mot de passe, ou en retapant son pseudo pour un compte GitHub sans mot de passe.
- Suppression : on coupe la session et les jetons, puis `$membre->delete()`. Les **cascades** de la base effacent marque-pages, collections, annotations, votes, recherches et liens courts. Les signalements restent, **sans auteur** (`nullOnDelete`).

## Pourquoi

- **Aucun id dans l'URL** : c'est toujours *mon* compte, l'identité vient de la session ou du jeton (`$request->user()`). Il n'y a personne d'autre à viser, donc pas besoin de Policy.
- **`role` n'est pas modifiable par ce formulaire** : il n'est ni dans les règles, ni dans `#[Fillable]`. Un test vérifie qu'envoyer `role: admin` ne change rien.
- **Redemander l'ancien mot de passe** : quelqu'un qui trouve une session ouverte ne peut pas s'approprier le compte.
- **Couper les autres sessions** : si le mot de passe a fui, l'intrus perd l'accès dès qu'on le change.
- **Confirmation avant de supprimer** : l'action est irréversible (RGPD : le membre doit pouvoir le faire, mais pas par accident).
- **Les signalements restent** (règle 19) : ils servent à la modération, ils ne sont pas « à » l'auteur.
- `current_password:sanctum` : la règle doit regarder la garde sanctum. Avec la garde web par défaut, un client à jeton serait toujours refusé.

## À savoir

Les écrans Blade du starter kit (`/settings/profile`…) existent encore pour la double authentification et les passkeys. Ils ne servent pas au front Angular.

## Tests

`tests/Feature/Api/CompteTest.php` (9 tests). Bruno : `Compte > Modifier mon pseudo`, `Changer de mot de passe`.
