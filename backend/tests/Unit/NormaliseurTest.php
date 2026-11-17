<?php

use App\Babel\Normaliseur;
use Babel\Moteur\Moteur;

// F3 : normaliser(texte). Référence : docs/cahier-des-charges.md, livrable 2.
// Tests unitaires : pas besoin de démarrer Laravel ni la clé du moteur, seulement la constante ALPHABET.

beforeEach(function () {
    $this->normaliseur = new Normaliseur;
});

/** Vrai si le texte n'utilise que les 29 symboles du moteur. */
function seulementAlphabet(string $texte): bool
{
    return preg_match('/^['.preg_quote(Moteur::ALPHABET, '/').']*$/u', $texte) === 1;
}

it('suit l\'exemple du cahier des charges', function () {
    expect($this->normaliseur->normaliser('Où est passé l\'été ?'))->toBe('ou est passe l ete');
});

it('applique les cinq étapes', function (string $entree, string $attendu) {
    expect($this->normaliseur->normaliser($entree))->toBe($attendu);
})->with([
    'minuscules' => ['BONJOUR Monde', 'bonjour monde'],
    'accents et cédille' => ['Ça, c\'est déjà Noël', 'ca, c est deja noel'],
    'majuscules accentuées' => ['ÉCOLE À ÇA', 'ecole a ca'],
    'lettres liées' => ['Œuvre, cœur, Ætna, æther', 'oeuvre, coeur, aetna, aether'],
    'virgule et point gardés' => ['un, deux. trois', 'un, deux. trois'],
    'chiffres et ponctuation hors alphabet' => ['Page 42 ! (vraiment ?)', 'page vraiment'],
    'sauts de ligne et tabulations' => ["ligne un\nligne\tdeux\r\n", 'ligne un ligne deux'],
    'espaces qui se suivent' => ['a    b', 'a b'],
    'espaces des bords' => ['   au milieu   ', 'au milieu'],
    'emoji' => ['salut 👋 toi', 'salut toi'],
    'lettre hors règle (pas un accent)' => ['straße', 'stra e'],
    'texte vide' => ['', ''],
    'rien de cherchable' => ['?!# 123', ''],
]);

it('donne le même résultat si on normalise deux fois', function (string $entree) {
    $une = $this->normaliseur->normaliser($entree);

    expect($this->normaliseur->normaliser($une))->toBe($une);
})->with([
    'Où est passé l\'été ?',
    "  Œuvre\n\n  complète, ÉTÉ.  ",
    'straße 👋 ½ ²',
    '',
]);

it('accepte toute chaîne, même invalide, et ne sort que les 29 symboles', function () {
    mt_srand(42); // tirage reproductible
    for ($i = 0; $i < 500; $i++) {
        $octets = '';
        for ($j = mt_rand(0, 300); $j > 0; $j--) {
            $octets .= chr(mt_rand(0, 255)); // octets au hasard : souvent de l'UTF-8 invalide
        }

        $sortie = $this->normaliseur->normaliser($octets);

        expect(seulementAlphabet($sortie))->toBeTrue()
            ->and($this->normaliseur->normaliser($sortie))->toBe($sortie);
    }
});

describe('mode page (étapes 1 à 3 seulement)', function () {
    it('ne réduit pas les espaces et ne retire pas ceux des bords', function () {
        expect($this->normaliseur->normaliserPage("  A  b\n"))->toBe('  a  b ');
    });

    it('remplace chaque caractère hors alphabet par une seule espace', function () {
        expect($this->normaliseur->normaliserPage('a?!b'))->toBe('a  b');
    });

    it('laisse intacte une page du moteur', function () {
        $alphabet = mb_str_split(Moteur::ALPHABET);
        $page = '';
        for ($i = 0; $i < Moteur::LONGUEUR_PAGE; $i++) {
            $page .= $alphabet[random_int(0, count($alphabet) - 1)];
        }

        expect($this->normaliseur->normaliserPage($page))->toBe($page);
    });
});
