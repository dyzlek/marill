# La Bibliothèque de Babel en réseau

Projet fil rouge du BUT MMI 3, parcours Développement web et dispositifs interactifs, IUT de Béziers. Groupe **Babel 3**.

## Le projet

Inspirée de la nouvelle de Borges, la bibliothèque contient **toutes les pages possibles** de 3 200 caractères, écrites avec 29 symboles (a à z, espace, virgule, point). Rien n'est stocké : un moteur calcule la page à partir de son adresse, et retrouve l'adresse d'une page qui contient un texte donné.

Chaque groupe code sa propre bibliothèque, avec sa propre clé : la même adresse n'affiche pas le même texte d'une bibliothèque à l'autre. Les dix bibliothèques se parlent par une **API publique commune**, pour chercher un texte sur tout le réseau, lire une page chez une autre bibliothèque ou partager les textes bannis.

## Stack

| Partie | Technologies | Module |
|---|---|---|
| Serveur | Laravel 13, PHP 8.3+ avec GMP, SQLite, Sanctum, Filament, Reverb, Pest | R5.06 |
| Application web | Angular, TypeScript, Tailwind CSS, Echo | R5.05 |
| Moteur (fourni) | [`but-mmi-beziers/babel-moteur`](https://github.com/BUT-MMI-Beziers/moteur-bibliotheque-babel) | |

## Où on en est

Le suivi détaillé est sur le [tableau du projet](https://github.com/users/dyzlek/projects/4), avec les [issues](https://github.com/dyzlek/marill/issues) rangées par [milestone](https://github.com/dyzlek/marill/milestones).

- [x] Cahier des charges final (15 novembre 2026)
- [x] Projet Laravel créé, moteur installé et vérifié
- [ ] API REST et authentification (Sanctum)
- [ ] Modèle de données : les 14 tables
- [ ] Fonctions du serveur : normaliser, valider, page au hasard
- [ ] Back-office Filament
- [ ] Réseau : recherche sur les pairs, temps réel
- [ ] Application web Angular

## Démarrer

Prérequis : PHP 8.3+ avec les extensions `gmp`, `intl` et `mbstring`, Composer, Node.js 22+.

```bash
git clone https://github.com/dyzlek/marill.git
cd marill/backend
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan babel:cle
php artisan babel:verifier
composer run dev
```

Le site répond sur http://localhost:8000. Le détail, dont le partage de la clé du moteur entre membres du groupe, est dans [`backend/README.md`](backend/README.md).

> La clé `BABEL_KEY` ne se commite jamais. Seule son empreinte se publie.

## Organisation du dépôt

| Dossier | Contenu |
|---|---|
| [`backend/`](backend/) | serveur Laravel : moteur, API interne et publique, back-office, tâches de fond |
| [`bruno/`](bruno/) | collection [Bruno](https://www.usebruno.com/) pour essayer l'API (environnement `local`) |
| [`frontend/`](frontend/) | application web Angular (à venir) |
| [`docs/cahier-des-charges.md`](docs/cahier-des-charges.md) | cahier des charges final : stories, fonctions du moteur, modèle de données, architecture |
| [`docs/atelier/`](docs/atelier/) | énoncé de l'atelier et livrables du groupe (5 novembre) |
| [`exercices/`](exercices/) | petits exercices de cours |
| [`CLAUDE.md`](CLAUDE.md) | contexte du projet et façon de travailler |

## Calendrier

| Date | Serveur (R5.06) | Application web (R5.05) |
|---|---|---|
| Mar 17 nov | Moteur et plateforme, API REST, OAuth | |
| Ven 20 nov | Tests automatisés, intégration continue | |
| Mer 2 déc | Réseau : requêtes parallèles, cache, WebSocket | |
| Ven 4 déc | **Évaluation** : démo et oral | Angular, TypeScript, Tailwind |
| Mer 16 déc | | Services, signals, formulaires réactifs |
| Jeu 17 déc | | Authentification côté front |
| Ven 8 jan | | Recherche réseau, temps réel, SSR |
| Jeu 21 jan | | **Évaluation** : démo et oral |
