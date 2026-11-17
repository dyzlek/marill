<?php

/*
| Identité de notre bibliothèque (B6), publiée sur GET /api/identite.
|
| PROVISOIRE : le cahier des charges range le nom et la description sur la ligne locale de la table
| BIBLIOTHEQUE (est_locale = vrai), modifiable par l'administrateur (A8). Tant que cette table n'existe pas
| (milestone 2, nom des tables à trancher dans l'issue #1), on les lit ici. Seule App\Babel\IdentiteLocale
| devra changer.
|
| Rien de secret ici : la clé du moteur reste dans BABEL_KEY, seule son empreinte est publiée.
*/

return [
    // Identifiant stable sur le réseau (40 caractères au plus) : à remplacer par celui remis par la plateforme.
    'identifiant' => env('BIBLIOTHEQUE_IDENTIFIANT', 'babel-3'),

    'nom' => env('BIBLIOTHEQUE_NOM', env('APP_NAME', 'Bibliothèque de Babel')),

    'description' => env('BIBLIOTHEQUE_DESCRIPTION'),

    // Règle 11 : un lien court créé sans compte expire obligatoirement. Le CDC ne fixe pas la durée :
    // choix du groupe (issue #21), 30 jours.
    'jours_avant_expiration_lien_visiteur' => (int) env('LIEN_VISITEUR_JOURS', 30),
];
