# CLAUDE.md : La Bibliothèque de Babel en réseau

Contexte pour Claude. À lire avant toute modification.

## Le projet en bref

Projet fil rouge **R5.06 (Back) + R5.05 (Front)**, BUT MMI 3, parcours Développement web et dispositifs interactifs, IUT de Béziers (Université de Montpellier). Cours lancé le lundi 2 novembre 2026.

Inspiré de la nouvelle de Borges et du site libraryofbabel.info : une bibliothèque contient **toutes les pages possibles** de texte. Chaque adresse renvoie une page de lettres sans sens, et une phrase peut s'y cacher.

Le réseau : **10 bibliothèques**, codées séparément par chaque étudiant ou groupe, qui se parlent via une **API commune** (comme les instances Mastodon ou PeerTube). Elles doivent répondre exactement pareil au même contrat. Le réseau est branché le **2 décembre**.

## Ce que fournit l'enseignant (le 17 novembre)

- **Le moteur** : génère les pages à partir d'une adresse, avec une clé secrète.
- **La plateforme** : annuaire des bibliothèques et testeur de conformité à l'API.
- **Le cahier des charges final**, tiré de nos choix.

## Règles du moteur (à respecter)

- **29 symboles** : a à z, espace, virgule, point.
- **Pages de 3 200 caractères**, rangées par hexagone, mur, étagère, volume et page.
- **Adresses très longues** : un nom d'hexagone peut dépasser 3 000 caractères.
- **Une clé secrète par bibliothèque** : la même adresse n'affiche pas la même page d'une bibliothèque à l'autre.
- Format d'adresse de l'exemple : `<hexagone>-w4-s4-v23:172` = hexagone, mur 4, étagère 4, volume 23, page 172.
- Chiffres du cours : 10^4680 pages possibles ; stocker toutes les pages demanderait environ 1,5 × 10^4671 To à 3,2 Ko par page.

## Stack imposée

- **Back** : Laravel (R5.06, PHP 8.3+ avec extension **GMP** obligatoire), Filament pour le back-office, API REST avec contrat **OpenAPI**, authentification **Sanctum / OAuth2 / JWT**, tests automatisés avec **Pest**, temps réel avec **Reverb** (WebSockets), base de départ **SQLite**.
- **Front** : Angular + TypeScript (R5.05), Tailwind CSS, services, signals, RxJS, intercepteurs et guards, temps réel avec Echo et Reverb, Node pour le build, SSR et déploiement.
- Outils : Git, Composer, Laravel installer, Node LTS.

## Livrables de l'atelier (groupes de 2 à 4)

Déposés sur **Moodle** au plus tard **jeudi 5 novembre à 11h30**. Pas de travail à la maison : on avance en séance. L'IA est autorisée, mais il faut pouvoir défendre chaque choix à l'oral (jeudi).

1. **User stories** : une fiche par story au format « En tant que [rôle], je veux [action], afin de [but] ». Rôles de départ : Visiteur (sans compte), Membre (avec compte), Administrateur, Autre bibliothèque (ajouter d'autres rôles si besoin). Au moins 3 stories par rôle. Dépôt sur la base Moodle « fiches ».
2. **Fonctions du moteur** : une fiche par fonction avec entrées, sortie, erreurs possibles et ce qu'elle garantit. On raisonne en besoins, pas en algorithme. Dépôt sur la base Moodle.
3. **MLD** : entités, attributs, identifiants, associations et cardinalités. Une seule fiche par groupe, export PNG et fichier `.drawio` (draw.io, sans compte).
4. **Architecture** : diagramme de conteneurs **C4** (boîtes sans technologie, flèches étiquetées : qui appelle qui, pour quoi faire, avec quelles données). Une seule fiche par groupe, PNG et `.drawio`. Un diagramme de séquence peut compléter pour la recherche sur le réseau.

Pistes de réflexion importantes : que se passe-t-il pour une page qui choque ? Une adresse mal écrite ? Une bibliothèque éteinte, lente, ou qui répond n'importe quoi ? Où vit la clé secrète, et qui la voit passer ? Comment vérifier la conformité quand chaque bibliothèque affiche des textes différents ?

## Calendrier

| Date | Back R5.06 | Front R5.05 |
|---|---|---|
| Lun 2 nov | Cours d'intro, atelier cahier des charges (14h40–15h25) | |
| Jeu 5 nov 9h30–12h30 | Atelier : finir les 4 livrables, dépôt Moodle avant 11h30, oral à 11h30 | |
| Ven 6 nov | Laravel et Filament, tutoriel pas à pas (venir avec tout installé) | |
| Mar 17 nov | Moteur et plateforme, API REST, OAuth | |
| Ven 20 nov | Tests automatisés, intégration continue | |
| Mer 2 déc | Réseau : requêtes parallèles, cache, WebSocket | |
| Ven 4 déc | **Évaluation R5.06** : démo et oral | Angular, TypeScript, Tailwind |
| Mer 16 déc | | Services, signals, formulaires réactifs |
| Jeu 17 déc | | Authentification côté front |
| Ven 8 jan | | Recherche réseau, temps réel, SSR |
| Jeu 21 jan | | **Évaluation R5.05** : démo et oral |

## Environnement à vérifier

`php -v` (8.3+), `php -m` (gmp doit apparaître), `composer -V`, `laravel --version`, `node -v`, `git --version`.

## Organisation du dépôt

- `CLAUDE.md` : ce fichier, le contexte du projet.
- `README.md` : présentation courte.
- `laravel-babel/` : dépôt git séparé (clone de https://github.com/withRen/laravel-babel), ignoré par ce dépôt.
- `.claude/` : journal local de Claude, ignoré par git.

## Consignes de travail pour Claude

- Répondre en **français**.
- Ne pas écrire à la place des étudiants les livrables de réflexion : aider à cadrer, questionner, relire, mais la décision et la justification viennent du groupe.
- Ne jamais valider un livrable comme « correct » sans explication : l'oral tranche.
- Les pistes de réflexion sont des questions, pas un questionnaire : pas de réponse unique.
- Ne rien committer ni pousser sans demande explicite.
