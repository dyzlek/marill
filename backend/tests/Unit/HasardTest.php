<?php

use App\Babel\Hasard;
use App\Babel\Validateur;
use Babel\Moteur\Moteur;

// F7 : auHasard(). Référence : docs/cahier-des-charges.md, livrable 2.
// Sans graine, on ne teste pas une valeur : on teste une distribution, sur 10 000 tirages.

/**
 * Les 10 000 tirages, faits une seule fois pour tous les tests de distribution.
 * On ne garde pas les adresses entières (3 000 caractères chacune, la mémoire de PHP n'y suffirait pas) :
 * seulement la longueur de l'hexagone, les quatre nombres, une empreinte de l'adresse pour repérer
 * les doublons, et le verdict de validerPage pour les 2 000 premières.
 */
function tirages(): array
{
    static $parties = null;
    if ($parties !== null) {
        return $parties;
    }

    $hasard = new Hasard;
    $validateur = new Validateur;
    $parties = [];
    for ($i = 0; $i < 10_000; $i++) {
        $adresse = $hasard->auHasard();
        preg_match('/\A([0-9a-z]+)-w(\d+)-s(\d+)-v(\d+):(\d+)\z/', $adresse, $m);
        $parties[] = [
            'empreinte' => md5($adresse),
            'longueur' => strlen($m[1]),
            'mur' => (int) $m[2],
            'etagere' => (int) $m[3],
            'volume' => (int) $m[4],
            'page' => (int) $m[5],
            'valide' => $i < 2_000 ? $validateur->validerPage($adresse)->niveau === 'page' : null,
        ];
    }

    return $parties;
}

/**
 * Test du khi-deux : chaque valeur de 1 à $nombre doit sortir à peu près aussi souvent.
 * Seuil large (moyenne + 6 écarts-types) : un générateur correct ne le dépasse pour ainsi dire jamais,
 * alors qu'une valeur oubliée ou deux fois trop fréquente le dépasse de très loin.
 */
function khiDeux(array $valeurs, int $nombre): array
{
    $attendu = count($valeurs) / $nombre;
    $comptes = array_count_values($valeurs) + array_fill_keys(range(1, $nombre), 0);
    $khi = 0.0;
    foreach (range(1, $nombre) as $v) {
        $khi += ($comptes[$v] - $attendu) ** 2 / $attendu;
    }
    $libertes = $nombre - 1;

    return [$khi, $libertes + 6 * sqrt(2 * $libertes), count(array_diff_key($comptes, array_fill_keys(range(1, $nombre), 0)))];
}

it('renvoie une adresse de page que valider accepte', function () {
    $verdicts = array_slice(array_column(tirages(), 'valide'), 0, 2_000);

    expect(array_unique($verdicts))->toBe([true]);
});

it('tire les murs, étagères, volumes et pages à parts égales', function (string $champ, int $nombre) {
    [$khi, $seuil, $horsLimites] = khiDeux(array_column(tirages(), $champ), $nombre);

    expect($horsLimites)->toBe(0)
        ->and($khi)->toBeLessThan($seuil);
})->with([
    'murs' => ['mur', Moteur::MURS],
    'étagères' => ['etagere', Moteur::ETAGERES],
    'volumes' => ['volume', Moteur::VOLUMES],
    'pages' => ['page', Moteur::PAGES],
]);

it('tire des hexagones au nom très long, comme le veut l\'uniformité', function () {
    $longueurs = array_count_values(array_column(tirages(), 'longueur'));
    $part = fn (int $longueur) => ($longueurs[$longueur] ?? 0) / 10_000;
    $plusCourts = 1 - $part(3004) - $part(3003);

    // Attendu : 79,0 % / 20,4 % / 0,6 %. Marges de plus de 5 écarts-types.
    expect($part(3004))->toBeGreaterThan(0.77)->toBeLessThan(0.81)
        ->and($part(3003))->toBeGreaterThan(0.185)->toBeLessThan(0.225)
        ->and($plusCourts)->toBeLessThan(0.01);
});

it('ne repasse jamais deux fois par la même adresse en 10 000 tirages', function () {
    // Sur 29^3200 pages, un doublon voudrait dire que le tirage n'est pas aléatoire (graine fixe, par exemple).
    expect(count(array_unique(array_column(tirages(), 'empreinte'))))->toBe(10_000);
});

it('n\'est pas reproductible : deux générateurs neufs ne donnent pas la même suite', function () {
    expect((new Hasard)->auHasard())->not->toBe((new Hasard)->auHasard());
});

describe('découpage d\'un numéro de page en adresse', function () {
    it('donne la première page pour le numéro 0', function () {
        expect((new Hasard)->adresseDuNumero(gmp_init(0)))->toBe('0-w1-s1-v1:1');
    });

    it('passe au volume suivant après la page 410, comme la lecture d\'un livre', function () {
        $hasard = new Hasard;

        expect($hasard->adresseDuNumero(gmp_init(409)))->toBe('0-w1-s1-v1:410')
            ->and($hasard->adresseDuNumero(gmp_init(410)))->toBe('0-w1-s1-v2:1')
            ->and($hasard->adresseDuNumero(gmp_init(262_399)))->toBe('0-w4-s5-v32:410')
            ->and($hasard->adresseDuNumero(gmp_init(262_400)))->toBe('1-w1-s1-v1:1');
    });

    it('donne la page unique du dernier hexagone pour le dernier numéro', function () {
        $dernierNumero = gmp_sub(gmp_pow(strlen(Moteur::ALPHABET), Moteur::LONGUEUR_PAGE), 1);

        expect((new Hasard)->adresseDuNumero($dernierNumero))->toBe(Validateur::dernierHexagone().'-w1-s1-v1:1');
    });
});
