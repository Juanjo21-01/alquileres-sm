<?php

namespace Tests\Feature;

use App\Models\Cuarto;
use App\Models\Estancia;
use App\Models\Inquilino;
use App\Models\Propiedad;
use App\Models\TipoPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ValidacionesFechaTest extends TestCase
{
    use RefreshDatabase;

    private function crearEstancia(string $fechaInicio): Estancia
    {
        $propiedad = Propiedad::create([
            'nombre' => 'Casa Test',
            'direccion' => 'Zona 1',
        ]);

        $cuarto = Cuarto::create([
            'propiedad_id' => $propiedad->id,
            'codigo' => 'A1',
            'precio_base' => 1000,
            'estado' => Cuarto::ESTADO_OCUPADO,
        ]);

        $inquilino = Inquilino::create([
            'nombres' => 'Ana',
            'apellidos' => 'López',
        ]);

        return Estancia::create([
            'inquilino_id' => $inquilino->id,
            'cuarto_id' => $cuarto->id,
            'fecha_inicio' => $fechaInicio,
            'precio_acordado' => 1000,
            'estado' => Estancia::ESTADO_ACTIVA,
        ]);
    }

    public function test_cerrar_rechaza_fecha_fin_anterior_al_inicio(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $estancia = $this->crearEstancia('2026-06-01');

        Livewire::test('estancias.form-cerrar')
            ->call('abrir', id: $estancia->id)
            ->set('fechaFin', '2026-05-15')
            ->call('guardar')
            ->assertHasErrors(['fechaFin' => 'after_or_equal']);
    }

    public function test_cerrar_acepta_fecha_fin_igual_o_posterior_al_inicio(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $estancia = $this->crearEstancia('2026-06-01');

        Livewire::test('estancias.form-cerrar')
            ->call('abrir', id: $estancia->id)
            ->set('fechaFin', '2026-06-10')
            ->call('guardar')
            ->assertHasNoErrors();
    }

    public function test_editar_rechaza_fecha_fin_estimada_anterior_al_inicio(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $estancia = $this->crearEstancia('2026-06-01');

        Livewire::test('estancias.form-editar')
            ->call('abrir', id: $estancia->id)
            ->set('fechaFinEstimada', '2026-05-15')
            ->call('guardar')
            ->assertHasErrors(['fechaFinEstimada' => 'after_or_equal']);
    }

    public function test_pago_rechaza_mes_aplicado_anterior_al_inicio(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $estancia = $this->crearEstancia('2026-06-01');

        $tipo = TipoPago::create([
            'nombre' => 'Mensualidad',
            'codigo' => TipoPago::COD_MENSUALIDAD,
            'requiere_mes' => true,
            'activo' => true,
        ]);

        Livewire::test('pagos.form')
            ->call('abrir', estanciaId: $estancia->id)
            ->set('tipoPagoId', $tipo->id)
            ->set('mesAplicado', '2026-05-01')
            ->set('montoBruto', '100')
            ->call('guardar')
            ->assertHasErrors(['mesAplicado' => 'after_or_equal']);
    }
}
