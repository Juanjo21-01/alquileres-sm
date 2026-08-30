<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasRenderizanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guardia de la Fase 6C: los índices CRUD simples deben renderizar con los
     * componentes compartidos (page-header, filtros-card, empty-state, skeleton)
     * y el header/toggle del layout, incluso con las tablas vacías.
     */
    public function test_los_indices_crud_renderizan_para_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->get(route('propiedades.index'))->assertOk();
        $this->get(route('categorias.index'))->assertOk();
        $this->get(route('usuarios.index'))->assertOk();
        $this->get(route('inquilinos.index'))->assertOk();
    }

    /**
     * Cobertura del barrido global de botones (ghost → outline): los índices con
     * más botones/filtros deben seguir renderizando.
     */
    public function test_los_indices_principales_renderizan_para_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('estancias.index'))->assertOk();
        $this->get(route('pagos.index'))->assertOk();
        $this->get(route('parqueo.index'))->assertOk();
        $this->get(route('gastos.index'))->assertOk();
    }

    /**
     * Reportes (solo admin): deben renderizar con el page-header y las tablas/gráficos.
     */
    public function test_los_reportes_renderizan_para_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->get(route('reportes.flujo'))->assertOk();
        $this->get(route('reportes.ocupacion'))->assertOk();
        $this->get(route('reportes.parqueo'))->assertOk();
        $this->get(route('reportes.inquilinos'))->assertOk();
    }
}
