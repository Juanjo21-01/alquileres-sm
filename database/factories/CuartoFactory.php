<?php

namespace Database\Factories;

use App\Models\Cuarto;
use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuarto>
 */
class CuartoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'propiedad_id' => Propiedad::factory(),
            'codigo' => strtoupper(fake()->unique()->bothify('?-###')),
            'nivel' => fake()->numberBetween(1, 3),
            'tamano' => fake()->randomElement(['individual', 'doble', 'suite']),
            'precio_base' => fake()->numberBetween(600, 2000),
            'estado' => Cuarto::ESTADO_DISPONIBLE,
            'descripcion' => null,
            'activo' => true,
        ];
    }

    public function ocupado(): static
    {
        return $this->state(fn (): array => ['estado' => Cuarto::ESTADO_OCUPADO]);
    }
}
