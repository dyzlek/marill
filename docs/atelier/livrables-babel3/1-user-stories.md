# Livrable 1 : User stories

> **Brouillon à défendre à l'oral (jeudi 5 novembre).** Une fiche par story, à recopier dans la base Moodle « Fiches » (groupes Babel 3).
> Chaque fiche : **Rôle / Je veux / Afin de**, plus un critère d'acceptation court pour pouvoir trancher à l'oral.
> Rôles de départ : Visiteur, Membre, Administrateur, Autre bibliothèque. Rôle ajouté (« Autre rôle ») : **Plateforme** (annuaire et testeur de conformité de l'enseignant).
> 31 stories, au moins 3 par rôle.

## Ce que chaque rôle peut faire (réponse à « un visiteur peut-il tout faire ? »)

| Capacité | Visiteur | Membre | Admin | Autre biblio |
|---|:-:|:-:|:-:|:-:|
| Ouvrir une page, chercher une phrase | oui | oui | oui | oui (via l'API) |
| Page au hasard, page voisine | oui | oui | oui | non |
| Partager une page par lien | oui | oui | oui | non |
| Marque-pages, collections, historique | non | oui | non | non |
| Signaler une page | non | oui | non | non |
| Rechercher sur le réseau | non | oui | non | non |
| Masquer une page, gérer voisines et jetons | non | non | oui | non |

Choix à justifier à l'oral : le compte n'apporte pas l'accès au contenu, il apporte la **mémoire** (marque-pages, historique), le **pouvoir de signaler** et la **recherche réseau**, qui coûte des appels aux voisines.

---

## Rôle : Visiteur

**V1 : Ouvrir une page par son adresse**
- Je veux saisir ou ouvrir une adresse (`<hexagone>-w4-s4-v23:172`), afin de lire la page qu'elle contient.
- *Critère* : la même adresse donne toujours la même page.

**V2 : Comprendre une adresse mal écrite**
- Je veux un message précis quand mon adresse est invalide (mur hors limites, caractère interdit, partie manquante), afin de savoir quoi corriger.
- *Critère* : l'élément fautif est nommé ; aucune page n'est affichée ; pas de plantage.

**V3 : Chercher une phrase**
- Je veux saisir une phrase, afin de trouver une page où elle apparaît.
- *Critère* : en ouvrant l'adresse obtenue, la phrase est dans la page.

**V4 : Savoir quels caractères sont acceptés**
- Je veux que la saisie me prévienne que seuls a à z, espace, virgule et point existent, afin de ne pas chercher une phrase impossible.
- *Critère* : accents et majuscules sont normalisés ; un caractère impossible est signalé, pas supprimé en silence.

**V5 : Tomber sur une page au hasard**
- Je veux un bouton « page au hasard », afin d'explorer sans connaître d'adresse.
- *Critère* : l'adresse tirée est valide et sa page s'affiche.

**V6 : Parcourir comme dans une vraie bibliothèque**
- Je veux passer à la page, à l'étagère, au volume ou au mur voisin, afin de lire autour d'une page qui me fascine.
- *Critère* : aux limites, la navigation est bloquée proprement ; aller puis revenir redonne la même page.

**V7 : Partager une page par un lien**
- Je veux copier un lien vers une page (adresse complète ou lien court), afin de la montrer à quelqu'un sans compte.
- *Critère* : le lien s'ouvre sans connexion et donne la même page ; une adresse de 3 000 caractères reste partageable.

**V8 : Créer un compte**
- Je veux m'inscrire avec e-mail et mot de passe, afin de conserver mes pages et d'accéder aux fonctions réservées aux membres.
- *Critère* : e-mail unique ; mot de passe jamais stocké en clair.

---

## Rôle : Membre

**M1 : Se connecter et se déconnecter**
- Je veux me connecter de façon sécurisée, afin de retrouver mon espace.
- *Critère* : jeton à durée limitée ; la déconnexion l'invalide.

**M2 : Garder une page en marque-page**
- Je veux enregistrer une page avec une note personnelle, afin de la retrouver plus tard.
- *Critère* : seule l'adresse est conservée, pas le texte. Un marque-page ouvert dans un an donne la même page.

**M3 : Organiser mes marque-pages en collection**
- Je veux grouper des pages dans une collection nommée, afin de rassembler celles qui vont ensemble.
- *Critère* : une page peut figurer dans plusieurs collections.

**M4 : Rendre une collection publique ou privée**
- Je veux choisir qui voit ma collection (moi seul, quiconque a le lien), afin de contrôler ce que je partage.
- *Critère* : privée par défaut ; la rendre publique génère un lien ; la repasser en privée l'invalide.

**M5 : Signaler une page qui me choque**
- Je veux signaler une page avec un motif, afin que l'administrateur décide de la masquer.
- *Critère* : un seul signalement par page et par membre ; je vois le statut (en attente, accepté, refusé).

**M6 : Rechercher sur tout le réseau**
- Je veux lancer une phrase sur toutes les bibliothèques à la fois, afin de comparer les pages que chacune propose pour la même phrase.
- *Critère* : les résultats arrivent au fil de l'eau ; une voisine lente ou éteinte ne bloque pas les autres.

**M7 : Savoir pourquoi une bibliothèque n'a pas répondu**
- Je veux voir pour chaque bibliothèque interrogée si elle a répondu, expiré, répondu n'importe quoi ou été injoignable, afin de savoir si l'absence de résultat est fiable.
- *Critère* : un statut et une durée par bibliothèque.

**M8 : Retrouver et supprimer mes données**
- Je veux relancer une recherche passée, modifier mon profil et supprimer mon compte, afin de garder la maîtrise de mes données.
- *Critère* : la suppression efface marque-pages, collections, historique et signalements nominatifs.

---

## Rôle : Administrateur

**A1 : Traiter les signalements**
- Je veux une file des pages signalées avec leur motif, afin de décider de les masquer ou de rejeter le signalement.
- *Critère* : décision tracée (qui, quand, pourquoi).

**A2 : Masquer une page**
- Je veux qu'une adresse masquée affiche un message neutre à la place du texte, afin de protéger les lecteurs.
- *Critère* : le masquage vaut aussi pour la recherche, les liens partagés et les appels des autres bibliothèques. Rien n'est supprimé : le moteur recalculerait la page.

**A3 : Gérer les bibliothèques voisines**
- Je veux ajouter, suspendre ou retirer une bibliothèque du réseau, afin d'écarter celle qui répond n'importe quoi.
- *Critère* : une bibliothèque suspendue n'est plus interrogée.

**A4 : Suivre la santé du réseau**
- Je veux voir latence, taux d'échec et dernière réponse de chaque voisine, afin de repérer les défaillantes.
- *Critère* : passage automatique en « suspecte » après N échecs consécutifs (N réglable).

**A5 : Gérer les membres**
- Je veux suspendre un compte ou changer un rôle, afin de gérer les abus.
- *Critère* : action journalisée.

**A6 : Contrôler qui appelle mon API**
- Je veux créer un jeton par bibliothèque partenaire et le révoquer, afin de savoir qui utilise mon API.
- *Critère* : le jeton n'est affiché qu'une fois ; seule son empreinte est stockée.

**A7 : Garder la clé secrète secrète**
- Je veux que la clé ne vive que dans la configuration du serveur, afin qu'elle ne fuite ni par la base, ni par les journaux, ni par une réponse.
- *Critère* : aucune réponse d'API ni ligne de journal ne la contient.

---

## Rôle : Autre bibliothèque

**B1 : Demander une page**
- Je veux demander la page d'une adresse, afin de l'afficher à mes utilisateurs ou de vérifier un résultat.
- *Critère* : réponse conforme au contrat commun.

**B2 : Demander l'adresse d'une phrase**
- Je veux envoyer une phrase et recevoir une adresse, afin d'agréger les résultats du réseau.
- *Critère* : réponse dans le délai fixé par le contrat, sinon je la traite comme expirée.

**B3 : Recevoir des erreurs standardisées**
- Je veux des codes et un format d'erreur communs (adresse invalide, non autorisé, page masquée, trop de requêtes), afin de les traiter sans cas particulier.
- *Critère* : mêmes codes que la spécification du réseau.

**B4 : M'authentifier**
- Je veux présenter un jeton, afin de prouver qui je suis.
- *Critère* : sans jeton valide ou avec un jeton révoqué, refus.

**B5 : Connaître la limite de débit**
- Je veux que la limite de requêtes soit annoncée (en-têtes, code 429), afin d'adapter mon rythme.
- *Critère* : une voisine trop bavarde ne fait pas tomber le service.

---

## Autre rôle : Plateforme (annuaire et testeur)

**P1 : Lister les bibliothèques**
- Je veux fournir l'annuaire des bibliothèques et leur adresse, afin que chacune connaisse ses voisines.
- *Critère* : notre bibliothèque récupère et met à jour sa liste.

**P2 : Tester la conformité**
- Je veux appeler l'API d'une bibliothèque et vérifier ses réponses, afin de valider qu'elle respecte le contrat.
- *Critère* : le test ne compare pas les textes (chaque clé donne d'autres pages) mais des propriétés : 3 200 caractères, alphabet respecté, même adresse donne même page, la phrase cherchée se retrouve dans la page.

**P3 : Connaître l'état d'une bibliothèque**
- Je veux un point de santé léger, afin d'afficher une disponibilité fiable.
- *Critère* : sans authentification et sans rien de secret.

---

## Points à trancher en groupe avant jeudi
1. **Lien court ou adresse complète pour partager** : l'adresse peut dépasser 3 000 caractères, un lien court demande de stocker une correspondance en base (V7, M4).
2. **Un visiteur peut-il rechercher sur le réseau ?** Ici non, pour limiter la charge sur les voisines (M6).
3. **Le partage d'une collection fait-il apparaître le nom du membre ?** (M4)
4. **Qu'apporte le réseau si chaque bibliothèque contient déjà tout ?** Ici : comparer les pages que chacune renvoie pour la même phrase, car les clés diffèrent (M6).
