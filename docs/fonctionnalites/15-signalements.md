# 15 · Signalements (issue #22 : V17, V18)

## Ce qui est fait

- `POST /api/signalements` (public) : **exactement un** champ parmi `annotation` (id), `collection` (id), `lien_court` (slug), plus un `motif` (≤ 1 000).
- Seul un **contenu publié** se signale : une annotation ou une collection privée reçoit la même réponse qu'un contenu inconnu.
- Auteur = membre connecté, ou `null` pour un visiteur.
- Un membre ne signale qu'une fois le même contenu : 409 `ERR_ALREADY_REPORTED` (choix du groupe).
- `GET /api/signalements` : mes signalements (sans leur statut), pour que le front cache ces contenus.
- Trait `App\Models\Concerns\Signalable` sur Annotation, Collection, LienCourt : `signalements()`, le scope `nonSignalesPar($membre)`, `estSignalePar()`.
- Le traitement par l'administrateur est la file A4 ([20](20-file-des-signalements.md)).

## Pourquoi

- **On ne signale jamais une page** (décision du CDC) : la page existe forcément, il n'y a rien à retirer. On modère ce qui est publié.
- **Trois vraies clés étrangères** plutôt qu'une cible en texte libre : la base vérifie que le contenu existe, et la cascade efface les signalements quand le contenu est supprimé (règle 13).
- **V18 sans nouvelle table** (règle 15) : « ce que j'ai signalé » se déduit de `signalements.auteur_id`. Pour un visiteur, le serveur ne sait pas qui il est : c'est son navigateur qui s'en souvient.
- **Ne pas révéler l'existence** d'un contenu privé : même réponse que pour un contenu inconnu.
- **Un trait** : le même comportement sur trois modèles, écrit une seule fois.
- Limite de débit : le quota « publications », partagé avec la création de liens courts ([22](22-limite-de-debit.md)).

## Tests

`tests/Feature/Api/SignalementsTest.php`. Bruno : dossier `Signalements`.
