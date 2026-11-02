# Livrables de l'atelier : La Bibliothèque de Babel en réseau

Par groupes de 2 à 4. Chaque livrable a son activité : déposez-le dès qu'il est prêt, **au plus tard jeudi 5 novembre à 11h30** sur Moodle. Pas de travail à la maison.

Les pistes de réflexion ne sont pas un questionnaire : rien à y répondre par écrit, elles servent à ne rien oublier. Il n'y a pas de réponse unique, et une réponse justifiée vaut mieux qu'une réponse sûre d'elle.

Ensuite, on compare les livrables un par un : chaque groupe défend ses choix à l'oral et on tranche. L'IA est autorisée, mais si c'est elle qui a réfléchi à votre place, ça se verra à l'oral.

Première étape : inscrire votre groupe dans « Choix du groupe » sur Moodle.

---

## Livrable 1 : User stories

**À rendre :** ce que la bibliothèque permet de faire, et pour qui. Une fiche par user story, sur le modèle : qui (un rôle), veut faire quoi (une action), pour quoi faire (le but).

> Exemple : « En tant que visiteur, je veux ouvrir une page à partir de son adresse, afin de lire ce qu'elle contient. »

**Rôles de départ** (ajoutez-en d'autres avec « Autre rôle » en précisant) :
- **Visiteur** : arrive sur la bibliothèque sans compte.
- **Membre** : a un compte.
- **Administrateur** : gère la bibliothèque.
- **Autre bibliothèque** : une bibliothèque du réseau qui s'adresse à la vôtre.

**Consigne :** au moins **3 stories par rôle**.

> Autre exemple : « En tant que membre, je veux garder une page en marque-page, afin de la retrouver plus tard. »

**Pistes de réflexion**
- Un visiteur sans compte peut-il tout faire ? Qu'apporte un compte ? Que peut demander une autre bibliothèque à la vôtre ?
- Que veut faire quelqu'un qui tombe sur une page qui le fascine ? Et sur une page qui le choque, sachant que le moteur peut produire n'importe quel texte ?
- Comment partager une page, ou un ensemble de pages ? Public ou privé, lien court ou adresse complète ?
- Qui administre une bibliothèque, et que doit-il pouvoir faire ?
- Qu'apporte le réseau à un utilisateur, si chaque bibliothèque contient déjà tout ?

**Dépôt :** fiches au fil de l'eau, Moodle, base « Fiches » (groupes Babel 3). Au plus tard jeudi 5 novembre à 11h30.

---

## Livrable 2 : Fonctions du moteur

**À rendre :** la liste des fonctions que le moteur doit fournir à la bibliothèque. Une fiche par fonction : **ses entrées, sa sortie, les erreurs possibles, ce qu'elle garantit**.

On raisonne en **besoins**, pas en algorithme : personne ne demande comment le moteur calcule.

**Pistes de réflexion**
- Reprenez vos user stories une par une : laquelle a besoin du moteur, et pour lui demander quoi ?
- Une adresse peut dépasser 3 000 caractères. Qui vérifie qu'elle est valide, et que se passe-t-il si elle est mal écrite ? Une même page peut-elle s'écrire de plusieurs façons ?
- Un utilisateur cherche « Où est passé l'été ? ». Que donnez-vous au moteur ? Et pour un texte plus long qu'une page ?
- Quels modes de recherche proposer, et que doit renvoyer le moteur pour chacun ?
- Pour passer à la page suivante, au volume ou à l'étagère d'à côté, a-t-on besoin du moteur ?
- Que doit garantir le moteur pour qu'un marque-page affiche encore la même page dans un an ?

**Dépôt :** fiches au fil de l'eau, Moodle, base « Fiches » (groupes Babel 3). Au plus tard jeudi 5 novembre à 11h30.

---

## Livrable 3 : MLD (modèle logique de données)

**À rendre :** le modèle logique de la base de données : **entités, attributs, identifiants, associations et cardinalités**.

**Dépôt :** une seule fiche par groupe, avec **l'export PNG et le fichier `.drawio`**. Un membre du groupe crée la fiche et peut la modifier jusqu'à jeudi 5 novembre à 11h30.

Outil conseillé : [draw.io](https://app.diagrams.net/) (gratuit, sans compte).

**Pistes de réflexion**
- Où se trouve une page avant que quelqu'un ne la demande ?
- Que faut-il stocker pour un marque-page ? Et surtout, que ne faut-il pas stocker ?
- Un lien court, un partage public ou privé, une collection de pages : qu'est-ce qui les distingue dans la base ?
- Comment enregistrer un marque-page vers une page d'une autre bibliothèque ? Que se passe-t-il si l'adresse de cette bibliothèque change ?
- Que garder des autres bibliothèques du réseau, et pour combien de temps ?
- La clé secrète du moteur a-t-elle sa place dans la base ?

---

## Livrable 4 : Architecture (diagramme de conteneurs C4)

**À rendre :** un diagramme de conteneurs, au sens du [modèle C4](https://c4model.com/). Une boîte par élément qui s'exécute ou stocke des données, une flèche par appel, **étiquetée : qui appelle qui, pour quoi faire, avec quelles données**. **Pas de technologie sur les boîtes** à ce stade. draw.io propose les formes C4.

**Dépôt :** une seule fiche par groupe, avec **l'export PNG et le fichier `.drawio`**. Un membre du groupe crée la fiche et peut la modifier jusqu'à jeudi 5 novembre à 11h30.

Outil conseillé : [draw.io](https://app.diagrams.net/) (gratuit, sans compte).

**Pistes de réflexion**
- Le moteur est-il un service à part, ou du code dans votre serveur ? Qui a le droit de l'appeler ?
- Où vit la clé secrète du moteur, et qui la voit passer ?
- De quoi l'interface a-t-elle besoin ? De quoi les autres bibliothèques ont-elles besoin ? Passent-elles par la même porte ?
- Comment une bibliothèque se présente-t-elle au réseau, et comment trouve-t-elle les autres ?
- Que veut dire « chercher sur le réseau » ? Qui interroge qui, dans quel ordre, et qui attend qui ?
- Que se passe-t-il si une bibliothèque est éteinte, lente ou répond n'importe quoi ?
- Sur quoi toutes les bibliothèques doivent-elles être d'accord pour se comprendre ? Comment vérifier qu'une bibliothèque respecte cet accord, alors que chacune affiche des textes différents ?
- Pour un échange à plusieurs acteurs, comme la recherche sur le réseau, un diagramme de séquence peut compléter le schéma.
