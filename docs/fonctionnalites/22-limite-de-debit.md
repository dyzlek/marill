# 22 · Limite de débit de l'API publique (issue #26 : A10)

## Ce qui est fait

Deux **limites nommées**, déclarées dans `AppServiceProvider::configurerLimitesDeDebit()` :

| Nom | Routes | Quota par défaut | Réglage |
|---|---|---|---|
| `api-publique` | tout ce qui est ouvert sans compte : lire une page, chercher, identité, hasard, annotations d'une page, ouvrir un lien court, collections publiques, pages populaires | **60 / minute** | `quota_api_par_minute` |
| `publications` | en plus, créer un lien court et signaler | **10 / minute** | `quota_publications_par_minute` |

- Les quotas se changent dans le back-office ([21](21-configurer-la-bibliotheque.md)), sans redéployer.
- On compte **par membre** s'il est connecté, sinon **par adresse IP**.
- Au-delà : **429 `ERR_TOO_MANY_REQUESTS`** au format commun, avec l'en-tête **`Retry-After`** (traduction déjà faite dans `bootstrap/app.php`).
- Les anciennes limites provisoires (`throttle:10,1,liens-courts`, `throttle:10,1,signalements`) sont remplacées.
- `POST /api/tokens` garde sa propre limite fixe (5 / minute) : c'est une protection contre les essais de mots de passe, pas un quota.

## Pourquoi

- **CDC (choix 4 de l'architecture)** : « le reste est protégé par la limite de débit », et « la même limite protège les actions sans compte : création de liens courts et signalements ». D'où un compteur commun aux deux actions qui écrivent.
- **Deux compteurs plutôt qu'un** : lire beaucoup de pages ne doit pas empêcher de signaler un contenu choquant.
- **Une limite nommée = un compteur partagé** entre toutes les routes qui l'utilisent. Avec `throttle:60,1`, chaque route aurait son propre compteur (ou un compteur partagé par hasard, comme on l'avait vu à l'issue #22).
- **Par membre si connecté** : deux membres derrière la même box (même IP) ne se gênent pas.
- **Une autre bibliothèque a une seule IP** : elle partage un compteur pour tous ses visiteurs. Le quota par défaut (60 / min) lui laisse relayer une recherche réseau. À ajuster quand on verra le trafic réel du réseau (le CDC ne donne aucun chiffre : **choix du groupe**).
- **Un 429 n'est pas une panne** : pour les autres bibliothèques, un pair qui répond 429 est en ligne mais occupé (CDC, choix 7). Son compteur d'échecs ne bouge pas.
- **Les routes des membres** (marque-pages, collections…) ne sont pas sous ce quota : elles demandent un compte, donc on sait qui abuserait, et l'admin peut le suspendre.

## Tests

`tests/Feature/Api/HistoriqueEtStatistiquesTest.php`, bloc « A10 » (quota lu dans les réglages, 429 + `Retry-After`, compteur par membre, routes des membres hors quota) ; `LiensCourtsTest` et `SignalementsTest` (quota des publications partagé).
