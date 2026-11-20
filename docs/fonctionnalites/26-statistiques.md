# 26 · Statistiques par jour (issue #30 : A11, règle 21)

## Ce qui est fait

**Les compteurs** : `ActiviteJour::compter('pages_lues' | 'recherches')`, appelé par :

- `GET /api/pages/{adresse}` et `GET /api/hasard` → `pages_lues` ;
- `POST /api/recherche` → `recherches` (membres et visiteurs).

**Les chiffres** : `App\Babel\Statistiques` :

| Méthode | Source |
|---|---|
| `comptes()` | `users` |
| `membresActifs()` | connectés dans les 30 derniers jours (`derniere_connexion_le`) |
| `pagesLues(7)`, `recherches(7)`, `pagesLuesParJour(7)` | `activites_jour` |
| `signalementsEnAttente()` | `signalements` |
| `pagesLesPlusPartagees(5)` | adresses dont les liens courts ont le plus de visites |

**L'écran** : sur le tableau de bord de `/admin`, deux widgets Filament : `StatistiquesBibliotheque` (4 chiffres, avec un petit graphique des pages lues sur 7 jours) et `PagesLesPlusPartagees` (tableau).

## Pourquoi

- **Règle 21 : des compteurs par jour, pas une ligne par lecture.** Une ligne par page lue ferait des millions de lignes, et des données personnelles (qui a lu quoi) pour rien.
- **`upsert` pour compter** : **une** requête qui crée la ligne du jour si elle manque, sinon fait `+ 1` dans la base. Deux lectures simultanées ne perdent pas de visite, ce qui arriverait avec « lire 41, écrire 42 » fait à deux.
- **Une page au hasard compte comme une page lue** : le visiteur la lit.
- **« Actif » = connecté dans les 30 derniers jours** : le CDC ne le définit pas. **Choix du groupe**, une constante (`Statistiques::JOURS_ACTIF`).
- **« Pages les plus partagées » = visites des liens courts** : un lien court est la seule trace d'un partage, et `visites` existe déjà (règle 11).
- **Le calcul à part des widgets** : on teste les chiffres sans écran, l'écran ne fait qu'afficher.

## Tests

`tests/Feature/Api/HistoriqueEtStatistiquesTest.php`, bloc « A11 » : lire une page ajoute 1 au compteur du jour sans créer d'autre ligne ; les chiffres du tableau de bord (la ligne d'il y a 30 jours n'entre pas dans les 7 jours).
