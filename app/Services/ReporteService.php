<?php

namespace App\Services;

use App\Models\AlquilerParqueo;
use App\Models\ArrendatarioParqueo;
use App\Models\Cuarto;
use App\Models\Estancia;
use App\Models\Gasto;
use App\Models\Inquilino;
use App\Models\Pago;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Flujo devengado: ingresos por el mes al que aplican (mes_aplicado),
     * egresos por su fecha. Refleja la realidad contable del periodo.
     */
    public function flujoDevengado(CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        $ingresos = Pago::whereNotNull('mes_aplicado')
            ->whereBetween('mes_aplicado', [$desde->copy()->startOfMonth(), $hasta->copy()->endOfMonth()])
            ->selectRaw($this->periodoMensualSql('mes_aplicado').' as periodo, SUM(monto_neto) as total')
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        $egresos = Gasto::whereBetween('fecha', [$desde, $hasta])
            ->selectRaw($this->periodoMensualSql('fecha').' as periodo, SUM(monto) as total')
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        return $this->consolidar($ingresos, $egresos, $desde, $hasta);
    }

    /**
     * Flujo de caja: ingresos por la fecha en que se cobraron (fecha_pago),
     * egresos por su fecha. Refleja el movimiento real de dinero.
     */
    public function flujoCaja(CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        $ingresos = Pago::whereBetween('fecha_pago', [$desde, $hasta])
            ->selectRaw($this->periodoMensualSql('fecha_pago').' as periodo, SUM(monto_neto) as total')
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        $egresos = Gasto::whereBetween('fecha', [$desde, $hasta])
            ->selectRaw($this->periodoMensualSql('fecha').' as periodo, SUM(monto) as total')
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        return $this->consolidar($ingresos, $egresos, $desde, $hasta);
    }

    /**
     * Une ingresos y egresos por periodo (YYYY-MM), rellenando meses sin datos.
     *
     * @return Collection<string, array{periodo: string, ingresos: float, egresos: float, ganancia: float}>
     */
    protected function consolidar(Collection $ingresos, Collection $egresos, CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        $resultado = collect();
        $cursor = $desde->copy()->startOfMonth();
        $fin = $hasta->copy()->endOfMonth();

        while ($cursor->lte($fin)) {
            $key = $cursor->format('Y-m');
            $ing = (float) ($ingresos[$key] ?? 0);
            $egr = (float) ($egresos[$key] ?? 0);

            $resultado->put($key, [
                'periodo' => $key,
                'ingresos' => $ing,
                'egresos' => $egr,
                'ganancia' => round($ing - $egr, 2),
            ]);

            $cursor = $cursor->addMonth();
        }

        return $resultado;
    }

    /**
     * Ocupación actual: total de cuartos activos vs ocupados, global y por propiedad.
     *
     * @return array{total: int, ocupados: int, porcentaje: float, porPropiedad: Collection}
     */
    public function ocupacionActual(): array
    {
        $total = Cuarto::where('activo', true)->count();
        $ocupados = Cuarto::where('activo', true)
            ->where('estado', Cuarto::ESTADO_OCUPADO)
            ->count();

        $porPropiedad = Cuarto::where('activo', true)
            ->selectRaw("propiedad_id, COUNT(*) as total, SUM(estado = 'ocupado') as ocupados")
            ->groupBy('propiedad_id')
            ->with('propiedad:id,nombre')
            ->get();

        return [
            'total' => $total,
            'ocupados' => $ocupados,
            'porcentaje' => $total > 0 ? round(($ocupados / $total) * 100, 1) : 0,
            'porPropiedad' => $porPropiedad,
        ];
    }

    /**
     * Conteo de cuartos activos por cada estado (disponible, ocupado, reservado, mantenimiento).
     *
     * @return array{porEstado: array<string, int>, total: int}
     */
    public function ocupacionPorEstado(): array
    {
        $conteos = Cuarto::where('activo', true)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $porEstado = [];
        foreach (array_keys(Cuarto::estados()) as $estado) {
            $porEstado[$estado] = (int) ($conteos[$estado] ?? 0);
        }

        return [
            'porEstado' => $porEstado,
            'total' => array_sum($porEstado),
        ];
    }

    /**
     * Ingresos de parqueo externo agrupados por mes. Reporte independiente del flujo de caja.
     *
     * @param  'todos'|'pagado'|'pendiente'  $estado
     * @return Collection<string, array{periodo: string, espacios: int, cobrado: float, pendiente: float}>
     */
    public function parqueoPorMes(CarbonInterface $desde, CarbonInterface $hasta, string $estado = 'todos'): Collection
    {
        $periodo = $this->periodoMensualSql('mes');

        $filas = AlquilerParqueo::query()
            ->whereBetween('mes', [$desde->copy()->startOfMonth(), $hasta->copy()->endOfMonth()])
            ->when($estado === 'pagado', fn ($q) => $q->where('pagado', true))
            ->when($estado === 'pendiente', fn ($q) => $q->where('pagado', false))
            ->selectRaw("
                {$periodo} as periodo,
                COUNT(*) as espacios,
                SUM(CASE WHEN pagado = 1 THEN monto ELSE 0 END) as cobrado,
                SUM(CASE WHEN pagado = 0 THEN monto ELSE 0 END) as pendiente
            ")
            ->groupBy('periodo')
            ->get()
            ->keyBy('periodo');

        $resultado = collect();
        $cursor = $desde->copy()->startOfMonth();
        $fin = $hasta->copy()->endOfMonth();

        while ($cursor->lte($fin)) {
            $key = $cursor->format('Y-m');
            $fila = $filas->get($key);

            $resultado->put($key, [
                'periodo' => $key,
                'espacios' => (int) ($fila->espacios ?? 0),
                'cobrado' => (float) ($fila->cobrado ?? 0),
                'pendiente' => (float) ($fila->pendiente ?? 0),
            ]);

            $cursor = $cursor->addMonth();
        }

        return $resultado;
    }

    /**
     * Vehículos esperados en el parqueo: inquilinos con vehículo y estancia activa
     * más arrendatarios externos activos.
     *
     * @return array{inquilinos: int, externos: int, total: int}
     */
    public function conteoVehiculos(): array
    {
        $inquilinos = Inquilino::whereNotNull('vehiculo_tipo')
            ->whereHas('estanciaActiva')
            ->count();

        $externos = ArrendatarioParqueo::activos()->count();

        return [
            'inquilinos' => $inquilinos,
            'externos' => $externos,
            'total' => $inquilinos + $externos,
        ];
    }

    /**
     * Ocupación histórica por cuarto en un rango: días ocupado, % del periodo,
     * rotación (# estancias) e inquilino actual/último. Agrupable por propiedad en la vista.
     *
     * @return Collection<int, array{cuarto_id: int, codigo: string, propiedad: ?string, propiedad_id: int, estado: string, estado_label: string, rotacion: int, dias_ocupado: int, dias_periodo: int, porcentaje: float, inquilino: ?string, ocupado_ahora: bool}>
     */
    public function ocupacionCuartos(CarbonInterface $desde, CarbonInterface $hasta, ?int $propiedadId = null): Collection
    {
        $diasPeriodo = $this->diasDelPeriodo($desde, $hasta);

        $cuartos = Cuarto::query()
            ->where('activo', true)
            ->when($propiedadId, fn ($q) => $q->where('propiedad_id', $propiedadId))
            ->with([
                'propiedad:id,nombre',
                'estancias' => fn ($q) => $q
                    ->whereIn('estado', [Estancia::ESTADO_ACTIVA, Estancia::ESTADO_FINALIZADA])
                    ->where('fecha_inicio', '<=', $hasta)
                    ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $desde))
                    ->with('inquilino:id,nombres,apellidos')
                    ->orderByDesc('fecha_inicio'),
            ])
            ->orderBy('propiedad_id')
            ->orderBy('codigo')
            ->get();

        return $cuartos->map(function (Cuarto $cuarto) use ($desde, $hasta, $diasPeriodo) {
            $dias = 0;
            foreach ($cuarto->estancias as $estancia) {
                $dias += $this->diasOcupadosEnRango($estancia, $desde, $hasta);
            }
            $dias = min($dias, $diasPeriodo);
            $ultima = $cuarto->estancias->first();

            return [
                'cuarto_id' => $cuarto->id,
                'codigo' => $cuarto->codigo,
                'propiedad' => $cuarto->propiedad?->nombre,
                'propiedad_id' => $cuarto->propiedad_id,
                'estado' => $cuarto->estado,
                'estado_label' => Cuarto::estados()[$cuarto->estado] ?? $cuarto->estado,
                'rotacion' => $cuarto->estancias->count(),
                'dias_ocupado' => $dias,
                'dias_periodo' => $diasPeriodo,
                'porcentaje' => $diasPeriodo > 0 ? round($dias / $diasPeriodo * 100, 1) : 0,
                'inquilino' => $ultima?->inquilino?->nombre_completo,
                'ocupado_ahora' => $cuarto->estado === Cuarto::ESTADO_OCUPADO,
            ];
        });
    }

    /**
     * Historial completo de estancias de un cuarto más estadísticas del periodo.
     *
     * @return array{estancias: Collection, stats: array{rotacion: int, dias_ocupado: int, dias_periodo: int, porcentaje: float}}
     */
    public function historialCuarto(Cuarto $cuarto, CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $diasPeriodo = $this->diasDelPeriodo($desde, $hasta);

        $estancias = $cuarto->estancias()
            ->with('inquilino:id,nombres,apellidos')
            ->orderByDesc('fecha_inicio')
            ->get();

        $ocupadas = $estancias->whereIn('estado', [Estancia::ESTADO_ACTIVA, Estancia::ESTADO_FINALIZADA]);

        $diasOcupado = 0;
        foreach ($ocupadas as $estancia) {
            $diasOcupado += $this->diasOcupadosEnRango($estancia, $desde, $hasta);
        }
        $diasOcupado = min($diasOcupado, $diasPeriodo);

        $filas = $estancias->map(fn (Estancia $e) => [
            'estancia_id' => $e->id,
            'inquilino' => $e->inquilino?->nombre_completo,
            'inicio' => $e->fecha_inicio,
            'fin' => $e->fecha_fin,
            'dias' => $e->estado === Estancia::ESTADO_CANCELADA ? 0 : $this->diasReales($e),
            'estado' => $e->estado,
            'precio' => (float) $e->precio_acordado,
        ]);

        return [
            'estancias' => $filas,
            'stats' => [
                'rotacion' => $ocupadas->count(),
                'dias_ocupado' => $diasOcupado,
                'dias_periodo' => $diasPeriodo,
                'porcentaje' => $diasPeriodo > 0 ? round($diasOcupado / $diasPeriodo * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Historial completo de estancias de un inquilino más estadísticas acumuladas.
     *
     * @return array{estancias: Collection, stats: array{rotacion: int, cuartos: int, dias_total: int, cuarto_actual: ?string}}
     */
    public function historialInquilino(Inquilino $inquilino): array
    {
        $estancias = $inquilino->estancias()
            ->with('cuarto.propiedad')
            ->orderByDesc('fecha_inicio')
            ->get();

        $ocupadas = $estancias->whereIn('estado', [Estancia::ESTADO_ACTIVA, Estancia::ESTADO_FINALIZADA]);
        $activa = $estancias->firstWhere('estado', Estancia::ESTADO_ACTIVA);

        $filas = $estancias->map(fn (Estancia $e) => [
            'estancia_id' => $e->id,
            'cuarto' => $e->cuarto?->codigo,
            'propiedad' => $e->cuarto?->propiedad?->nombre,
            'inicio' => $e->fecha_inicio,
            'fin' => $e->fecha_fin,
            'dias' => $e->estado === Estancia::ESTADO_CANCELADA ? 0 : $this->diasReales($e),
            'estado' => $e->estado,
            'precio' => (float) $e->precio_acordado,
        ]);

        return [
            'estancias' => $filas,
            'stats' => [
                'rotacion' => $ocupadas->count(),
                'cuartos' => $ocupadas->pluck('cuarto_id')->unique()->count(),
                'dias_total' => (int) $filas->where('estado', '!=', Estancia::ESTADO_CANCELADA)->sum('dias'),
                'cuarto_actual' => $activa ? "{$activa->cuarto->propiedad->nombre} — {$activa->cuarto->codigo}" : null,
            ],
        ];
    }

    /**
     * Resumen por inquilino de su actividad de estancias dentro de un rango.
     * Solo incluye inquilinos con estancias (activa/finalizada) que traslapan el periodo.
     *
     * @param  'todos'|'estudiante'|'salud'|'otro'  $ocupacion
     * @return Collection<int, array{inquilino_id: int, nombre: string, ocupacion: string, estancias: int, cuartos: int, dias_ocupado: int, cuarto_actual: ?string, activo: bool}>
     */
    public function resumenInquilinos(CarbonInterface $desde, CarbonInterface $hasta, string $ocupacion = 'todos'): Collection
    {
        $overlap = fn ($q) => $q
            ->whereIn('estado', [Estancia::ESTADO_ACTIVA, Estancia::ESTADO_FINALIZADA])
            ->where('fecha_inicio', '<=', $hasta)
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $desde));

        $inquilinos = Inquilino::query()
            ->when($ocupacion !== 'todos', fn ($q) => $q->where('ocupacion', $ocupacion))
            ->whereHas('estancias', $overlap)
            ->with([
                'estancias' => fn ($q) => $overlap($q)->with('cuarto.propiedad'),
                'estanciaActiva.cuarto.propiedad',
            ])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return $inquilinos->map(function (Inquilino $inquilino) use ($desde, $hasta) {
            $dias = 0;
            foreach ($inquilino->estancias as $estancia) {
                $dias += $this->diasOcupadosEnRango($estancia, $desde, $hasta);
            }
            $activa = $inquilino->estanciaActiva;

            return [
                'inquilino_id' => $inquilino->id,
                'nombre' => $inquilino->nombre_completo,
                'ocupacion' => $inquilino->ocupacion,
                'estancias' => $inquilino->estancias->count(),
                'cuartos' => $inquilino->estancias->pluck('cuarto_id')->unique()->count(),
                'dias_ocupado' => $dias,
                'cuarto_actual' => $activa ? "{$activa->cuarto->propiedad->nombre} — {$activa->cuarto->codigo}" : null,
                'activo' => (bool) $inquilino->activo,
            ];
        });
    }

    private function diasDelPeriodo(CarbonInterface $desde, CarbonInterface $hasta): int
    {
        return max(1, (int) round($desde->startOfDay()->diffInDays($hasta->startOfDay())) + 1);
    }

    /**
     * Expresión SQL para agrupar una columna de fecha por 'YYYY-MM' según el driver.
     * MySQL usa DATE_FORMAT; SQLite (suite de tests) usa strftime.
     */
    private function periodoMensualSql(string $columna): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$columna})"
            : "DATE_FORMAT({$columna}, '%Y-%m')";
    }

    /**
     * Días que una estancia estuvo ocupada dentro del rango [desde, hasta] (traslape recortado).
     * Para estancias activas (sin fecha_fin) usa la fecha de hoy como fin.
     */
    private function diasOcupadosEnRango(Estancia $estancia, CarbonInterface $desde, CarbonInterface $hasta): int
    {
        $inicio = $estancia->fecha_inicio->greaterThan($desde) ? $estancia->fecha_inicio : $desde;
        $fin = $estancia->fecha_fin ?? now();
        $fin = $fin->greaterThan($hasta) ? $hasta : $fin;

        if ($fin->lessThan($inicio)) {
            return 0;
        }

        return (int) round($inicio->startOfDay()->diffInDays($fin->startOfDay())) + 1;
    }

    /**
     * Duración real de una estancia (inicio → fin, o hoy si sigue activa), en días.
     */
    private function diasReales(Estancia $estancia): int
    {
        $fin = $estancia->fecha_fin ?? now();

        if ($fin->lessThan($estancia->fecha_inicio)) {
            return 0;
        }

        return (int) round($estancia->fecha_inicio->startOfDay()->diffInDays($fin->startOfDay())) + 1;
    }
}
