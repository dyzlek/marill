# backend : serveur de la bibliothèque (R5.06)

Laravel, PHP 8.3+ avec l'extension GMP, Filament (back-office), API REST décrite en OpenAPI, Sanctum, Pest, Reverb, SQLite au départ.

Le projet Laravel sera créé ici quand le moteur fourni par l'enseignant sera disponible (cours du 17 novembre).

Ce que ce dossier devra contenir, d'après le [cahier des charges](../docs/cahier-des-charges.md) :

- le moteur (composant fourni) appelé directement, avec sa clé lue dans la configuration (`.env`, jamais commité) ;
- les trois portes : API interne (application web), API publique (réseau), back-office Filament (admin) ;
- `normaliser` (F3), `valider` (F5), `auHasard` (F7) ;
- les 14 tables du modèle logique ;
- les tâches de fond (recherche réseau, contrôle des pairs, textes bannis, conversion des imports) ;
- les tests Pest.
