# 30 · Documenter l'API en OpenAPI (issue #38)

## Ce qui est fait

- **Scramble** (`dedoc/scramble`) est installé. Il **lit le code** (routes, FormRequest, Resources, codes de retour) et en déduit la documentation OpenAPI 3.1, sans annotations à écrire à la main.
- En local, la documentation interactive est sur **`http://localhost:8000/docs/api`**.
- Le fichier est exporté et commité : **[`docs/openapi.json`](../openapi.json)** (28 chemins), par `php artisan scramble:export` (`export_path` dans `config/scramble.php`).
- Titre et description réglés dans `config/scramble.php` : la description prévient que les chemins sont provisoires.

## Pourquoi

- **Le CDC impose OpenAPI** (stack back : « API REST + OpenAPI »).
- **Générée depuis le code, elle ne ment pas** : une doc écrite à la main finit toujours par dire autre chose que le code. Ici, changer une FormRequest ou une Resource change la doc.
- **C'est pour ça qu'on a tout mis dans des FormRequest et des Resources** : Scramble y trouve les champs attendus et la forme des réponses.
- **Le fichier exporté** se compare au contrat commun quand il sortira, et les autres groupes peuvent le lire sans lancer notre serveur.
- `/docs/api` n'est ouvert **qu'en local** (réglage par défaut de Scramble) : en production, on ne publie pas un plan détaillé du serveur sans l'avoir décidé.

## À refaire après chaque changement d'API

```bash
php artisan scramble:export
```

## Reste à faire

- Comparer avec le contrat OpenAPI commun dès sa publication (#5, voir [27](27-contrat-api-commun.md)).
- Ajouter des descriptions là où Scramble devine mal (par exemple les codes d'erreur `ERR_…` de chaque route).
