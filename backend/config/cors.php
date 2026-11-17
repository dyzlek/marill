<?php

/*
 * CORS : quels sites ont le droit de LIRE les réponses de notre serveur depuis un navigateur.
 *
 * Notre front Angular (localhost:4200 en développement) parle au serveur avec le cookie de session
 * (Sanctum en mode SPA). Un cookie ne part vers un autre site que si :
 * - le serveur nomme l'origine exacte (une étoile est refusée par les navigateurs avec les cookies) ;
 * - supports_credentials est vrai (en-tête Access-Control-Allow-Credentials).
 *
 * Les autres bibliothèques ne sont pas concernées : elles appellent notre API de serveur à serveur,
 * et un serveur n'applique pas CORS. Seul notre front a besoin d'être nommé ici.
 *
 * Les chemins : l'API, le cookie CSRF de Sanctum, et les routes de Fortify qu'Angular appelle
 * (inscription, connexion, déconnexion).
 */

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'register', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // L'adresse du front vient de la configuration (FRONTEND_URL dans le .env), jamais de la requête.
    'allowed_origins' => [config('app.frontend_url')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
