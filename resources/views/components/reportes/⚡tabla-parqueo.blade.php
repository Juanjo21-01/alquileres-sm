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

    /** 'todos' | 'pagado' | 'pendiente'. */
    public string $estado = 'todos';

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
            'personalizado' => null,
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
        $this->estado = 'todos';
        $this->setFechasPeriodo();
    }

    /**
     * @return array{parqueo: \Illuminate\Support\Collection, totales: array{espacios: int, cobrado: float, pendiente: float}, vehiculos: array{inquilinos: int, externos: int, total: int}}
     */
    private function calcularParqueo(): array
    {
        $svc = app(ReporteService::class);
        $parqueo = $svc->parqueoPorMes(Carbon::parse($this->desde), Carbon::parse($this->hasta), $this->estado);

        $totales = [
            'espacios' => (int) $parqueo->sum('espacios'),
            'cobrado' => (float) $parqueo->sum('cobrado'),
            'pendiente' => (float) $parqueo->sum('pendiente'),
        ];

        return [
            'parqueo' => $parqueo,
            'totales' => $totales,
            'vehiculos' => $svc->conteoVehiculos(),
        ];
    }

    private function nombreArchivo(string $extension): string
    {
        return "parqueo-{$this->estado}-{$this->desde}-a-{$this->hasta}.{$extension}";
    }

    public function exportarCsv()
    {
        ['parqueo' => $parqueo, 'totales' => $totales] = $this->calcularParqueo();

        return response()->streamDownload(function () use ($parqueo, $totales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Periodo', 'Espacios', 'Cobrado', 'Pendiente'], escape: '');
            foreach ($parqueo as $fila) {
                fputcsv($out, [$fila['periodo'], $fila['espacios'], $fila['cobrado'], $fila['pendiente']], escape: '');
            }
            fputcsv($out, ['TOTAL', $totales['espacios'], $totales['cobrado'], $totales['pendiente']], escape: '');
            fclose($out);
        }, $this->nombreArchivo('csv'), ['Content-Type' => 'text/csv']);
    }

    public function exportarPdf()
    {
        ['parqueo' => $parqueo, 'totales' => $totales, 'vehiculos' => $vehiculos] = $this->calcularParqueo();

        $pdf = Pdf::loadView('pdfs.reporte-parqueo', [
            'parqueo' => $parqueo,
            'totales' => $totales,
            'vehiculos' => $vehiculos,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'estado' => $this->estado,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->nombreArchivo('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function with(): array
    {
        return $this->calcularParqueo();
    }
}; ?>

<div class="space-y-4">
    {{-- Filtros --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/40 p-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-end gap-3">
            <div>
                <flux:label class="text-xs">Período</flux:label>
                <flux:select wire:model.live="periodo" class="w-full md:w-48">
                    <flux:select.option value="mes_actual">Mes actual</flux:select.option>
                    <flux:select.option value="este_anio">Este año</flux:select.option>
                    <flux:select.option value="personalizado">Rango personalizado</flux:select.option>
                </flux:select>
            </div>

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

            <div>
                <flux:label class="text-xs">Estado</flux:label>
                <flux:select wire:model.live="estado" class="w-full md:w-44">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    <flux:select.option value="pagado">Pagados</flux:select.option>
                    <flux:select.option value="pendiente">Pendientes</flux:select.option>
                </flux:select>
            </div>

            <div class="flex gap-2 md:ml-auto">
                <flux:button wire:click="limpiar" variant="ghost" icon="arrow-path">Limpiar</flux:button>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
            <flux:icon.calendar-days variant="micro" />
            <span>
                {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }}
                al
                {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
            </span>
        </div>
    </div>

    {{-- Conteo de vehículos esperados --}}
    <flux:card class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div class="flex items-center gap-3">
            <flux:icon.truck class="text-zinc-500" />
            <div>
                <flux:text size="sm" class="text-zinc-500">Vehículos esperados en parqueo</flux:text>
                <flux:heading size="lg">{{ $vehiculos['total'] }}</flux:heading>
            </div>
        </div>
        <flux:text size="sm" class="text-zinc-500">
            {{ $vehiculos['inquilinos'] }} inquilinos con vehículo · {{ $vehiculos['externos'] }} arrendatarios externos activos
        </flux:text>
    </flux:card>

    {{-- Descripción + exportaciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <flux:text size="sm" class="text-zinc-500">
            Ingreso de parqueo externo, independiente del flujo de caja principal.
        </flux:text>

        <div class="flex gap-2">
            <flux:button wire:click="exportarCsv" icon="table-cells" variant="ghost" size="sm">
                <span wire:loading.remove wire:target="exportarCsv">Exportar CSV</span>
                <span wire:loading wire:target="exportarCsv">Generando...</span>
            </flux:button>
            <flux:button wire:click="exportarPdf" icon="document-arrow-down" variant="ghost" size="sm">
                <span wire:loading.remove wire:target="exportarPdf">Exportar PDF</span>
                <span wire:loading wire:target="exportarPdf">Generando...</span>
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Mes</flux:table.column>
            <flux:table.column align="end">Espacios</flux:table.column>
            <flux:table.column align="end">Cobrado</flux:table.column>
            <flux:table.column align="end">Pendiente</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($parqueo as $fila)
                <flux:table.row :key="$fila['periodo']">
                    <flux:table.cell class="font-medium whitespace-nowrap">
                        {{ ucfirst(\Carbon\CarbonImmutable::createFromFormat('Y-m', $fila['periodo'])->translatedFormat('F Y')) }}
                    </flux:table.cell>
                    <flux:table.cell align="end">{{ $fila['espacios'] }}</flux:table.cell>
                    <flux:table.cell align="end" class="text-green-600 dark:text-green-500">
                        Q {{ number_format($fila['cobrado'], 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="text-amber-600 dark:text-amber-500">
                        Q {{ number_format($fila['pendiente'], 2) }}
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

    {{-- Totales --}}
    @if ($parqueo->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <flux:card class="space-y-1">
                <flux:text size="sm" class="text-zinc-500">Espacios rentados</flux:text>
                <flux:heading size="lg">{{ $totales['espacios'] }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text size="sm" class="text-zinc-500">Total cobrado</flux:text>
                <flux:heading size="lg" class="text-green-600 dark:text-green-500">
                    Q {{ number_format($totales['cobrado'], 2) }}
                </flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text size="sm" class="text-zinc-500">Total pendiente</flux:text>
                <flux:heading size="lg" class="text-amber-600 dark:text-amber-500">
                    Q {{ number_format($totales['pendiente'], 2) }}
                </flux:heading>
            </flux:card>
        </div>
    @endif
</div>
