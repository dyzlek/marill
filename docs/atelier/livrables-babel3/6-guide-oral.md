# Guide pour comprendre et défendre le projet (oral du jeudi 5 novembre)

Ce fichier accompagne `5-presentation-babel3.pptx` (14 slides, notes de l'orateur dans chaque slide). Il sert à **comprendre**, puis à **se préparer aux questions**. Les justifications sont des propositions tirées de vos livrables : à vous de dire si vous les maintenez. Si c'est l'IA qui a réfléchi, ça se verra : reformulez avec vos mots.

---

## 1. Comprendre en 5 minutes

**Le projet.** Une bibliothèque en ligne qui contient toutes les pages possibles de texte (nouvelle de Borges). Chaque adresse donne une page de 3 200 lettres sans sens. Une phrase peut s'y cacher. Dix bibliothèques, faites par dix groupes, forment un réseau et se parlent via la même API.

**Le moteur.** Un programme donné par l'enseignant (le 17 nov). Il prend une adresse et une clé secrète, et calcule la page. Vous ne le codez pas : vous dites ce que vous attendez de lui.

**L'idée qui explique presque tout.** Il y a 10^4680 pages possibles : impossible de les stocker. Donc la page se **recalcule** à chaque fois. Même adresse + même clé = même page. En base, on ne garde que des **adresses** (marque-pages, signalements...).

**Les mots à connaître.**

| Mot | Sens simple |
|---|---|
| Adresse | `<hexagone>-w4-s4-v23:172` : hexagone, mur 4, étagère 4, volume 23, page 172. L'hexagone peut dépasser 3 000 caractères. |
| Clé secrète | Propre à chaque bibliothèque. Elle explique que la même adresse donne une page différente d'une bibliothèque à l'autre. |
| User story | Une phrase : « En tant que [rôle], je veux [action], afin de [but] ». |
| MLD | Le plan de la base de données : tables, colonnes, liens, cardinalités (0,n / 1,1). |
| C4 (conteneurs) | Un schéma de boîtes : qui appelle qui, pour quoi faire, avec quelles données. Sans techno. |
| Agrégateur | La partie qui interroge les autres bibliothèques en parallèle et rassemble les réponses. |
| Conformité | Vérifier qu'une bibliothèque respecte le contrat d'API commun. |

**Ce que vous faites, dans l'ordre.** Stories (besoins) → fonctions du moteur (ce qu'on lui demande) → MLD (ce que la base garde) → architecture (qui appelle qui). Chaque étape découle de la précédente : c'est votre meilleure défense contre « c'est de l'IA ».

---

## 2. Plan de parole suggéré (5 à 7 minutes)

1. **Le projet en une phrase** (slides 1 à 3) : on conçoit une bibliothèque, pas le moteur. Dire l'idée « on ne stocke jamais une page ».
2. **Les stories** (slides 5 et 6) : 5 rôles, ce que le compte apporte, un choix à assumer (recherche réseau pour les membres).
3. **Le moteur** (slides 7 et 8) : 10 fonctions en 3 familles, 4 garanties.
4. **Le MLD** (slides 9 et 10) : ne pas lire le schéma, montrer trois endroits.
5. **L'architecture** (slides 11 et 12) : lire en trois temps (personnes, notre bibliothèque, systèmes externes).
6. **Les cas difficiles** (slide 13), puis les points encore ouverts (slide 14).

Répartissez les parties entre vous : chacun doit pouvoir répondre sur **tout** livrable, mais présente le sien.

---

## 3. Questions probables du jury (à vous de répondre)

Pour chaque question, des pistes issues de vos livrables et une question pour réfléchir.

**Une page qui choque, que fait-on ?**
Pistes : signalement (membre) → file de l'admin → masquage valable partout. On masque, on ne supprime pas, car le moteur recalculerait la page.
*À vous : pourquoi un visiteur ne peut-il pas signaler ? Est-ce le bon choix ? (Votre Moodle contient une story « Visiteur : signaler », le brouillon local dit « Membre » seulement : accordez-vous.)*

**Une adresse mal écrite ?**
Pistes : validation avant lecture, message précis, une seule écriture acceptée (w4, pas w04).
*À vous : pourquoi refuser plutôt que corriger automatiquement ?*

**Une bibliothèque éteinte, lente, ou qui répond n'importe quoi ?**
Pistes : délai max par voisine, requêtes en parallèle, statut affiché, vérification de propriétés (3 200 caractères, alphabet, phrase présente), suspension par l'admin.
*À vous : combien d'échecs avant « suspecte » ? Qui décide ?*

**Où vit la clé secrète, qui la voit passer ?**
Pistes : configuration serveur, vue par le seul moteur, jamais en base, jamais dans une réponse ni un journal.
*À vous : comment s'assurer qu'elle ne fuit pas dans les journaux d'erreurs ?*

**Comment vérifier la conformité si chaque bibliothèque affiche des textes différents ?**
Pistes : on compare des propriétés, pas des textes.
*À vous : quelle propriété prouve que la phrase cherchée est bien dans la page ?*

**Pourquoi ces 11 tables dans le MLD ?**
Pistes : chaque table répond à une story (collections, liens courts, votes, commentaires, signalements, bibliothèques voisines).
*À vous : quelles tables supprimeriez-vous si le temps manque ? Qu'est-ce qui reste indispensable ?*

**Pourquoi un cache ? Un diffuseur temps réel ?**
Pistes : le cache évite de rappeler 9 voisines pour la même phrase ; le temps réel affiche les résultats au fil de l'eau (cours du 2 décembre).
*À vous : quelle durée de vie du cache est raisonnable ?*

---

## 4. Points à régler avant jeudi 11h30

- [ ] **Moodle vs dossier local.** Sur Moodle, vos 4 fiches « fonctions du moteur » sont : lire une page, trouver l'adresse d'un texte, valider une adresse, tirer au hasard. Votre dossier local en décrit 10. Décidez ce que vous déposez et gardez le même discours.
- [ ] Stories : le brouillon local en compte 31 ; Moodle en a 24 à ce jour. Vérifiez ce qui est réellement déposé.
- [ ] Trancher les 4 points ouverts (slide 14) et écrire pourquoi.
- [ ] Chacun relit les 4 livrables et sait expliquer **un choix** qu'il n'a pas écrit.
- [ ] Déposer le MLD et l'architecture (PNG + `.drawio`), une seule fiche par groupe.
- [ ] Choisir qui parle de quoi et chronométrer.

Le jour J, le jury compare à un moteur réel : si l'on vous demande quelque chose que vous ne savez pas, dites-le et proposez comment le vérifier.
