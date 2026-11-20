# 24 · Historique des recherches (issue #28 : M8, règle 12)

## Ce qui est fait

- `POST /api/recherche` reste public. Mais si la requête vient d'un **membre connecté** (session ou jeton), le contrôleur enregistre une ligne `recherches` : **texte normalisé**, mode, portée `locale`.
- `GET /api/recherches` : mon historique, la plus récente d'abord, par 50 (`HistoriqueRechercheController`, `RecherchePasseeResource`).
- `DELETE /api/recherches/{id}` : effacer une recherche de mon historique. Celle d'un autre donne 404.
- Un visiteur (ou une autre bibliothèque) ne laisse **qu'un compteur** : `activites_jour.recherches` ([26](26-statistiques.md)).

## Pourquoi

- **Règle 12** : on ne garde que les recherches des membres. Pour un visiteur, ce serait des données personnelles sans raison.
- **L'adresse trouvée n'est pas stockée** : la même recherche la redonne (le moteur est déterministe). Le front relance la recherche quand le membre clique.
- **Le texte normalisé**, pas le texte tapé : c'est lui qui donne l'adresse.
- **`auth('sanctum')->user()` sur une route publique** : la route n'exige pas de compte, mais on regarde quand même s'il y en a un. Même technique que pour l'auteur d'un lien court.
- **Supprimer une ligne** n'est pas demandé par M8. On l'ajoute parce que c'est une donnée personnelle : on peut la retirer sans supprimer tout son compte. Chercher **dans ses propres recherches** (`$membre->recherches()->findOrFail()`) rend une Policy inutile : celle d'un autre est introuvable, donc 404.
- La portée `reseau` sera écrite par la recherche sur tout le réseau (V15, issue #33).

## Tests

`tests/Feature/Api/HistoriqueEtStatistiquesTest.php`, bloc « M8 ». Bruno : `Compte > Mes recherches`.
