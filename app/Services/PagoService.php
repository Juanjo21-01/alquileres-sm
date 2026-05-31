<?php

namespace App\Services;

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PagoService
{
    public function registrar(array $datos, ?int $userId = null): Pago
    {
        return DB::transaction(function () use ($datos, $userId) {
            $estancia = Estancia::lockForUpdate()->findOrFail($datos['estancia_id']);

            if ($estancia->estado === Estancia::ESTADO_CANCELADA) {
                throw new RuntimeException('No se pueden registrar pagos en una estancia cancelada.');
            }

            $tipoPago = TipoPago::findOrFail($datos['tipo_pago_id']);

            if ($tipoPago->requiere_mes && empty($datos['mes_aplicado'])) {
                throw new RuntimeException("El tipo de pago '{$tipoPago->nombre}' requiere mes aplicado.");
            }

            if ($tipoPago->requiere_mes && ! empty($datos['mes_aplicado'])) {
                $mesAplicado = Carbon::parse($datos['mes_aplicado'])->startOfMonth();
                $mesInicio = $estancia->fecha_inicio->copy()->startOfMonth();

                if ($mesAplicado->lt($mesInicio)) {
                    throw new RuntimeException(
                        "El mes aplicado no puede ser anterior al inicio de la estancia ({$mesInicio->translatedFormat('F Y')})."
                    );
                }

                $yaExiste = Pago::where('estancia_id', $estancia->id)
                    ->where('tipo_pago_id', $tipoPago->id)
                    ->where('mes_aplicado', $mesAplicado->toDateString())
                    ->exists();

                if ($yaExiste) {
                    throw new RuntimeException(
                        "Ya existe un pago de {$tipoPago->nombre} para {$mesAplicado->translatedFormat('F Y')} en esta estancia."
                    );
                }
            }

            $bruto = round((float) $datos['monto_bruto'], 2);
            $descuento = round((float) ($datos['descuento'] ?? 0), 2);

            if ($descuento > 0 && empty($datos['motivo_descuento'])) {
                throw new RuntimeException('El descuento requiere un motivo.');
            }

            if ($descuento > $bruto) {
                throw new RuntimeException('El descuento no puede exceder el monto bruto.');
            }

            $neto = round($bruto - $descuento, 2);

            // El PDF NO se genera ni persiste aquí.
            // Se genera on-demand desde ReciboPdfService cuando el usuario hace clic en "Descargar".
            return Pago::create([
                'estancia_id' => $estancia->id,
                'tipo_pago_id' => $tipoPago->id,
                'fecha_pago' => $datos['fecha_pago'],
                'mes_aplicado' => $tipoPago->requiere_mes ? $datos['mes_aplicado'] : null,
                'monto_bruto' => $bruto,
                'descuento' => $descuento,
                'motivo_descuento' => $descuento > 0 ? $datos['motivo_descuento'] : null,
                'monto_neto' => $neto,
                'metodo_pago' => $datos['metodo_pago'],
                'referencia' => $datos['referencia'] ?? null,
                'recibo_numero' => $this->generarReciboNumero(),
                'notas' => $datos['notas'] ?? null,
                'user_registro_id' => $userId,
            ]);
        });
    }

    public function eliminar(Pago $pago): void
    {
        // Soft delete simple. No hay archivo físico que limpiar (los PDFs no se persisten).
        $pago->delete();
    }

    protected function generarReciboNumero(): string
    {
        $year = now()->year;
        $ultimo = Pago::where('recibo_numero', 'like', "REC-{$year}-%")
            ->orderByDesc('id')
            ->value('recibo_numero');
        $secuencial = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return sprintf('REC-%d-%06d', $year, $secuencial);
    }
}
