<?php

namespace Database\Seeders;

use App\Models\CategoriaGasto;
use Illuminate\Database\Seeder;

class CategoriaGastoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            // Gastos por propiedad — requieren especificar a qué casa pertenecen
            ['codigo' => 'luz',           'nombre' => 'Luz',           'requiere_propiedad' => true,  'requiere_cuarto' => false],
            ['codigo' => 'agua',          'nombre' => 'Agua',          'requiere_propiedad' => true,  'requiere_cuarto' => false],
            ['codigo' => 'internet',      'nombre' => 'Internet',      'requiere_propiedad' => true,  'requiere_cuarto' => false],
            ['codigo' => 'basura',        'nombre' => 'Basura',        'requiere_propiedad' => true,  'requiere_cuarto' => false],
            ['codigo' => 'gas',           'nombre' => 'Gas',           'requiere_propiedad' => true,  'requiere_cuarto' => false],
            // Gastos flexibles — propiedad y cuarto opcionales
            ['codigo' => 'mantenimiento', 'nombre' => 'Mantenimiento', 'requiere_propiedad' => false, 'requiere_cuarto' => false],
            ['codigo' => 'salarios',      'nombre' => 'Salarios',      'requiere_propiedad' => false, 'requiere_cuarto' => false],
            ['codigo' => 'otros',         'nombre' => 'Otros',         'requiere_propiedad' => false, 'requiere_cuarto' => false],
        ];

        foreach ($categorias as $cat) {
            CategoriaGasto::updateOrCreate(
                ['codigo' => $cat['codigo']],
                $cat + ['activo' => true]
            );
        }
    }
}
