# 04 · Le format des erreurs (issue #7)

## Ce qui est fait

Toutes nos erreurs d'API ont la même forme :

```json
{ "erreurs": [ { "code": "ERR_OUT_OF_BOUNDS", "champ": "mur", "message": "Le mur va de 1 à 4." } ] }
```

- `App\Exceptions\ErreurApi` : une exception « rendable ». Un contrôleur fait `throw ErreurApi::depuisValidation($resultat)` ou `throw new ErreurApi([...])`, et Laravel appelle `render()` tout seul.
- Le **statut HTTP** se déduit des codes (`STATUTS`) : 422 dès qu'il y a une erreur de forme, 404 s'il ne reste que `ERR_OUT_OF_BOUNDS`, 429 pour le quota, 409 pour un doublon, 410 pour un lien expiré.
- `bootstrap/app.php` traduit au même endroit les erreurs du moteur (`ErreurMoteur::versTableau()`) et le dépassement de quota (`ThrottleRequestsException` → 429 `ERR_TOO_MANY_REQUESTS`, en gardant l'en-tête `Retry-After`).

## Pourquoi

- **Dix bibliothèques, une seule forme d'erreur** : une bibliothèque qui interroge la nôtre sait toujours lire la réponse.
- **Le message ne recopie jamais l'entrée** : pas de texte de l'utilisateur renvoyé tel quel (risque d'injection dans une page qui l'afficherait).
- `CleInvalide` (clé du moteur absente ou mal formée) **n'est pas traduite** : c'est une erreur d'installation, elle doit faire un 500 et finir dans les logs. Même chose pour `ERR_INVALID_ALPHABET`, qui voudrait dire un bug chez nous (texte non normalisé envoyé au moteur).
- Les erreurs du client ne vont **pas dans les logs** (`dontReport`) : sinon le journal se remplit de fautes de frappe.

## Reste à faire

Le nom de la clé `erreurs` et les codes de nos propres cas (`ERR_REGISTRATION_CLOSED`, `ERR_ALREADY_BOOKMARKED`…) attendent le contrat commun. Les erreurs de validation des FormRequest gardent pour l'instant le format Laravel (`errors`). Voir [27](27-contrat-api-commun.md).

## Tests

`tests/Feature/ErreursApiTest.php` (forme commune, statuts, rien dans les logs pour une erreur du client).
