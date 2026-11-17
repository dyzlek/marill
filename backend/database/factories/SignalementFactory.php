<?php

namespace Database\Factories;

use App\Enums\StatutSignalement;
use App\Models\Annotation;
use App\Models\Signalement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut : un visiteur signale une annotation, en attente de traitement.
 *
 * @extends Factory<Signalement>
 */
class SignalementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'annotation_id' => Annotation::factory()->publique(),
            'collection_id' => null,
            'lien_court_id' => null,
            'auteur_id' => null,
            'motif' => fake()->sentence(),
            'statut' => StatutSignalement::EnAttente,
        ];
    }
}
