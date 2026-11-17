<?php

namespace Database\Factories;

use App\Enums\StatutTexteBanni;
use App\Models\Bibliotheque;
use App\Models\TexteBanni;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TexteBanni> */
class TexteBanniFactory extends Factory
{
    public function definition(): array
    {
        // Des mots en minuscules séparés par des espaces : déjà un texte normalisé (F3).
        // word() est toujours une chaîne (words() peut rendre un tableau ou une chaîne selon ses arguments).
        $texte = collect(range(1, 4))->map(fn (): string => fake()->unique()->word())->implode(' ');

        return [
            'texte' => $texte,
            'empreinte' => TexteBanni::empreinteDe($texte),
            'motif' => fake()->optional()->sentence(),
            'origine_id' => Bibliotheque::factory(),
            'statut' => StatutTexteBanni::Applique,
            'decide_le' => now(),
        ];
    }
}
