<?php

use App\Babel\Validateur;
use Babel\Moteur\Erreur\ErreurMoteur;
use Babel\Moteur\Moteur;

// F5 face au moteur fourni. Le moteur renvoie déjà toutes les erreurs d'une adresse de page :
// il sert d'oracle. validerPage() (la variante de F1, qui refuse un livre) doit dire exactement la même chose que lire().
// Ce test a besoin de BABEL_KEY (dans le .env, ou générée par la CI).

/** Ce que dit le moteur : [] si la page se lit, sinon les erreurs réduites à [code, champ]. */
function avisDuMoteur(Moteur $moteur, string $adresse): array
{
    try {
        $moteur->lire($adresse);

        return [];
    } catch (ErreurMoteur $e) {
        return array_map(fn (array $x) => [$x['code'], $x['champ']], $e->versTableau());
    }
}

it('dit la même chose que le moteur sur des milliers d\'adresses de page', function () {
    $moteur = app(Moteur::class);
    $validateur = app(Validateur::class);
    $dernier = Validateur::dernierHexagone();

    $hexagones = ['0', '1', 'zz', '0a', '0A', 'Ab', 'a_b', '', ' ', '00', $dernier, $dernier.'0', str_repeat('z', 3004)];
    $nombres = ['1', '2', '4', '5', '6', '32', '33', '410', '411', '0', '04', '+1', '', ' 1', 'x', '99999999999999999999'];

    mt_srand(42);
    $tire = fn (array $liste) => $liste[mt_rand(0, count($liste) - 1)];
    for ($i = 0; $i < 2000; $i++) {
        $adresse = $tire($hexagones).'-w'.$tire($nombres).'-s'.$tire($nombres).'-v'.$tire($nombres).':'.$tire($nombres);

        $erreurs = array_map(fn (array $e) => [$e['code'], $e['champ']], $validateur->validerPage($adresse)->erreurs);

        expect($erreurs)->toBe(avisDuMoteur($moteur, $adresse), "adresse : $adresse");
    }
});

it('dit la même chose que le moteur sur des adresses abîmées au hasard', function () {
    $moteur = app(Moteur::class);
    $validateur = app(Validateur::class);
    $caracteres = ['0', '1', '9', 'a', 'z', 'A', 'W', '-', ':', 'w', 's', 'v', ' ', "\n", '_'];

    mt_srand(7);
    for ($i = 0; $i < 2000; $i++) {
        $adresse = 'k3x-w2-s4-v17:205';
        for ($j = mt_rand(1, 3); $j > 0; $j--) {
            $position = mt_rand(0, strlen($adresse));
            $adresse = match (mt_rand(0, 2)) {
                0 => substr($adresse, 0, $position).$caracteres[mt_rand(0, count($caracteres) - 1)].substr($adresse, $position),
                1 => substr($adresse, 0, $position).substr($adresse, $position + 1),
                2 => substr($adresse, 0, $position).$caracteres[mt_rand(0, count($caracteres) - 1)].substr($adresse, $position + 1),
            };
        }
        $erreurs = array_map(fn (array $e) => [$e['code'], $e['champ']], $validateur->validerPage($adresse)->erreurs);

        expect($erreurs)->toBe(avisDuMoteur($moteur, $adresse), 'adresse : '.json_encode($adresse));
    }
});

it('rend lisible par lire() toute adresse de page qu\'il accepte', function () {
    $moteur = app(Moteur::class);
    $dernier = Validateur::dernierHexagone();

    foreach (['0-w1-s1-v1:1', 'zz-w4-s5-v32:410', "$dernier-w1-s1-v1:1"] as $adresse) {
        expect(app(Validateur::class)->valider($adresse)->valide)->toBeTrue()
            ->and($moteur->lire($adresse))->toHaveLength(Moteur::LONGUEUR_PAGE);
    }
});
