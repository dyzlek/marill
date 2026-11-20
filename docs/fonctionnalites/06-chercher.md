# 06 · Chercher un texte (issue #11)

## Ce qui est fait

`POST /api/recherche` (publique), corps `{"texte": "…", "mode": "exact" | "bruit"}` → `{"data": {"texte": "<normalisé>", "mode": "exact", "adresse": "…"}}`.

- `ChercherTexteRequest` : texte requis, mode `exact` ou `bruit`. Les erreurs sont traduites en `ERR_EMPTY_QUERY` / `ERR_UNKNOWN_MODE`.
- `Api\RechercheController` (invocable) : **normaliser** (F3), puis `$moteur->chercher($texte, $mode)` (F2).
- `RechercheResource` : texte normalisé, mode, adresse. **Pas de position.**
- Depuis l'issue #28, la recherche d'un membre connecté est gardée dans son historique ([24](24-historique-des-recherches.md)) ; celle d'un visiteur ne laisse qu'un compteur ([26](26-statistiques.md)).

## Pourquoi

- **POST et pas GET** : un texte de 3 200 caractères accentués, encodé dans l'URL, dépasse la longueur que beaucoup de serveurs acceptent. Rien n'est créé, donc la réponse est 200 et non 201.
- **On renvoie le texte normalisé** : le front sait ce qui a vraiment été cherché, et il en a besoin pour surligner le passage.
- **Pas de position** (décision du CDC) : la surbrillance est un calcul du navigateur (F4 `localiser`). Le serveur ne renvoie que l'adresse.
- Le mode `page` n'est pas exposé ici : il sert à la migration d'un compte (conversion d'adresses, [25](25-export-import.md)).
- Contrôleur **invocable** (`__invoke`) : une recherche n'est pas une ressource qu'on liste ou qu'on modifie.

## Tests

`tests/Feature/Api/ChercherTexteTest.php`. Bruno : dossier `Recherche`.
