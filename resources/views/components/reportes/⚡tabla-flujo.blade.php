<?php

use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component {
    /** 'mes_actual' | 'este_anio' | 'personalizado'. */
    public string $periodo = 'este_anio';

    public string $desde = '';

    public string $hasta = '';

    /** 'caja' (por fecha_pago) o 'devengado' (por mes_aplicado). */
    public string $vista = 'caja';

    public function mount(): void
    {
        $this->setFechasPeriodo();
    }

    public function updatedPeriodo(): void
    {
        $this->setFechasPeriodo();
    }

    private function setFechasPeriodo(): void
    {
        match ($this->periodo) {
            'mes_actual' => $this->fijarRango(now()->startOfMonth(), now()->endOfMonth()),
            'este_anio' => $this->fijarRango(now()->startOfYear(), now()->endOfYear()),
            'personalizado' => null, // conserva las fechas actuales para edición manual
        };
    }

    private function fijarRango(CarbonInterface $desde, CarbonInterface $hasta): void
    {
        $this->desde = $desde->format('Y-m-d');
        $this->hasta = $hasta->format('Y-m-d');
    }

    public function limpiar(): void
    {
        $this->periodo = 'este_anio';
        $this->vista = 'caja';
        $this->setFechasPeriodo();
    }

    /**
     * Calcula el flujo mensual y sus totales según los filtros actuales.
     *
     * @return array{flujo: \Illuminate\Support\Collection, totales: array{ingresos: float, egresos: float, ganancia: float}}
     */
    private function calcularFlujo(): array
    {
        $svc = app(ReporteService::class);
        $desde = Carbon::parse($this->desde);
        $hasta = Carbon::parse($this->hasta);

        $flujo = $this->vista === 'devengado'
            ? $svc->flujoDevengado($desde, $hasta)
            : $svc->flujoCaja($desde, $hasta);

        $totales = [
            'ingresos' => (float) $flujo->sum('ingresos'),
            'egresos' => (float) $flujo->sum('egresos'),
            'ganancia' => (float) $flujo->sum('ganancia'),
        ];

        return ['flujo' => $flujo, 'totales' => $totales];
    }

    private function nombreArchivo(string $extension): string
    {
        return "flujo-{$this->vista}-{$this->desde}-a-{$this->hasta}.{$extension}";
    }

    public function exportarCsv()
    {
        ['flujo' => $flujo, 'totales' => $totales] = $this->calcularFlujo();

        return response()->streamDownload(function () use ($flujo, $totales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Periodo', 'Ingresos', 'Egresos', 'Ganancia'], escape: '');
            foreach ($flujo as $fila) {
                fputcsv($out, [$fila['periodo'], $fila['ingresos'], $fila['egresos'], $fila['ganancia']], escape: '');
            }
            fputcsv($out, ['TOTAL', $totales['ingresos'], $totales['egresos'], $totales['ganancia']], escape: '');
            fclose($out);
        }, $this->nombreArchivo('csv'), ['Content-Type' => 'text/csv']);
    }

    public function exportarPdf()
    {
        ['flujo' => $flujo, 'totales' => $totales] = $this->calcularFlujo();

        $pdf = Pdf::loadView('pdfs.reporte-flujo', [
            'flujo' => $flujo,
            'totales' => $totales,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'vista' => $this->vista,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->nombreArchivo('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function with(): array
    {
        return $this->calcularFlujo();
    }
}; ?>

<div class="space-y-4">
    {{-- Filtros --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/40 p-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-end gap-3">
            {{-- Período --}}
            <div>
                <flux:label class="text-xs">Período</flux:label>
                <flux:select wire:model.live="periodo" class="w-full md:w-48">
                    <flux:select.option value="mes_actual">Mes actual</flux:select.option>
                    <flux:select.option value="este_anio">Este año</flux:select.option>
                    <flux:select.option value="personalizado">Rango personalizado</flux:select.option>
                </flux:select>
            </div>

            {{-- Rango personalizado (solo si aplica) --}}
            @if ($periodo === 'personalizado')
                <div>
                    <flux:label class="text-xs">Desde</flux:label>
                    <flux:input wire:model.live.debounce.500ms="desde" type="date" class="w-full md:w-44" />
                </div>
                <div>
                    <flux:label class="text-xs">Hasta</flux:label>
                    <flux:input wire:model.live.debounce.500ms="hasta" type="date" class="w-full md:w-44" />
                </div>
            @endif

            {{-- Vista --}}
            <div>
                <flux:label class="text-xs">Vista</flux:label>
                <flux:select wire:model.live="vista" class="w-full md:w-48">
                    <flux:select.option value="caja">Flujo de caja</flux:select.option>
                    <flux:select.option value="devengado">Devengado</flux:select.option>
                </flux:select>
            </div>

            <div class="flex gap-2 md:ml-auto">
                <flux:button wire:click="limpiar" variant="outline" icon="arrow-path">
                    Limpiar
                </flux:button>
            </div>
        </div>

        {{-- Período activo --}}
        <div class="flex items-center gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
            <flux:icon.calendar-days variant="micro" />
            <span>
                {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }}
                al
                {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
            </span>
            <flux:badge size="sm" color="zinc">
                {{ $vista === 'devengado' ? 'Devengado' : 'Flujo de caja' }}
            </flux:badge>
        </div>
    </div>

    {{-- Descripción de la vista + exportaciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <flux:text size="sm" class="text-zinc-500">
            {{ $vista === 'devengado'
                ? 'Ingresos imputados al mes al que corresponden (mes aplicado).'
                : 'Ingresos por la fecha en que se cobraron (movimiento de caja).' }}
        </flux:text>

        <div class="flex gap-2">
            <flux:button wire:click="exportarCsv" icon="table-cells" variant="outline" size="sm">
                <span wire:loading.remove wire:target="exportarCsv">Exportar CSV</span>
                <span wire:loading wire:target="exportarCsv">Generando...</span>
            </flux:button>
            <flux:button wire:click="exportarPdf" icon="document-arrow-down" variant="outline" size="sm">
                <span wire:loading.remove wire:target="exportarPdf">Exportar PDF</span>
                <span wire:loading wire:target="exportarPdf">Generando...</span>
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Mes</flux:table.column>
            <flux:table.column align="end">Ingresos</flux:table.column>
            <flux:table.column align="end">Egresos</flux:table.column>
            <flux:table.column align="end">Ganancia</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($flujo as $fila)
                <flux:table.row :key="$fila['periodo']">
                    <flux:table.cell class="font-medium whitespace-nowrap">
                        {{ ucfirst(\Carbon\CarbonImmutable::createFromFormat('Y-m', $fila['periodo'])->translatedFormat('F Y')) }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="text-green-600 dark:text-green-500">
                        Q {{ number_format($fila['ingresos'], 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="text-red-600 dark:text-red-500">
                        Q {{ number_format($fila['egresos'], 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="end" @class([
                        'font-semibold' => true,
                        'text-green-600 dark:text-green-500' => $fila['ganancia'] >= 0,
                        'text-red-600 dark:text-red-500' => $fila['ganancia'] < 0,
                    ])>
                        Q {{ number_format($fila['ganancia'], 2) }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500 py-8">
                        Sin datos en el rango seleccionado.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Totales generales --}}
    @if ($flujo->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <flux:card class="space-y-1">
                <flux:text size="sm" class="text-zinc-500">Total ingresos</flux:text>
                <flux:heading size="lg" class="text-green-600 dark:text-green-500">
                    Q {{ number_format($totales['ingresos'], 2) }}
                </flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text size="sm" class="text-zinc-500">Total egresos</flux:text>
                <flux:heading size="lg" class="text-red-600 dark:text-red-500">
                    Q {{ number_format($totales['egresos'], 2) }}
                </flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text size="sm" class="text-zinc-500">Ganancia neta</flux:text>
                <flux:heading size="lg" @class([
                    'text-green-600 dark:text-green-500' => $totales['ganancia'] >= 0,
                    'text-red-600 dark:text-red-500' => $totales['ganancia'] < 0,
                ])>
                    Q {{ number_format($totales['ganancia'], 2) }}
                </flux:heading>
            </flux:card>
        </div>
    @endif
</div>
