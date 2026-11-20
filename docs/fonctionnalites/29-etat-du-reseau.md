# 29 · L'état du réseau dans le back-office (issue #32, partie écran : A9)

## Ce qui est fait

Page Filament **État du réseau** (`/admin/reseau`, `app/Filament/Resources/Bibliotheques/`), **en lecture seule** : une ligne par bibliothèque de la table `bibliotheques`.

Colonnes : identifiant, nom, « ici » (notre bibliothèque), **état** (en ligne en vert, hors ligne en rouge, inconnu en gris), **échecs de suite**, dernier contact (« il y a 5 minutes »), URL, **clé changée le** (en rouge). Filtre par état.

## Pourquoi

- **A9** demande trois choses : le lien avec l'annuaire, les bibliothèques qui répondent, les clés qui ont changé. Les colonnes viennent du MLD (règle 6) : `etat`, `echecs_consecutifs`, `dernier_contact_le`, `cle_changee_le`.
- **Une clé changée est grave** : les adresses enregistrées vers cette bibliothèque ne désignent plus les mêmes textes. D'où la couleur.
- **Pas de bouton « bloquer »** : le CDC écarte toute liste blanche ou noire, le réseau est ouvert en lecture.
- **Lecture seule** : ces lignes sont écrites par des tâches automatiques (annuaire, contrôle des pairs), pas à la main.

## Reste à faire (attend la plateforme et le contrat)

- **#31 l'annuaire** : s'annoncer, récupérer la liste des pairs (`vue_annuaire_le`, `url_actuelle`).
- **#32 le contrôle des pairs** en tâche de fond : appeler l'identité de chaque pair (2 secondes au plus), compter les échecs (hors ligne au **3e** de suite, un 429 ne compte pas), comparer l'empreinte publiée à `empreinte_cle` et dater `cle_changee_le` si elle change.

L'écran est prêt : il montrera ces valeurs dès que les tâches les écriront.
