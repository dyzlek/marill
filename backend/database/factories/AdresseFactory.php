<?php

namespace Database\Factories;

use App\Models\Adresse;
use App\Models\Bibliotheque;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Une adresse de page valide, avec un petit hexagone (les vraies vont jusqu'à 3 004 caractères ;
 * pour en tirer une au hasard : App\Babel\Hasard, puis Adresse::enregistrer()).
 *
 * @extends Factory<Adresse>
 */
class AdresseFactory extends Factory
{
    public function definition(): array
    {
        // base_convert écrit le nombre en base 36, en minuscules, sans zéro devant : la forme valide.
        $hexagone = base_convert((string) fake()->numberBetween(0, 36 ** 5), 10, 36);
        $mur = fake()->numberBetween(1, 4);
        $etagere = fake()->numberBetween(1, 5);
        $volume = fake()->numberBetween(1, 32);
        $page = fake()->numberBetween(1, 410);
        $livre = "{$hexagone}-w{$mur}-s{$etagere}-v{$volume}";

        return [
            'bibliotheque_id' => Bibliotheque::factory(),
            'hexagone' => $hexagone,
            'mur' => $mur,
            'etagere' => $etagere,
            'volume' => $volume,
            'page' => $page,
            'empreinte' => Adresse::empreinteDe("{$livre}:{$page}"),
            'empreinte_livre' => Adresse::empreinteDe($livre),
        ];
    }

    /** Le livre entier (règle 4) : pas de page, et l'empreinte est celle du livre. */
    public function livre(): static
    {
        return $this->state(fn (array $attributes) => [
            'page' => null,
            'empreinte' => $attributes['empreinte_livre'],
        ]);
    }
}
