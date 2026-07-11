<?php

namespace Database\Factories;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_gasto_id' => fn () => CategoriaGasto::firstOrCreate(
                ['codigo' => 'otros'],
                ['nombre' => 'Otros', 'requiere_propiedad' => false, 'requiere_cuarto' => false, 'activo' => true],
            )->id,
            'propiedad_id' => null,
            'cuarto_id' => null,
            'fecha' => now()->subDays(fake()->numberBetween(0, 120))->toDateString(),
            'monto' => fake()->numberBetween(50, 1500),
            'descripcion' => ucfirst(fake()->words(3, true)),
            'proveedor' => fake()->optional()->company(),
            'metodo_pago' => Gasto::METODO_EFECTIVO,
            'comprobante_path' => null,
            'notas' => null,
        ];
    }
}
