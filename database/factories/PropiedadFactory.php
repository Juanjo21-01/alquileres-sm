<?php

namespace Database\Factories;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Propiedad>
 */
class PropiedadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Casa '.fake()->unique()->firstName(),
            'direccion' => fake()->streetAddress(),
            'zona' => 'Zona '.fake()->numberBetween(1, 5),
            'referencia' => fake()->optional()->sentence(4),
            'notas' => null,
            'activo' => true,
        ];
    }
}
