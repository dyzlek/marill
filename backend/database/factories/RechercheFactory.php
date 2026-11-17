<?php

namespace Database\Factories;

use App\Enums\ModeRecherche;
use App\Enums\PorteeRecherche;
use App\Models\Recherche;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Recherche> */
class RechercheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'texte' => fake()->words(3, true),
            'mode' => fake()->randomElement(ModeRecherche::cases()),
            'portee' => fake()->randomElement(PorteeRecherche::cases()),
        ];
    }
}
