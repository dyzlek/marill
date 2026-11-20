# 07 · L'identité de la bibliothèque (issue #12, mise à jour #25)

## Ce qui est fait

`GET /api/identite` (publique) → `{"data": {"identifiant", "nom", "description", "empreinte"}}`.

- `App\Babel\IdentiteLocale::lire()` fabrique l'objet `Identite` ; `IdentiteResource` le met en forme.
- **Depuis l'issue #25** : identifiant, nom et description viennent de la **ligne locale de la table `bibliotheques`** (`est_locale = true`), que l'administrateur modifie dans le back-office ([21](21-configurer-la-bibliotheque.md)).
- `config/bibliotheque.php` ne sert plus qu'à **créer cette ligne la première fois** (`Bibliotheque::locale()`).
- L'**empreinte** (F8) est demandée au moteur à chaque fois : jamais recopiée en base.

## Pourquoi

- **B6** : les autres bibliothèques et la plateforme savent à qui elles parlent. L'empreinte leur dit si notre clé a changé (A9 chez elles).
- **On publie l'empreinte, jamais la clé.** On ne peut pas retrouver la clé à partir de l'empreinte, mais l'empreinte change dès que la clé change. Un test vérifie que la clé n'apparaît dans aucune réponse.
- **Règle 22** : le nom et la description sont sur la ligne locale de `BIBLIOTHEQUE`, pas dans les réglages clé/valeur. Notre bibliothèque est « une ligne comme les autres » (règle 5).
- **L'identifiant ne change pas** (règle 6) : il est remis par la plateforme et n'est pas modifiable dans le back-office.
- Un seul endroit sait d'où vient l'identité (`IdentiteLocale`). Passer de la config à la base n'a changé que cette classe.

## Tests

`tests/Feature/Api/IdentiteTest.php`, plus `BackOfficeTest` (le nom changé dans le back-office est bien publié).
