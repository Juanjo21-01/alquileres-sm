<?php

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
});

require __DIR__.'/settings.php';
