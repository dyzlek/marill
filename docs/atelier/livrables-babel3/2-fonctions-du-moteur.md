# Livrable 2 : Fonctions du moteur

> **Brouillon à défendre à l'oral.** On raisonne en besoins : pour chaque fonction, entrées, sortie, erreurs possibles, garanties.
> Le moteur est fourni par l'enseignant (17 nov) : ces fiches fixent ce que **nous** attendons de lui, pas son algorithme.
> Constantes : alphabet de 29 symboles (a à z, espace, virgule, point), page de 3 200 caractères, clé secrète propre à la bibliothèque.

Vocabulaire : une **adresse** = hexagone (texte pouvant dépasser 3 000 caractères) + mur + étagère + volume + page.

---

## F1 : Analyser une adresse
- **Besoin** : transformer le texte `<hexagone>-w4-s4-v23:172` en parties exploitables.
- **Entrée** : une chaîne.
- **Sortie** : hexagone, mur, étagère, volume, page.
- **Erreurs** : format absent ou incomplet ; séparateurs mal placés ; nombres non entiers ou négatifs ; chaîne vide ; longueur déraisonnable.
- **Garantit** : ne génère aucune page ; ne plante jamais, quelle que soit l'entrée ; analyser puis formater (F3) redonne l'adresse d'origine.

## F2 : Valider une adresse
- **Besoin** : décider si une adresse existe dans la bibliothèque.
- **Entrée** : les parties d'une adresse (ou une chaîne, via F1).
- **Sortie** : valide, ou liste des problèmes.
- **Erreurs** : hexagone avec un caractère hors alphabet ; mur, étagère, volume ou page hors des bornes ; hexagone trop long.
- **Garantit** : une adresse validée peut toujours être passée à F4 sans erreur ; le message d'erreur nomme l'élément fautif (story V2).

## F3 : Formater une adresse
- **Besoin** : produire la forme textuelle canonique d'une adresse.
- **Entrée** : les parties d'une adresse valides.
- **Sortie** : la chaîne canonique.
- **Erreurs** : parties invalides (voir F2).
- **Garantit** : une seule écriture par adresse, ce qui sert de clé pour les favoris, signalements, masquages et caches.

## F4 : Générer une page
- **Besoin** : renvoyer le contenu de la page à une adresse (stories V1, B1).
- **Entrées** : une adresse valide ; la clé secrète de la bibliothèque (lue en configuration, jamais passée par l'appelant).
- **Sortie** : une page de exactement 3 200 caractères.
- **Erreurs** : adresse invalide ; clé absente ou mal formée ; extension GMP manquante.
- **Garantit** : déterministe (même adresse + même clé donne la même page) ; uniquement les 29 symboles ; deux adresses différentes donnent deux pages différentes ; la clé ne peut pas être déduite des pages ; aucun stockage de page.

## F5 : Trouver l'adresse d'une phrase
- **Besoin** : fournir une adresse dont la page contient la phrase (stories V3, B2).
- **Entrées** : une phrase ; la clé secrète.
- **Sortie** : une adresse valide.
- **Erreurs** : phrase vide ; caractère hors alphabet ; phrase plus longue que 3 200 caractères.
- **Garantit** : roundtrip : `F4(F5(phrase))` contient la phrase ; même phrase donne même adresse ; fonctionne même si la phrase est coupée sur plusieurs lignes d'affichage.
- **À trancher avec l'enseignant** : une phrase à cheval sur deux pages est-elle possible ?

## F6 : Normaliser une saisie
- **Besoin** : rendre une phrase utilisable (story V4).
- **Entrée** : texte libre.
- **Sortie** : texte réduit à l'alphabet de 29 symboles.
- **Erreurs** : texte vide après normalisation.
- **Garantit** : minuscules, accents retirés, caractères non convertibles signalés plutôt que supprimés en silence ; résultat idempotent.

## F7 : Tirer une adresse au hasard
- **Besoin** : story V5.
- **Entrée** : aucune.
- **Sortie** : une adresse valide.
- **Erreurs** : source d'aléa indisponible.
- **Garantit** : toujours valide (F2) ; répartition uniforme sur tout l'espace d'adresses.

## F8 : Calculer une adresse voisine
- **Besoin** : navigation précédent/suivant (story V6).
- **Entrées** : une adresse valide ; une direction (page, étagère, volume, mur).
- **Sortie** : l'adresse voisine, ou « aucune » en bout de course.
- **Erreurs** : adresse invalide ; direction inconnue.
- **Garantit** : le voisin est valide ; aller puis revenir redonne l'adresse d'origine.

## F9 : Empreinte d'une adresse
- **Besoin** : identifier une adresse de plus de 3 000 caractères dans la base et le cache sans comparer des textes énormes.
- **Entrée** : une adresse (forme canonique, F3).
- **Sortie** : un condensé de taille fixe.
- **Erreurs** : adresse invalide.
- **Garantit** : même adresse donne même empreinte ; collisions négligeables ; ne dépend pas de la clé secrète.

## F10 : Vérifier la conformité d'une page reçue
- **Besoin** : se protéger d'une bibliothèque qui « répond n'importe quoi » (page d'une voisine) et supporter le testeur (story P2).
- **Entrée** : une réponse (texte de page) venue d'ailleurs.
- **Sortie** : conforme ou non, avec la raison.
- **Erreurs** : aucune (la fonction retourne toujours un verdict).
- **Garantit** : vérifie ce qui est vérifiable sans connaître la clé de l'autre : longueur exacte de 3 200, alphabet, et que la phrase demandée y figure ; **ne compare jamais le texte avec nos propres pages**.

---

## Ce que le moteur ne fait pas (à gérer ailleurs)
| Besoin | Où |
|---|---|
| Masquer une page signalée | API / base (le moteur recalcule toujours la page) |
| Appeler les voisines, délais, cache | agrégateur réseau |
| Authentifier les appelants | API |
| Stocker la clé secrète | configuration serveur, jamais en base |
