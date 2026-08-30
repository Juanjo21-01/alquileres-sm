<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FiltrosStagedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Los filtros de selects/fechas son borradores (fCategoria, fMetodo, ...):
     * NO deben aplicarse a la consulta hasta pulsar "Buscar", y "Limpiar" reinicia todo.
     */
    public function test_gastos_aplica_filtros_solo_al_buscar(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('gastos.tabla')
            ->set('fCategoria', '5')
            ->set('fMetodo', 'efectivo')
            ->assertSet('categoriaId', '')   // borrador aún no aplicado
            ->assertSet('metodo', '')
            ->call('buscar')
            ->assertSet('categoriaId', '5')  // aplicado tras Buscar
            ->assertSet('metodo', 'efectivo')
            ->call('limpiar')
            ->assertSet('categoriaId', '')   // Limpiar reinicia aplicado y borrador
            ->assertSet('fCategoria', '')
            ->assertSet('metodo', '');
    }

    public function test_pagos_aplica_filtros_solo_al_buscar(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('pagos.tabla')
            ->set('fTipo', '3')
            ->set('fMetodo', 'cuenta')
            ->assertSet('tipoId', '')
            ->assertSet('metodo', '')
            ->call('buscar')
            ->assertSet('tipoId', '3')
            ->assertSet('metodo', 'cuenta')
            ->call('limpiar')
            ->assertSet('tipoId', '')
            ->assertSet('fTipo', '')
            ->assertSet('metodo', '');
    }

    public function test_parqueo_aplica_filtro_estado_solo_al_buscar(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('parqueo.tabla-arrendatarios')
            ->set('fEstado', 'todos')
            ->set('fOcupacion', 'salud')
            ->assertSet('estadoFiltro', 'activos')   // sigue el default hasta Buscar
            ->assertSet('ocupacion', '')
            ->call('buscar')
            ->assertSet('estadoFiltro', 'todos')
            ->assertSet('ocupacion', 'salud')
            ->call('limpiar')
            ->assertSet('estadoFiltro', 'activos')   // vuelve al default
            ->assertSet('fEstado', 'activos')
            ->assertSet('ocupacion', '');
    }

    public function test_estancias_aplica_filtros_solo_al_buscar(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('estancias.tabla')
            ->set('fEstado', 'activa')
            ->set('fPropiedad', '2')
            ->assertSet('estado', '')
            ->assertSet('propiedadId', '')
            ->call('buscar')
            ->assertSet('estado', 'activa')
            ->assertSet('propiedadId', '2')
            ->call('limpiar')
            ->assertSet('estado', '')
            ->assertSet('fEstado', '')
            ->assertSet('propiedadId', '');
    }

    public function test_inquilinos_aplica_filtros_solo_al_buscar(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('inquilinos.tabla')
            ->set('fOcupacion', 'estudiante')
            ->set('fEstancia', 'con')
            ->assertSet('ocupacion', '')
            ->assertSet('estanciaFiltro', '')
            ->call('buscar')
            ->assertSet('ocupacion', 'estudiante')
            ->assertSet('estanciaFiltro', 'con')
            ->call('limpiar')
            ->assertSet('ocupacion', '')
            ->assertSet('fOcupacion', '')
            ->assertSet('estanciaFiltro', '');
    }
}
