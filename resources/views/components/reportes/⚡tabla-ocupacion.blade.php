<?php

use App\Models\Cuarto;
use App\Models\Propiedad;
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

    public string $propiedadId = '';

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
        $this->propiedadId = '';
        $this->setFechasPeriodo();
    }

    /**
     * @return array{rows: \Illuminate\Support\Collection, totales: array{cuartos: int, promedio: float}}
     */
    private function calcularOcupacion(): array
    {
        $rows = app(ReporteService::class)->ocupacionCuartos(
            Carbon::parse($this->desde),
            Carbon::parse($this->hasta),
            $this->propiedadId !== '' ? (int) $this->propiedadId : null,
        );

        return [
            'rows' => $rows,
            'totales' => [
                'cuartos' => $rows->count(),
                'promedio' => $rows->isNotEmpty() ? round($rows->avg('porcentaje'), 1) : 0,
            ],
        ];
    }

    private function propiedadNombre(): string
    {
        return $this->propiedadId !== ''
            ? (Propiedad::find($this->propiedadId)?->nombre ?? 'Todas')
            : 'Todas las propiedades';
    }

    private function nombreArchivo(string $extension): string
    {
        return "ocupacion-{$this->desde}-a-{$this->hasta}.{$extension}";
    }

    public function exportarCsv()
    {
        ['rows' => $rows] = $this->calcularOcupacion();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Propiedad', 'Cuarto', 'Estado', 'Rotacion', 'Dias ocupado', 'Porcentaje', 'Inquilino'], escape: '');
            foreach ($rows as $r) {
                fputcsv($out, [$r['propiedad'], $r['codigo'], $r['estado_label'], $r['rotacion'], $r['dias_ocupado'], $r['porcentaje'], $r['inquilino'] ?? ''], escape: '');
            }
            fclose($out);
        }, $this->nombreArchivo('csv'), ['Content-Type' => 'text/csv']);
    }

    public function exportarPdf()
    {
        ['rows' => $rows] = $this->calcularOcupacion();

        $pdf = Pdf::loadView('pdfs.reporte-ocupacion', [
            'rows' => $rows,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'diasPeriodo' => $rows->first()['dias_periodo'] ?? 0,
            'propiedadNombre' => $this->propiedadNombre(),
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->nombreArchivo('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function with(): array
    {
        ['rows' => $rows, 'totales' => $totales] = $this->calcularOcupacion();

        return [
            'grupos' => $rows->groupBy('propiedad'),
            'totales' => $totales,
            'propiedades' => Propiedad::orderBy('nombre')->get(),
        ];
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
                <flux:label class="text-xs">Propiedad</flux:label>
                <flux:select wire:model.live="propiedadId" class="w-full md:w-56">
                    <flux:select.option value="">Todas las propiedades</flux:select.option>
                    @foreach ($propiedades as $propiedad)
                        <flux:select.option value="{{ $propiedad->id }}">{{ $propiedad->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex gap-2 md:ml-auto">
                <flux:button wire:click="limpiar" variant="outline" icon="arrow-path">Limpiar</flux:button>
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

    {{-- Descripción + exportaciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <flux:text size="sm" class="text-zinc-500">
            Tiempo de ocupación por cuarto en el periodo. {{ $totales['cuartos'] }} cuartos · promedio {{ $totales['promedio'] }}%.
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

    {{-- Tabla agrupada por propiedad --}}
    @forelse ($grupos as $propiedad => $cuartos)
        <flux:card class="space-y-3">
            <flux:heading size="lg">{{ $propiedad ?? 'Sin propiedad' }}</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Cuarto</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column align="center">Rotación</flux:table.column>
                    <flux:table.column align="end">Días ocupado</flux:table.column>
                    <flux:table.column align="end">% Ocupación</flux:table.column>
                    <flux:table.column>Inquilino actual/último</flux:table.column>
                    <flux:table.column align="end">Detalle</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($cuartos as $c)
                        <flux:table.row :key="$c['cuarto_id']">
                            <flux:table.cell class="font-mono font-medium">{{ $c['codigo'] }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $color = match ($c['estado']) {
                                        Cuarto::ESTADO_DISPONIBLE => 'green',
                                        Cuarto::ESTADO_OCUPADO => 'blue',
                                        Cuarto::ESTADO_RESERVADO => 'amber',
                                        default => 'red',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$color">{{ $c['estado_label'] }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="center">{{ $c['rotacion'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $c['dias_ocupado'] }} / {{ $c['dias_periodo'] }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="hidden sm:block h-1.5 w-16 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $c['porcentaje']) }}%"></div>
                                    </div>
                                    <span class="font-semibold tabular-nums">{{ $c['porcentaje'] }}%</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $c['inquilino'] ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button href="{{ route('cuartos.detalle', $c['cuarto_id']) }}" size="xs" variant="outline" icon="eye" wire:navigate />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @empty
        <flux:card class="text-center py-10">
            <flux:text class="text-zinc-500">Sin cuartos para el filtro seleccionado.</flux:text>
        </flux:card>
    @endforelse
</div>
