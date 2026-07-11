<?php

namespace Database\Factories;

use App\Models\Cuarto;
use App\Models\Estancia;
use App\Models\Inquilino;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estancia>
 */
class EstanciaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $precio = fake()->numberBetween(600, 2000);

        return [
            'inquilino_id' => Inquilino::factory(),
            'cuarto_id' => Cuarto::factory()->ocupado(),
            'fecha_inicio' => now()->subMonths(fake()->numberBetween(1, 6))->startOfMonth()->toDateString(),
            'fecha_fin_estimada' => null,
            'precio_acordado' => $precio,
            'anticipo' => 0,
            'estado' => Estancia::ESTADO_ACTIVA,
        ];
    }

    public function finalizada(): static
    {
        return $this->state(fn (): array => [
            'estado' => Estancia::ESTADO_FINALIZADA,
            'fecha_fin' => now()->toDateString(),
        ]);
    }
}
