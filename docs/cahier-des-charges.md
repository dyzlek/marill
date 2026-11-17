# Cahier des charges final : résumé de travail

Source : « La Bibliothèque de Babel en réseau, Cahier des charges final », version du 15 novembre 2026, Damien Marill (31 pages). Le PDF reste hors du dépôt. Ce fichier résume le texte **visible** du document pour s'y retrouver vite. En cas de doute, le PDF fait foi, et l'enseignant tranche.

Le contrat d'API commun (routes, forme des erreurs, format d'export) n'est **pas encore publié**. Ce cahier des charges en est la base.

## Les 10 décisions qui tiennent tout le reste

1. On ne masque jamais une page. On modère ce qui est publié autour : annotations, collections, liens courts.
2. Seuls les membres annotent ; chaque annotation est publique ou privée, au choix de l'auteur.
3. Le réseau est ouvert en lecture : une autre bibliothèque lit ce qu'un visiteur lit. Seule la liste des textes bannis est réservée aux bibliothèques du réseau, par un **jeton réseau** commun.
4. Un compte est local à une bibliothèque. On en change par export puis import, qui retrouve chaque page dans la nouvelle bibliothèque.
5. Modération en réseau : on s'échange le **texte banni lui-même**, chaque bibliothèque décide chez elle.
6. Aucune fonction ne reçoit la clé du moteur. Seule son **empreinte** est publiée, pour qu'un changement de clé se voie.
7. La recherche renvoie une adresse, toujours la même pour un même texte et un même mode. La position du texte se calcule dans l'application web.
8. Une adresse mal écrite est refusée, jamais corrigée.
9. La page au hasard est un vrai tirage, sans graine.
10. Tout le monde peut créer un lien court, visiteurs compris.

Vocabulaire : « collection » = regroupement ordonné de pages ; « annotation » = ce qu'un membre écrit sur une page ; « pair » = une autre bibliothèque du réseau.

## Livrable 1 : 51 user stories

Priorités : **I** = Indispensable, **Imp** = Important, **B** = Bonus.

### Visiteur (21)

| N° | Je veux | Prio |
|---|---|---|
| V1 | ouvrir une page à partir de son adresse | I |
| V2 | page précédente / suivante, y compris d'un volume ou d'un hexagone à l'autre | I |
| V3 | changer de mur, d'étagère ou de volume | Imp |
| V4 | chercher un texte | I |
| V5 | choisir le mode : texte seul (exact) ou entouré de caractères aléatoires (bruit) | Imp |
| V6 | voir le texte réellement cherché une fois converti | I |
| V7 | voir mon texte surligné dans la page trouvée | I |
| V8 | message clair quand une adresse est refusée | I |
| V9 | ouvrir une page au hasard | Imp |
| V10 | copier l'adresse complète ou le lien de la page | I |
| V11 | créer un lien court vers une page, un livre ou une collection, avec un passage à surligner | I |
| V12 | ouvrir un lien court reçu (page, collection, ou page 1 du livre) | I |
| V13 | consulter les collections publiques et les pages populaires | Imp |
| V14 | lire les annotations publiques d'une page | Imp |
| V15 | chercher un texte sur toutes les bibliothèques du réseau | I |
| V16 | ouvrir la même adresse dans une autre bibliothèque | B |
| V17 | signaler un contenu publié (annotation, collection, lien court) | I |
| V18 | ne plus voir un contenu que j'ai signalé, quelle que soit la décision | Imp |
| V19 | créer un compte (pseudo, mot de passe, email = identifiant de connexion) | I |
| V20 | lire une page « Manifest » | B |
| V21 | parcourir collections publiques et pages populaires des autres bibliothèques | B |

### Membre (13)

| N° | Je veux | Prio |
|---|---|---|
| M1 | me connecter (email + mot de passe), me déconnecter | I |
| M2 | marque-page avec titre facultatif | I |
| M3 | lister, rouvrir, retirer mes marque-pages | I |
| M4 | créer une collection ordonnée, la renommer, la supprimer | Imp |
| M5 | rendre une collection publique ou privée | Imp |
| M6 | annoter une page (modifier, supprimer, public/privé) | Imp |
| M7 | voter pour ou contre une page | B |
| M8 | revoir mes recherches passées | B |
| M9 | modifier pseudo, email, mot de passe | Imp |
| M10 | supprimer mon compte (RGPD) | Imp |
| M11 | exporter mes données : profil, marque-pages, collections, annotations, votes, recherches, liens courts | Imp |
| M12 | importer mon export dans une autre bibliothèque, qui retrouve chaque page | Imp |
| M13 | garder une page d'une autre bibliothèque (marque-page ou collection) | Imp |

### Administrateur (11)

Le premier admin est créé à l'installation, hors interface ; les suivants sont promus (A3).

| N° | Je veux | Prio |
|---|---|---|
| A1 | voir et filtrer la liste des comptes | I |
| A2 | suspendre, rétablir, supprimer un compte | I |
| A3 | promouvoir un membre administrateur | B |
| A4 | traiter la file des signalements : masquer, rétablir, rejeter | I |
| A5 | déclarer ou lever un texte banni | Imp |
| A6 | publier mes textes bannis aux seules bibliothèques du réseau | Imp |
| A7 | examiner les textes bannis des autres et décider de les appliquer | Imp |
| A8 | configurer : nom, description, inscriptions ouvertes/fermées | I |
| A9 | voir l'état du réseau : annuaire, pairs qui répondent, clés changées | Imp |
| A10 | limiter le nombre de requêtes sur l'API publique | Imp |
| A11 | statistiques : comptes, actifs, pages lues, recherches, signalements, pages partagées | B |

### Autre bibliothèque (6) : première version du contrat d'API

| N° | Je veux | Prio |
|---|---|---|
| B1 | lire une page chez vous par son adresse | I |
| B2 | obtenir l'adresse d'une page contenant un texte | I |
| B3 | la liste de vos collections publiques | Imp |
| B4 | vos pages les plus populaires | B |
| B5 | relever vos textes bannis, avec le jeton réseau | Imp |
| B6 | votre identité : identifiant, nom, description, empreinte de clé | I |

B3 et B4 renvoient des adresses accompagnées de l'identifiant de leur bibliothèque. B5 renvoie des textes.

### Plateforme du réseau (fournie)

Annuaire + testeur de conformité. Chaque bibliothèque s'annonce avec son identifiant et son URL du moment, authentifiée par un **secret d'annonce** propre (qui ne sert jamais entre bibliothèques). La plateforme remet aussi le **jeton réseau**, le même pour tous, qui n'ouvre que B5. Elle vérifie la présence en lisant B6. Le testeur vérifie la conformité sans connaître la clé.

## Livrable 2 : 8 fonctions, 10 codes d'erreur

Adresse : `<hexagone>-w<mur>-s<étagère>-v<volume>:<page>`. Hexagone en **base 36** (chiffres + minuscules), au plus **3 004 caractères** ; mur 1–4, étagère 1–5, volume 1–32, page 1–410. Sans `:<page>` = adresse d'un livre (seulement pour un lien court ; on ouvre sa page 1). Une page = exactement **3 200 caractères** parmi 29 symboles (a–z, espace, virgule, point).

| N° | Fonction | Où |
|---|---|---|
| F1 | `lire(adresse)` → texte de la page | moteur |
| F2 | `chercher(texte, mode)` → adresse | moteur |
| F3 | `normaliser(texte)` → texte cherchable | serveur (règle commune) |
| F4 | `localiser(texte, page)` → positions | application web (règle commune) |
| F5 | `valider(adresse)` → valide + niveau, ou liste d'erreurs | serveur (règle commune) |
| F6 | `voisine(adresse, sens, niveau)` → adresse voisine | application web (règle commune) |
| F7 | `auHasard()` → adresse de page | serveur (règle commune) |
| F8 | `empreinte()` → empreinte de la clé | moteur |

Points clés :

- **F1** : refuse une adresse de livre (`ERR_INVALID_ADDRESS_SYNTAX`, champ page). Même adresse = même texte, toujours. Calcul en quelques millisecondes.
- **F2** : modes `exact` (texte + espaces), `bruit` (texte entouré de pseudo-aléatoire **dépendant du texte**), `page` (texte de 3 200 caractères → adresse de cette page, sert à l'import M12). Toujours la même adresse pour un même texte et un même mode. L'interface ne propose que exact et bruit. Ne renvoie pas la position.
- **F3** : 1) minuscules ; 2) retirer accents/cédille, développer œ → oe, æ → ae ; 3) tout caractère hors alphabet → espace (sauts de ligne compris) ; 4) réduire les espaces multiples ; 5) trim. Idempotente. En mode page, on s'arrête à l'étape 3. Le serveur renvoie le texte normalisé avec l'adresse (V6). Exemple : « Où est passé l'été ? » → `ou est passe l ete`.
- **F4** : toutes les positions de départ (0–3199), dans l'ordre ; liste vide sinon. Le serveur fait le même calcul pour détecter les textes bannis.
- **F5** : ne lève jamais d'exception, renvoie **toutes** les erreurs d'un coup, chacune avec code + champ. Une seule écriture valide par page : `w04`, `W4`, hexagone en majuscules sont refusés. Bien écrite mais inexistante (au-delà de la dernière page) = `ERR_OUT_OF_BOUNDS`.
- **F6** : après la page 410 vient la page 1 du volume suivant, puis étagère, mur, hexagone suivants. À un niveau supérieur, les coordonnées fines ne changent pas. `precedente(suivante(x)) = x`. Nécessite un entier sans limite de taille (hexagone de 3 004 caractères). Le dernier hexagone ne contient qu'une page.
- **F7** : uniforme sur toute la bibliothèque, non reproductible. Piège : un aléatoire 64 bits ne touche que les premiers hexagones. Test par distribution : sur 12 000 tirages, murs/étagères/volumes/pages équirépartis ; hexagone de 3 004 caractères ≈ 79 %, 3 003 ≈ 20 %, moins < 1 %.
- **F8** : chaîne de longueur fixe, stable tant que la clé ne change pas, ne permet pas de retrouver la clé.

**La clé** : lue par le moteur dans la configuration du serveur. Jamais en base, jamais dans une réponse, jamais dans un journal, ne sort jamais du serveur. Ne doit pas changer après l'ouverture.

### Codes d'erreur

| Code | Quand | HTTP proposé |
|---|---|---|
| `ERR_INVALID_ADDRESS_SYNTAX` | forme non respectée | 422 |
| `ERR_INVALID_HEX_CHARS` | caractère interdit dans l'hexagone | 422 |
| `ERR_OUT_OF_BOUNDS` | bien écrite, page inexistante | 404 |
| `ERR_EMPTY_QUERY` | texte vide après conversion | 422 |
| `ERR_TEXT_TOO_LONG` | plus de 3 200 caractères après conversion | 422 |
| `ERR_INVALID_ALPHABET` | moteur a reçu un symbole hors alphabet (bug : le serveur normalise avant) | aucune |
| `ERR_UNKNOWN_MODE` | mode inexistant | 422 |
| `ERR_INVALID_PAGE_LENGTH` | mode page, texte ≠ 3 200 caractères | 422 |
| `ERR_LIBRARY_LIMIT` | voisine avant la première / après la dernière page | aucune (bouton désactivé côté web) |
| `ERR_TOO_MANY_REQUESTS` | quota API publique dépassé (A10) | 429 |

Forme commune : une **liste** d'erreurs, chacune avec code, champ (si pertinent) et message lisible. Un seul statut : 422 dès qu'une erreur de forme est présente, 404 s'il ne reste que `ERR_OUT_OF_BOUNDS`. Les autres cas (lien court inconnu/expiré/masqué, pair injoignable, accès refusé, inscriptions fermées, import invalide) auront leur code dans le contrat d'API.

## Livrable 3 : modèle logique, 14 tables, 22 règles

Aucune table ne contient le texte d'une page.

| Table | Colonnes |
|---|---|
| UTILISATEUR | id, pseudo, email, mot_de_passe_hache, role, suspendu_le, derniere_connexion_le, cree_le |
| BIBLIOTHEQUE | id, identifiant, nom, description, url_actuelle, est_locale, etat, echecs_consecutifs, dernier_contact_le, vue_annuaire_le, empreinte_cle, cle_changee_le |
| ADRESSE | id, #bibliotheque_id, hexagone, mur, etagere, volume, page, empreinte, empreinte_livre, #texte_banni_id, cree_le |
| MARQUE_PAGE | id, #utilisateur_id, #adresse_id, titre, cree_le |
| COLLECTION | id, #utilisateur_id, nom, description, est_publique, masque_le, cree_le, maj_le |
| COLLECTION_PAGE | #collection_id, position, #adresse_id |
| ANNOTATION | id, #utilisateur_id, #adresse_id, contenu, est_publique, masque_le, cree_le, maj_le |
| VOTE | #utilisateur_id, #adresse_id, valeur, cree_le |
| LIEN_COURT | id, slug, #utilisateur_id, #collection_id, #adresse_id, passage, visites, expire_le, masque_le, cree_le |
| SIGNALEMENT | id, #annotation_id, #collection_id, #lien_court_id, #auteur_id, motif, statut, #traite_par_id, traite_le, cree_le |
| TEXTE_BANNI | id, texte, empreinte, motif, #origine_id, statut, decide_le, cree_le |
| RECHERCHE | id, #utilisateur_id, texte, mode, portee, cree_le |
| ACTIVITE_JOUR | jour, pages_lues, recherches |
| REGLAGE | cle, valeur |

Règles à retenir :

1. Aucune page en base.
2. Une adresse n'est enregistrée qu'une fois ; unicité sur `(bibliotheque_id, empreinte)`, empreinte = SHA-1 de l'adresse sous sa forme valide (un index unique sur 3 004 caractères est refusé).
3. Coordonnées en colonnes séparées ; `empreinte_livre` = SHA-1 de l'adresse sans la page.
4. Livre entier = `page` nul ; seul un lien court peut viser un livre.
5. Notre bibliothèque est une ligne de BIBLIOTHEQUE avec `est_locale` vrai (une seule).
6. Identifiant stable, URL changeante ; pas de liste blanche/noire ; `cle_changee_le` daté si l'empreinte change.
7. Marque-page privé, unique `(utilisateur_id, adresse_id)`.
8. Collection ordonnée : clé `(collection_id, position)`, une page peut revenir deux fois.
9. Annotation ≤ 2 500 caractères.
10. Vote : clé `(utilisateur_id, adresse_id)`, valeur ±1 ; popularité calculée (votes + nombre de marque-pages), non stockée.
11. Lien court : exactement une cible (adresse ou collection) ; sans compte = sans auteur et expiration obligatoire ; `passage` seulement avec une cible page.
12. Seules les recherches des membres sont gardées ; `portee` = locale ou réseau ; adresse non stockée.
13. Signalement : exactement un contenu (vraie FK) ; auteur nul si visiteur ; part avec le contenu supprimé.
14. Masquer = dater `masque_le` ; seul un admin traitant un signalement le pose.
15. V18 sans table : déduit de SIGNALEMENT (membre) ou du navigateur (visiteur).
16. Texte banni stocké en clair, normalisé ; l'empreinte sert à éviter les doublons ; `origine_id` = première bibliothèque qui l'a banni.
17. Statuts : `applique` (déclaré ici), `propose` (relevé ailleurs), `rejete`. Seuls les textes locaux appliqués sont publiés (B5, avec jeton).
18. Appliquer ne supprime rien : `ADRESSE.texte_banni_id` marque les adresses dont la page contient un texte banni ; ce qui y renvoie n'est plus montré, sans toucher à `masque_le`.
19. Supprimer un compte efface ses données ; ses signalements restent, sans auteur.
20. Export/import sans table. Export : profil sans mot de passe + tout le reste, chaque adresse avec l'identifiant de sa bibliothèque. Import : ne recrée que marque-pages, collections, annotations ; conversion par lecture chez l'origine puis `chercher` en mode page ; fusion des doublons.
21. Statistiques = compteurs par jour.
22. Réglages clé/valeur (inscriptions, quota). Clé du moteur et jeton réseau jamais en base.

Comment un texte banni masque ce qui est publié : recherche exact + bruit à l'application ; tâche de fond qui relit les pages déjà désignées ; vérification à chaque publication ; 410 pages vérifiées pour un lien court vers un livre ; levée = retrait des marques + revérification ; filtrage à la volée des contenus d'autres bibliothèques (V21). Les annotations ne sont pas vérifiées automatiquement : elles se signalent.

## Livrable 4 : architecture

| Élément | Type | Rôle |
|---|---|---|
| Visiteur ou membre | personne | lit, explore, cherche, liens courts ; avec compte : marque-pages, collections, annotations |
| Administrateur | personne | comptes, modération, textes bannis, suivi du réseau |
| Application web | conteneur (navigateur) | lecteur, navigation, recherche locale et réseau, espace membre ; calcule voisine, localise et surligne |
| Serveur d'application | conteneur | trois portes : API interne (app web), API publique (réseau), back-office (admin) ; normalise et valide |
| Moteur | composant fourni, embarqué | lire, chercher, empreinte ; lit sa clé dans la config |
| Base de données | conteneur | les 14 tables |
| Tâches de fond | conteneur | une tâche par pair en recherche réseau, contrôle des pairs, relevé des textes bannis, vérification des pages publiées, conversion des adresses importées |
| Serveur temps réel | conteneur | pousse les résultats à l'app web |
| Plateforme du réseau | système externe | annuaire + testeur |
| Autres bibliothèques | système externe ×9 | même API publique, clé différente |

Choix à défendre :

1. Le moteur est un composant, pas un conteneur.
2. La clé ne quitte pas le serveur ; seule l'empreinte est publiée (B6).
3. L'application web ne parle qu'à nos serveurs, jamais directement à un pair.
4. Une seule API publique, identique dans les 10 bibliothèques, ouverte en lecture sans jeton sauf la route des textes bannis. Limite de débit sur l'API publique et sur les actions sans compte (liens courts, signalements).
5. Résultats réseau au fil de l'eau.
6. **2 secondes** max par pair (fait partie du contrat).
7. Chaque réponse d'un pair est vérifiée contre le contrat ; hors contrat = échec. Un 429 n'est pas un échec.
8. Au **5e échec de suite**, un pair passe hors ligne jusqu'au prochain contrôle réussi.
9. Back-office séparé, servi par le serveur, accès rôle admin.
10. La surbrillance est calculée dans le navigateur.
11. Le réseau ne transporte aucune écriture.

### Recherche réseau, pas à pas

1. Saisie + « tout le réseau ». 2. L'app web crée un identifiant de recherche et **s'abonne d'abord** au canal temps réel. 3. Lance la recherche (identifiant, texte, mode). 4. Le serveur normalise. 5. Lit les pairs en ligne. 6. Dépose une tâche par pair. 7. Répond tout de suite : texte normalisé, nombre de pairs interrogés, adresse locale. 8. Chaque tâche interroge son pair (2 s max). 9. Vérifie la réponse. 10. Publie le résultat. 11. Le temps réel pousse à l'app web. 12. Affichage pair par pair. 13. Bilan « 7 pairs sur 9 ont répondu » + absents (délai global choisi par chaque bibliothèque). 14–18. Ouverture d'un résultat : notre serveur lit la page chez le pair, l'app web localise et surligne. Pair éteint/lent/hors contrat → « pair sans réponse » + compteur d'échecs.

### Modération en réseau

A déclare un texte banni (appliqué) → A l'expose sur l'API publique avec jeton → les tâches de fond de B le relèvent (proposé) → l'admin de B voit ce qui serait masqué chez lui → applique ou rejette. B ne republie jamais un texte venu d'ailleurs.

### Migration de compte

Export chez A (sans mot de passe) → compte chez B → import vérifié contre le contrat, adresses validées, marque-pages/collections/annotations recréés → tâche de fond : lecture chez A puis `chercher` mode page chez B ; tant que A ne répond pas, l'adresse désigne encore A (M13) → collections et annotations importées arrivent **privées** → suppression éventuelle du compte chez A. Le format d'export fait partie du contrat commun.

Non retenu notamment : cache des réponses de pairs en base (facultatif, en mémoire seulement), délai de 3 s, jeton pour tous les pairs.
