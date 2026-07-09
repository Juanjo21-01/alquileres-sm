<?php

use App\Http\Controllers\GastoController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Propiedades
    Route::livewire('/propiedades', 'pages::propiedades.index')
        ->name('propiedades.index');

    // Cuartos por propiedad (route model binding: {propiedad} → Propiedad)
    Route::livewire('/propiedades/{propiedad}/cuartos', 'pages::cuartos.tablero')
        ->name('cuartos.tablero');

    // Inquilinos
    Route::livewire('/inquilinos', 'pages::inquilinos.index')
        ->name('inquilinos.index');
    Route::livewire('/inquilinos/{inquilino}', 'pages::inquilinos.detalle')
        ->name('inquilinos.detalle');

    // Estancias
    Route::livewire('/estancias', 'pages::estancias.index')
        ->name('estancias.index');
    Route::livewire('/estancias/{estancia}', 'pages::estancias.detalle')
        ->name('estancias.detalle');

    // Pagos
    Route::livewire('/pagos', 'pages::pagos.index')
        ->name('pagos.index');
    Route::livewire('/pagos/registrar', 'pages::pagos.registrar')
        ->name('pagos.registrar');
    Route::livewire('/pagos/{pago}', 'pages::pagos.detalle')
        ->name('pagos.detalle');

    // Parqueo
    Route::livewire('/parqueo', 'pages::parqueo.index')
        ->name('parqueo.index');
    Route::livewire('/parqueo/{id}', 'pages::parqueo.detalle')
        ->name('parqueo.detalle');

    // Gastos
    Route::livewire('/gastos', 'pages::gastos.index')
        ->name('gastos.index');

    // Categorías de gasto (solo administrador)
    Route::middleware('rol:administrador')->group(function () {
        Route::livewire('/categorias-gasto', 'pages::categorias-gasto.index')
            ->name('categorias.index');
    });

    // Servir comprobante autenticado — preview (inline) y descarga
    Route::get('/comprobante/{gasto}/preview', [GastoController::class, 'previewComprobante'])
        ->name('gastos.comprobante.preview');
    Route::get('/comprobante/{gasto}/descargar', [GastoController::class, 'descargarComprobante'])
        ->name('gastos.comprobante.descargar');
});

require __DIR__.'/settings.php';
