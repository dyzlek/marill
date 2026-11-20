# 27 · Le contrat d'API commun (issue #5, en attente)

## Où on en est

Le contrat (OpenAPI commun aux dix bibliothèques : routes, forme des erreurs, format d'export) **n'est pas encore publié**. Rien n'est codé pour cette issue : on attend le document.

## Ce qui devra s'aligner dessus

Tout ce qui est marqué « provisoire » dans le code :

| Quoi | Où |
|---|---|
| Les chemins et méthodes des routes publiques (`/api/pages/{adresse}`, `/api/recherche`, `/api/identite`, `/api/hasard`, `/api/collections-publiques`, `/api/pages-populaires`, `/api/reseau/textes-bannis`…) | `routes/api.php` |
| Le nom des champs des réponses | les Resources dans `app/Http/Resources/` |
| La clé `erreurs` et les codes que nous avons inventés (`ERR_REGISTRATION_CLOSED`, `ERR_INVALID_CREDENTIALS`, `ERR_ALREADY_BOOKMARKED`, `ERR_LINK_*`, `ERR_ALREADY_REPORTED`, `ERR_OAUTH_*`, `ERR_INVALID_NETWORK_TOKEN`) | `App\Exceptions\ErreurApi` |
| Les erreurs de validation des FormRequest, encore au format Laravel (`errors`) | `bootstrap/app.php` (un `render` pour `ValidationException`) |
| Le format du fichier d'export | `App\Babel\ExportCompte`, `ImporterCompteRequest` |

## Pourquoi on a pu avancer sans lui

- **Les Resources sont le contrat** : changer un nom de champ, c'est une ligne dans une Resource, pas dans dix contrôleurs.
- **Une seule classe pour les erreurs** (`ErreurApi`) : changer `erreurs` en autre chose, c'est une ligne.
- **Les routes sont nommées** (`api.pages.show`…) : on change le chemin dans `routes/api.php`, le reste du code utilise le nom.

## À faire quand il sort

1. Le lire **sur le rendu**, pas par extraction de texte (voir la vigilance pièges dans `CLAUDE.md`).
2. Comparer route par route avec `php artisan route:list --path=api`.
3. Adapter les Resources et les codes, puis les tests (`composer test`).
4. Réexporter notre documentation OpenAPI (`php artisan scramble:export`, [30](30-openapi.md)) et la comparer au contrat.
