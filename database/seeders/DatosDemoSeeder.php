<?php

namespace Database\Seeders;

use App\Models\CategoriaGasto;
use App\Models\Cuarto;
use App\Models\Estancia;
use App\Models\Gasto;
use App\Models\Inquilino;
use App\Models\Propiedad;
use App\Models\Rol;
use App\Models\TipoPago;
use App\Models\User;
use App\Services\EstanciaService;
use App\Services\PagoService;
use Illuminate\Database\Seeder;

class DatosDemoSeeder extends Seeder
{
    /**
     * Genera un conjunto de datos de demostración coherente para probar el sistema:
     * usuarios, propiedades, cuartos, inquilinos, estancias, pagos y gastos.
     */
    public function run(): void
    {
        // 1. Datos de referencia (roles, tipos de pago, categorías de gasto).
        $this->call([
            RolSeeder::class,
            TipoPagoSeeder::class,
            CategoriaGastoSeeder::class,
        ]);

        // 2. Usuarios operadores de demo.
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'rol_id' => Rol::where('codigo', Rol::COD_ADMIN)->value('id'),
                'name' => 'Administrador Demo',
                'password' => 'password',
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'encargado@demo.test'],
            [
                'rol_id' => Rol::where('codigo', Rol::COD_ENCARGADO)->value('id'),
                'name' => 'Encargado Demo',
                'password' => 'password',
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Propiedades (3) con cuartos (4 c/u = 12).
        $cuartos = collect();
        foreach (range(1, 3) as $p) {
            $propiedad = Propiedad::factory()->create(['nombre' => "Casa {$this->nombreCasa($p)}"]);

            foreach (range(1, 4) as $c) {
                $cuartos->push(Cuarto::factory()->create([
                    'propiedad_id' => $propiedad->id,
                    'codigo' => "{$p}-0{$c}",
                    'estado' => Cuarto::ESTADO_DISPONIBLE,
                ]));
            }
        }

        // 4. Inquilinos (12).
        $inquilinos = Inquilino::factory()->count(12)->create();

        // 5. Estancias: 7 activas + 4 finalizadas (9 cuartos e inquilinos distintos).
        $estanciaService = app(EstanciaService::class);

        $activas = collect();
        foreach (range(0, 6) as $i) {
            $activas->push($estanciaService->abrir(
                datos: [
                    'inquilino_id' => $inquilinos[$i]->id,
                    'cuarto_id' => $cuartos[$i]->id,
                    'fecha_inicio' => now()->subMonths(6)->startOfMonth()->toDateString(),
                    'precio_acordado' => (float) $cuartos[$i]->precio_base,
                    'anticipo' => 0,
                ],
                userId: $admin->id,
            ));
        }

        foreach (range(7, 10) as $i) {
            $estancia = $estanciaService->abrir(
                datos: [
                    'inquilino_id' => $inquilinos[$i]->id,
                    'cuarto_id' => $cuartos[$i]->id,
                    'fecha_inicio' => now()->subMonths(8)->startOfMonth()->toDateString(),
                    'precio_acordado' => (float) $cuartos[$i]->precio_base,
                    'anticipo' => 0,
                ],
                userId: $admin->id,
            );

            $estanciaService->cerrar(
                estancia: $estancia,
                fechaFin: now()->subMonths(2)->toDateString(),
                motivo: 'Fin de contrato',
            );
        }

        // 6. Pagos (30): mensualidades desde el inicio de cada estancia.
        $pagoService = app(PagoService::class);
        $mensualidadId = TipoPago::where('codigo', TipoPago::COD_MENSUALIDAD)->value('id');
        $objetivoPagos = 30;
        $pagosCreados = 0;

        foreach (Estancia::orderBy('id')->get() as $estancia) {
            $cursor = $estancia->fecha_inicio->copy()->startOfMonth();
            $limite = ($estancia->fecha_fin ?? now())->copy()->startOfMonth();

            while ($cursor->lte($limite) && $pagosCreados < $objetivoPagos) {
                $pagoService->registrar(
                    datos: [
                        'estancia_id' => $estancia->id,
                        'tipo_pago_id' => $mensualidadId,
                        'fecha_pago' => $cursor->copy()->addDays(3)->toDateString(),
                        'mes_aplicado' => $cursor->toDateString(),
                        'monto_bruto' => (float) $estancia->precio_acordado,
                        'descuento' => 0,
                        'metodo_pago' => 'efectivo',
                    ],
                    userId: $admin->id,
                );

                $pagosCreados++;
                // La app usa CarbonImmutable: addMonth() NO muta, hay que reasignar.
                $cursor = $cursor->addMonth();
            }

            if ($pagosCreados >= $objetivoPagos) {
                break;
            }
        }

        // 7. Gastos (20): respetando requiere_propiedad / requiere_cuarto de la categoría.
        $categorias = CategoriaGasto::all();
        $propiedades = Propiedad::all();

        foreach (range(1, 20) as $g) {
            $categoria = $categorias->random();

            $propiedadId = null;
            $cuartoId = null;

            if ($categoria->requiere_propiedad || $categoria->requiere_cuarto) {
                $propiedadId = $propiedades->random()->id;
            }

            if ($categoria->requiere_cuarto) {
                $cuartoId = Cuarto::where('propiedad_id', $propiedadId)->inRandomOrder()->value('id');
            }

            Gasto::factory()->create([
                'categoria_gasto_id' => $categoria->id,
                'propiedad_id' => $propiedadId,
                'cuarto_id' => $cuartoId,
                'user_registro_id' => $admin->id,
            ]);
        }
    }

    private function nombreCasa(int $n): string
    {
        return ['Central', 'Norte', 'Sur'][$n - 1] ?? "#{$n}";
    }
}
