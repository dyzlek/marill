# backend : serveur de la bibliothèque (R5.06)

Laravel 13 (starter kit Livewire pour l'authentification, Pest), PHP 8.3+ avec l'extension **GMP**, SQLite. Moteur fourni : `but-mmi-beziers/babel-moteur`. À venir : API REST décrite en OpenAPI, Sanctum, Filament (back-office), Reverb.

Créé le 17 novembre 2026 avec la commande du tuto :

```bash
laravel new backend --livewire --pest --no-boost --npm --database=sqlite
```

## Installer le projet après un clone

```bash
cd backend
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Puis la clé du moteur :

- **Pour développer seul sur sa machine** : `php artisan babel:cle` crée une clé à soi (les adresses donneront d'autres textes que chez les camarades, c'est normal).
- **Pour la bibliothèque du groupe** (démo, mise en ligne) : tout le monde utilise **la même** ligne `BABEL_KEY=…`, transmise hors de Git (gestionnaire de mots de passe), jamais committée. Ne jamais relancer `babel:cle --force` dessus.

Vérifier :

```bash
php artisan babel:verifier   # doit finir par « Tout est prêt »
php artisan test
composer run dev             # http://localhost:8000
```

## Ce que ce dossier devra contenir

D'après le [cahier des charges](../docs/cahier-des-charges.md) :

- le moteur, injecté (`Babel\Moteur\Moteur`), jamais construit à la main ;
- les trois portes : API interne (application web), API publique (réseau), back-office Filament (admin) ;
- `normaliser` (F3), `valider` (F5), `auHasard` (F7) ;
- les 14 tables du modèle logique ;
- les tâches de fond (recherche réseau, contrôle des pairs, textes bannis, conversion des imports) ;
- les tests Pest.
