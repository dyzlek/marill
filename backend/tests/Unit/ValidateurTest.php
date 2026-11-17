<?php

use App\Babel\Validateur;

// F5 : valider(adresse). Référence : docs/cahier-des-charges.md, livrable 2.
// Tests unitaires : ni Laravel, ni clé du moteur. La comparaison avec le moteur est dans tests/Feature.

beforeEach(function () {
    $this->validateur = new Validateur;
});

/** Les erreurs réduites à [code, champ], plus lisibles dans les tests. */
function codes(array $erreurs): array
{
    return array_map(fn (array $e) => [$e['code'], $e['champ']], $erreurs);
}

it('accepte une adresse de page et donne son niveau', function (string $adresse) {
    $resultat = $this->validateur->valider($adresse);

    expect($resultat->valide)->toBeTrue()
        ->and($resultat->niveau)->toBe('page')
        ->and($resultat->erreurs)->toBe([]);
})->with(['0-w1-s1-v1:1', 'zz-w4-s5-v32:410', '1a2b-w2-s3-v17:205']);

it('accepte une adresse de livre (sans page)', function () {
    $resultat = $this->validateur->valider('abc-w2-s3-v4');

    expect($resultat->valide)->toBeTrue()
        ->and($resultat->niveau)->toBe('livre');
});

it('refuse une adresse de livre quand il faut une page (variante de F1)', function () {
    expect(codes($this->validateur->validerPage('abc-w2-s3-v4')->erreurs))
        ->toBe([['ERR_INVALID_ADDRESS_SYNTAX', 'page']])
        ->and($this->validateur->validerPage('abc-w2-s3-v4:1')->niveau)->toBe('page');
});

it('refuse les écritures non canoniques au lieu de les corriger', function (string $adresse, array $attendu) {
    $resultat = $this->validateur->valider($adresse);

    expect($resultat->valide)->toBeFalse()
        ->and($resultat->niveau)->toBeNull()
        ->and(codes($resultat->erreurs))->toBe($attendu);
})->with([
    'zéro devant le mur' => ['0-w04-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'mur']]],
    'W majuscule' => ['0-W4-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'adresse']]],
    'hexagone en majuscules' => ['ABC-w1-s1-v1:1', [['ERR_INVALID_HEX_CHARS', 'hexagone']]],
    'espace final' => ['0-w1-s1-v1:1 ', [['ERR_INVALID_ADDRESS_SYNTAX', 'page']]],
    'espace au début' => [' 0-w1-s1-v1:1', [['ERR_INVALID_HEX_CHARS', 'hexagone']]],
    'saut de ligne final' => ["0-w1-s1-v1:1\n", [['ERR_INVALID_ADDRESS_SYNTAX', 'page']]],
    'zéro devant l\'hexagone' => ['00-w1-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'hexagone']]],
    'signe plus' => ['0-w+1-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'mur']]],
    'chiffre pleine largeur' => ['0-w１-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'mur']]],
]);

it('signale chaque type d\'erreur sur le bon champ', function (string $adresse, array $attendu) {
    expect(codes($this->validateur->valider($adresse)->erreurs))->toBe($attendu);
})->with([
    'texte quelconque' => ['bonjour', [['ERR_INVALID_ADDRESS_SYNTAX', 'adresse']]],
    'vide' => ['', [['ERR_INVALID_ADDRESS_SYNTAX', 'adresse']]],
    'partie manquante' => ['0-w1-s1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'adresse']]],
    'tiret dans l\'hexagone' => ['a-b-w1-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'adresse']]],
    'mur vide' => ['0-w-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'mur']]],
    'hexagone vide' => ['-w1-s1-v1:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'hexagone']]],
    'volume vide' => ['0-w1-s1-v:1', [['ERR_INVALID_ADDRESS_SYNTAX', 'volume']]],
    'page vide' => ['0-w1-s1-v1:', [['ERR_INVALID_ADDRESS_SYNTAX', 'page']]],
    'deux pages' => ['0-w1-s1-v1:1:2', [['ERR_INVALID_ADDRESS_SYNTAX', 'page']]],
    'caractère interdit dans l\'hexagone' => ['a_b-w1-s1-v1:1', [['ERR_INVALID_HEX_CHARS', 'hexagone']]],
    'page 0' => ['zzz-w1-s1-v1:0', [['ERR_OUT_OF_BOUNDS', 'page']]],
    'nombre géant' => ['0-w1-s1-v99999999999999999999:1', [['ERR_OUT_OF_BOUNDS', 'volume']]],
]);

it('renvoie toutes les erreurs en une fois, au plus une par partie, dans l\'ordre', function () {
    expect(codes($this->validateur->valider('0-w5-s6-v33:411')->erreurs))->toBe([
        ['ERR_OUT_OF_BOUNDS', 'mur'],
        ['ERR_OUT_OF_BOUNDS', 'etagere'],
        ['ERR_OUT_OF_BOUNDS', 'volume'],
        ['ERR_OUT_OF_BOUNDS', 'page'],
    ])->and(codes($this->validateur->valider('Ab-w9-s1-v1:01')->erreurs))->toBe([
        ['ERR_INVALID_HEX_CHARS', 'hexagone'],
        ['ERR_OUT_OF_BOUNDS', 'mur'],
        ['ERR_INVALID_ADDRESS_SYNTAX', 'page'],
    ]);
});

it('donne un message en français sans recopier l\'adresse reçue', function () {
    $erreur = $this->validateur->valider('<script>-w1-s1-v1:1')->erreurs[0];

    expect($erreur['message'])->toBeString()->not->toBe('')
        ->and($erreur['message'])->not->toContain('<script>');
});

describe('le bout de la bibliothèque', function () {
    it('connaît le dernier hexagone : 3 004 chiffres en base 36', function () {
        expect(Validateur::dernierHexagone())->toHaveLength(3004)
            ->toMatch('/^[1-9a-z][0-9a-z]*$/');
    });

    it('n\'a qu\'une page dans le dernier hexagone', function () {
        $dernier = Validateur::dernierHexagone();

        expect($this->validateur->valider("$dernier-w1-s1-v1:1")->valide)->toBeTrue()
            ->and($this->validateur->valider("$dernier-w1-s1-v1")->valide)->toBeTrue()
            ->and(codes($this->validateur->valider("$dernier-w1-s1-v1:2")->erreurs))
            ->toBe([['ERR_OUT_OF_BOUNDS', 'adresse']])
            ->and(codes($this->validateur->valider("$dernier-w2-s1-v1:1")->erreurs))
            ->toBe([['ERR_OUT_OF_BOUNDS', 'adresse']]);
    });

    it('refuse un hexagone au-delà du dernier, même bien écrit', function () {
        expect(codes($this->validateur->valider(str_repeat('z', 3004).'-w1-s1-v1:1')->erreurs))
            ->toBe([['ERR_OUT_OF_BOUNDS', 'hexagone']])
            ->and(codes($this->validateur->valider(str_repeat('z', 3005).'-w1-s1-v1:1')->erreurs))
            ->toBe([['ERR_OUT_OF_BOUNDS', 'hexagone']]);
    });
});

it('ne lève jamais d\'exception, quelle que soit l\'entrée', function () {
    mt_srand(42);
    $morceaux = ['0', 'zz', '-w', '-s', '-v', ':', '1', '04', 'A', ' ', "\n", "\xff", '-', '410', '33'];
    for ($i = 0; $i < 1000; $i++) {
        $adresse = '';
        for ($j = mt_rand(0, 12); $j > 0; $j--) {
            $adresse .= $morceaux[mt_rand(0, count($morceaux) - 1)];
        }

        $resultat = $this->validateur->valider($adresse);

        expect($resultat->valide)->toBe($resultat->erreurs === []);
    }
});
