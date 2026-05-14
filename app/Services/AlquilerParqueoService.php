<?php

namespace App\Services;

use App\Models\AlquilerParqueo;
use App\Models\ArrendatarioParqueo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AlquilerParqueoService
{
    public function registrarMes(array $datos, ?int $userId = null): AlquilerParqueo
    {
        return DB::transaction(function () use ($datos, $userId) {
            $arrendatario = ArrendatarioParqueo::findOrFail($datos['arrendatario_parqueo_id']);

            if (! $arrendatario->activo) {
                throw new RuntimeException('No se puede registrar un mes a un arrendatario inactivo.');
            }

            $monto = round((float) $datos['monto'], 2);
            if ($monto <= 0) {
                throw new RuntimeException('El monto debe ser mayor a cero.');
            }

            $mes = Carbon::parse($datos['mes'])->startOfMonth()->format('Y-m-d');

            return AlquilerParqueo::create([
                'arrendatario_parqueo_id' => $arrendatario->id,
                'mes' => $mes,
                'monto' => $monto,
                'pagado' => $datos['pagado'] ?? false,
                'fecha_pago' => ($datos['pagado'] ?? false) ? ($datos['fecha_pago'] ?? now()->toDateString()) : null,
                'metodo_pago' => $datos['metodo_pago'] ?? AlquilerParqueo::METODO_EFECTIVO,
                'notas' => $datos['notas'] ?? null,
                'user_registro_id' => $userId,
            ]);
        });
    }

    public function marcarPagado(AlquilerParqueo $alquiler, string $metodoPago, ?string $fechaPago = null): void
    {
        $alquiler->update([
            'pagado' => true,
            'fecha_pago' => $fechaPago ?? now()->toDateString(),
            'metodo_pago' => $metodoPago,
        ]);
    }

    public function marcarPendiente(AlquilerParqueo $alquiler): void
    {
        $alquiler->update([
            'pagado' => false,
            'fecha_pago' => null,
        ]);
    }

    public function desactivarArrendatario(ArrendatarioParqueo $arrendatario): void
    {
        $arrendatario->update(['activo' => false]);
    }

    public function activarArrendatario(ArrendatarioParqueo $arrendatario): void
    {
        $arrendatario->update(['activo' => true]);
    }
}
