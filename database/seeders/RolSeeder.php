<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Administrador',
                'codigo' => Rol::COD_ADMIN,
                'descripcion' => 'Acceso total al sistema.',
            ],
            [
                'nombre' => 'Encargado',
                'codigo' => Rol::COD_ENCARGADO,
                'descripcion' => 'Operación diaria.',
            ],
        ];

        foreach ($roles as $r) {
            Rol::updateOrCreate(['codigo' => $r['codigo']], $r);
        }
    }
}
