<?php

namespace Tests\Feature;

use App\Models\Estancia;
use Database\Seeders\DatosDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatosDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_genera_datos_demo_coherentes(): void
    {
        $this->seed(DatosDemoSeeder::class);

        $this->assertDatabaseCount('propiedades', 3);
        $this->assertDatabaseCount('cuartos', 12);
        $this->assertDatabaseCount('inquilinos', 12);
        $this->assertDatabaseCount('pagos', 30);
        $this->assertDatabaseCount('gastos', 20);

        $this->assertSame(7, Estancia::where('estado', Estancia::ESTADO_ACTIVA)->count());
        $this->assertSame(4, Estancia::where('estado', Estancia::ESTADO_FINALIZADA)->count());

        $this->assertDatabaseHas('users', ['email' => 'admin@demo.test', 'activo' => true]);
        $this->assertDatabaseHas('users', ['email' => 'encargado@demo.test', 'activo' => true]);
    }
}
