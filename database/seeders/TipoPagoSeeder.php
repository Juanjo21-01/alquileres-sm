<?php

namespace Database\Seeders;

use App\Models\TipoPago;
use Illuminate\Database\Seeder;

class TipoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'anticipo',    'nombre' => 'Anticipo',    'requiere_mes' => false],
            ['codigo' => 'mensualidad', 'nombre' => 'Mensualidad', 'requiere_mes' => true],
            ['codigo' => 'extra',       'nombre' => 'Extra',       'requiere_mes' => false],
        ];

        foreach ($tipos as $t) {
            TipoPago::updateOrCreate(['codigo' => $t['codigo']], $t + ['activo' => true]);
        }
    }
}
