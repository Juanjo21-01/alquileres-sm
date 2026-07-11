<?php

namespace Database\Factories;

use App\Models\Inquilino;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquilino>
 */
class InquilinoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'dpi' => fake()->optional()->numerify('#############'),
            'telefono' => fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'ocupacion' => fake()->randomElement(['estudiante', 'salud', 'otro']),
            'institucion' => fake()->optional()->company(),
            'activo' => true,
        ];
    }
}
