# 05 · Lire une page (issue #10)

## Ce qui est fait

`GET /api/pages/{adresse}` (publique) → `{"data": {"adresse": "…", "texte": "…3 200 caractères…"}}`.

- `Api\PageController@show` : **F5 d'abord** (`validerPage`), puis `$moteur->lire($adresse)` (F1).
- `PageResource` met en forme l'objet `App\Babel\Page` (adresse + texte).
- Depuis l'issue #30, chaque lecture ajoute 1 au compteur du jour (voir [26](26-statistiques.md)).
- Depuis l'issue #26, la route est sous la limite de débit publique (voir [22](22-limite-de-debit.md)).

## Pourquoi

- **Notre validateur décide avant le moteur** : c'est la règle commune (F5), et elle donne nos messages en français, au même format.
- **Le moteur est injecté**, jamais `new Moteur()` : il a besoin de la clé, et seul le service provider du paquet sait la lire dans la configuration.
- **Rien en base** (règle 1) : le texte se recalcule à partir de l'adresse en 1 à 2 ms.
- Une adresse ne contient pas de `/` : le paramètre de route par défaut (`[^/]+`) suffit.
- **Jamais un modèle brut en réponse** : la Resource est le contrat. Renommer un champ casserait le réseau, donc on le change à un seul endroit, en connaissance de cause.

## Tests

`tests/Feature/Api/LirePageTest.php` : page valide, livre refusé, 422 / 404. Bruno : dossier `Pages`.
