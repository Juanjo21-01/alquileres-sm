<?php

namespace Database\Factories;

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bruto = fake()->numberBetween(600, 2000);

        return [
            'estancia_id' => Estancia::factory(),
            'tipo_pago_id' => fn () => TipoPago::firstOrCreate(
                ['codigo' => TipoPago::COD_MENSUALIDAD],
                ['nombre' => 'Mensualidad', 'requiere_mes' => true, 'activo' => true],
            )->id,
            'fecha_pago' => now()->toDateString(),
            'mes_aplicado' => now()->startOfMonth()->toDateString(),
            'monto_bruto' => $bruto,
            'descuento' => 0,
            'motivo_descuento' => null,
            'monto_neto' => $bruto,
            'metodo_pago' => Pago::METODO_EFECTIVO,
            'referencia' => null,
            'recibo_numero' => 'REC-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'notas' => null,
        ];
    }
}
