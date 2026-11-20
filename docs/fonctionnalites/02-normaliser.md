# 02 · F3 normaliser (issue #8)

## Ce qui est fait

`App\Babel\Normaliseur` transforme n'importe quel texte en texte écrit avec les **29 symboles** du moteur (a–z, espace, virgule, point).

- `normaliser($texte)` : les **5 étapes** du CDC, pour les modes `exact` et `bruit` ;
- `normaliserPage($texte)` : seulement les **étapes 1 à 3**, pour le mode `page`.

Les 5 étapes : 1. minuscules (Unicode, `É` → `é`) ; 2. accents et cédille retirés (`é` → `e`), lettres liées développées (`œ` → `oe`) ; 3. tout autre caractère devient une espace, saut de ligne compris ; 4. espaces répétées réduites à une ; 5. espaces du début et de la fin retirées.

## Comment

- Étape 2 : `Normalizer::normalize(…, FORM_D)` (extension **intl**) sépare chaque lettre de son accent, puis `\p{Mn}` retire les accents. `œ` et `æ` ne sont pas des lettres accentuées : on les remplace à la main.
- Étape 3 : l'alphabet vient de `Moteur::ALPHABET`. On ne le recopie pas, il n'y a qu'**une source de vérité**.
- `mb_scrub` d'abord : un texte qui n'est pas de l'UTF-8 valide ne fait pas planter la fonction.
- `remplacer()` lève une exception si `preg_replace` renvoie `null` : mieux vaut une erreur 500 visible qu'un texte faux en silence.

## Pourquoi

- **La règle fait partie du contrat commun.** Si une bibliothèque normalise autrement, la même phrase ne mène pas à la même adresse sur le réseau.
- **Le serveur normalise toujours**, même quand la demande vient d'une autre bibliothèque : on ne fait pas confiance à l'entrée.
- **Mode page : pas d'étapes 4 et 5.** Une page du moteur peut commencer par une espace ou en contenir deux à la suite. Les retirer donnerait un autre texte, donc une autre adresse.
- Une classe sans dépendance : Laravel la fabrique tout seul (autowiring) quand un contrôleur la demande en paramètre.

## Tests

`tests/Unit/NormaliseurTest.php` : accents, ligatures, sauts de ligne, espaces multiples, texte vide, octets invalides, différence entre les modes.
