<?php

namespace App\Services;

use App\Models\Cuarto;
use App\Models\Estancia;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EstanciaService
{
    public function abrir(array $datos, array $extras = [], ?int $userId = null): Estancia
    {
        return DB::transaction(function () use ($datos, $extras, $userId) {
            $cuarto = Cuarto::lockForUpdate()->findOrFail($datos['cuarto_id']);

            if (! $cuarto->estaDisponible()) {
                throw new RuntimeException("El cuarto no está disponible (estado actual: {$cuarto->estado}).");
            }

            $estancia = Estancia::create([
                'inquilino_id' => $datos['inquilino_id'],
                'cuarto_id' => $cuarto->id,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin_estimada' => $datos['fecha_fin_estimada'] ?? null,
                'precio_acordado' => $datos['precio_acordado'],
                'deposito' => $datos['deposito'] ?? 0,
                'estado' => Estancia::ESTADO_ACTIVA,
                'notas' => $datos['notas'] ?? null,
                'user_registro_id' => $userId,
            ]);

            foreach ($extras as $extra) {
                $estancia->extras()->create($extra);
            }

            $cuarto->update(['estado' => Cuarto::ESTADO_OCUPADO]);

            return $estancia->load(['inquilino', 'cuarto', 'extras']);
        });
    }

    public function cerrar(Estancia $estancia, string $fechaFin, ?string $motivo = null): Estancia
    {
        return DB::transaction(function () use ($estancia, $fechaFin, $motivo) {
            if (! $estancia->estaActiva()) {
                throw new RuntimeException('Solo se pueden cerrar estancias activas.');
            }

            $estancia->update([
                'fecha_fin' => $fechaFin,
                'estado' => Estancia::ESTADO_FINALIZADA,
                'motivo_cierre' => $motivo,
            ]);

            $estancia->cuarto->update(['estado' => Cuarto::ESTADO_DISPONIBLE]);

            return $estancia->fresh(['cuarto', 'inquilino']);
        });
    }

    public function cancelar(Estancia $estancia, string $motivo): Estancia
    {
        return DB::transaction(function () use ($estancia, $motivo) {
            if (! $estancia->estaActiva()) {
                throw new RuntimeException('Solo se pueden cancelar estancias activas.');
            }

            $estancia->update([
                'estado' => Estancia::ESTADO_CANCELADA,
                'motivo_cierre' => $motivo,
            ]);

            $estancia->cuarto->update(['estado' => Cuarto::ESTADO_DISPONIBLE]);

            return $estancia->fresh(['cuarto', 'inquilino']);
        });
    }
}
