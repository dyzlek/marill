<?php

namespace Database\Factories;

use App\Enums\EtatBibliotheque;
use App\Models\Bibliotheque;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Une autre bibliothèque du réseau. Pour la nôtre : ->locale().
 *
 * @extends Factory<Bibliotheque>
 */
class BibliothequeFactory extends Factory
{
    public function definition(): array
    {
        $numero = fake()->unique()->numberBetween(1, 99);

        return [
            'identifiant' => 'babel-pair-'.$numero,
            'nom' => 'Bibliothèque '.fake()->city(),
            'description' => fake()->optional()->sentence(),
            'url_actuelle' => 'https://babel-'.$numero.'.example.org',
            'est_locale' => false,
            'etat' => fake()->randomElement(EtatBibliotheque::cases()),
            'echecs_consecutifs' => 0,
            // Une empreinte inventée : 64 caractères hexadécimaux, comme celle que publie un pair (B6).
            'empreinte_cle' => hash('sha256', fake()->uuid()),
        ];
    }

    /** Notre bibliothèque (règle 5 : une seule à la fois). */
    public function locale(): static
    {
        return $this->state(fn (array $attributes) => [
            'est_locale' => true,
            'etat' => EtatBibliotheque::EnLigne,
        ]);
    }
}
