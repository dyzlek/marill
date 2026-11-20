# 25 · Export et import du compte (issue #29 : M11, M12, règle 20)

## Ce qui est fait

### M11 : exporter

`GET /api/user/export` → un **fichier JSON à télécharger** (`Content-Disposition: attachment`, `Cache-Control: no-store`), fabriqué par `App\Babel\ExportCompte` :

```json
{
  "format": "babel-export/1",
  "bibliotheque": "babel-3",
  "exporte_le": "…",
  "profil": { "pseudo", "email", "cree_le" },
  "marque_pages": [{ "adresse", "bibliotheque", "titre", "cree_le" }],
  "collections": [{ "nom", "description", "est_publique", "pages": [{ "position", "adresse", "bibliotheque" }] }],
  "annotations": [{ "adresse", "bibliotheque", "contenu", "est_publique", "cree_le" }],
  "votes": [{ "adresse", "bibliotheque", "valeur" }],
  "recherches": [{ "texte", "mode", "portee", "cree_le" }],
  "liens_courts": [{ "slug", "cible", "passage", "visites", "cree_le" }]
}
```

**Jamais le mot de passe.** Chaque adresse a l'identifiant de sa bibliothèque.

### M12 : importer

`POST /api/user/import` avec le contenu du fichier en corps JSON → **201** et un rapport `{"marque_pages": n, "collections": n, "annotations": n, "doublons": n}`.

`App\Babel\ImportCompte` :

1. `ImporterCompteRequest` vérifie la **forme** du fichier (format connu, champs, longueurs, nombre de lignes) ;
2. **toutes** les adresses passent F5 **avant** d'écrire : une seule fausse, rien n'est importé, et l'erreur dit où (`"champ": "marque_pages.1.adresse"`) ;
3. tout est écrit dans **une transaction** ;
4. ne sont recréés **que** les marque-pages, les collections et les annotations ;
5. collections et annotations arrivent **privées** ;
6. deux marque-pages sur la même page sont **fusionnés** (compté dans `doublons`) ;
7. une adresse **garde sa bibliothèque d'origine**. Une bibliothèque que nous ne connaissons pas encore est ajoutée par son identifiant, état `inconnu`, URL vide.

## Pourquoi

- **Règle 20** : l'export est un fichier, sans table. L'import ne reprend que ce qui a du sens ailleurs : un vote ou un lien court appartient à la bibliothèque où il a été fait.
- **Privées à l'arrivée** (migration, étape 5) : le membre republie ce qu'il veut, et ce qu'il publie passe par **notre** modération.
- **Tout vérifier, puis tout écrire en transaction** : un import à moitié fait n'existe pas.
- **Le format porte une version** (`babel-export/1`) : l'import refuse ce qu'il ne connaît pas. Le vrai format fait partie du **contrat commun** (toutes les bibliothèques lisent le fichier des autres). Il faudra l'aligner ([27](27-contrat-api-commun.md)).
- **Le corps JSON plutôt qu'un envoi de fichier** : le front lit le fichier choisi et l'envoie. La FormRequest valide alors le contenu comme n'importe quelle requête.
- **Une bibliothèque inconnue est ajoutée par son identifiant** : l'identifiant est stable (règle 6). L'annuaire (#31) donnera son URL plus tard, sans rien changer aux adresses importées.
- **Les classes `ExportCompte` / `ImportCompte`** à part du contrôleur : elles se testent sans HTTP, et le contrôleur reste court.
- Les limites (5 000 marque-pages, 500 collections…) protègent le serveur d'un fichier énorme. **Choix du groupe.**

## Reste à faire

**La conversion des adresses** (migration, étape 4) : lire la page chez la bibliothèque d'origine, puis la retrouver ici avec `chercher(texte, 'page')`, en tâche de fond. Elle a besoin de la lecture chez un pair (issue #34, milestone 3). D'ici là, une page importée d'ailleurs reste une page d'ailleurs (M13), ce qui fonctionne déjà.

## Tests

`tests/Feature/Api/ExportImportTest.php` (8 tests, dont un aller-retour export → import chez un autre membre). Bruno : `Compte > Exporter mes données`, `Importer un export`.
