<?php

namespace Database\Factories;

use App\Models\Adresse;
use App\Models\Collection;
use App\Models\LienCourt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Par défaut : un lien créé par un visiteur vers une page, qui expire (règle 11).
 *
 * @extends Factory<LienCourt>
 */
class LienCourtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => Str::random(8),
            'user_id' => null,
            'adresse_id' => Adresse::factory(),
            'collection_id' => null,
            'passage' => null,
            'visites' => fake()->numberBetween(0, 50),
            'expire_le' => now()->addDays(30),
        ];
    }

    /** Créé par un membre : pas d'expiration obligatoire. */
    public function parUnMembre(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user ?? User::factory(),
            'expire_le' => null,
        ]);
    }

    /** Vers une collection au lieu d'une adresse (et donc sans passage). */
    public function versUneCollection(?Collection $collection = null): static
    {
        return $this->state(fn (array $attributes) => [
            'adresse_id' => null,
            'passage' => null,
            'collection_id' => $collection ?? Collection::factory(),
        ]);
    }
}
