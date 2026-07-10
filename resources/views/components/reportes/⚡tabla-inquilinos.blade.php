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

    /** 'todos' | 'estudiante' | 'salud' | 'otro'. */
    public string $ocupacion = 'todos';

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
        $this->ocupacion = 'todos';
        $this->setFechasPeriodo();
    }

    private function calcular(): \Illuminate\Support\Collection
    {
        return app(ReporteService::class)->resumenInquilinos(
            Carbon::parse($this->desde),
            Carbon::parse($this->hasta),
            $this->ocupacion,
        );
    }

    private function nombreArchivo(string $extension): string
    {
        return "inquilinos-{$this->desde}-a-{$this->hasta}.{$extension}";
    }

    public function exportarCsv()
    {
        $rows = $this->calcular();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Inquilino', 'Ocupacion', 'Estancias', 'Cuartos', 'Dias ocupado', 'Cuarto actual', 'Activo'], escape: '');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['nombre'],
                    $r['ocupacion'],
                    $r['estancias'],
                    $r['cuartos'],
                    $r['dias_ocupado'],
                    $r['cuarto_actual'] ?? '',
                    $r['activo'] ? 'Si' : 'No',
                ], escape: '');
            }
            fclose($out);
        }, $this->nombreArchivo('csv'), ['Content-Type' => 'text/csv']);
    }

    public function exportarPdf()
    {
        $rows = $this->calcular();

        $pdf = Pdf::loadView('pdfs.reporte-inquilinos', [
            'rows' => $rows,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'ocupacion' => $this->ocupacion,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->nombreArchivo('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function with(): array
    {
        $rows = $this->calcular();

        return [
            'rows' => $rows,
            'totales' => [
                'inquilinos' => $rows->count(),
                'dias' => (int) $rows->sum('dias_ocupado'),
            ],
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
                <flux:label class="text-xs">Ocupación</flux:label>
                <flux:select wire:model.live="ocupacion" class="w-full md:w-44">
                    <flux:select.option value="todos">Todas</flux:select.option>
                    <flux:select.option value="estudiante">Estudiante</flux:select.option>
                    <flux:select.option value="salud">Salud</flux:select.option>
                    <flux:select.option value="otro">Otro</flux:select.option>
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

    {{-- Descripción + exportaciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <flux:text size="sm" class="text-zinc-500">
            Actividad de estancias por inquilino en el periodo. {{ $totales['inquilinos'] }} inquilinos · {{ $totales['dias'] }} días acumulados.
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
            <flux:table.column>Inquilino</flux:table.column>
            <flux:table.column>Ocupación</flux:table.column>
            <flux:table.column align="center">Estancias</flux:table.column>
            <flux:table.column align="center">Cuartos</flux:table.column>
            <flux:table.column align="end">Días ocupado</flux:table.column>
            <flux:table.column>Cuarto actual</flux:table.column>
            <flux:table.column align="end">Detalle</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $r)
                <flux:table.row :key="$r['inquilino_id']">
                    <flux:table.cell class="font-medium">
                        {{ $r['nombre'] }}
                        @unless ($r['activo'])
                            <flux:badge size="sm" color="zinc" class="ml-1">Inactivo</flux:badge>
                        @endunless
                    </flux:table.cell>
                    <flux:table.cell class="capitalize">{{ $r['ocupacion'] }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $r['estancias'] }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $r['cuartos'] }}</flux:table.cell>
                    <flux:table.cell align="end" class="font-semibold tabular-nums">{{ $r['dias_ocupado'] }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($r['cuarto_actual'])
                            <flux:badge size="sm" color="blue">{{ $r['cuarto_actual'] }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button href="{{ route('inquilinos.detalle', $r['inquilino_id']) }}" size="xs" variant="ghost" icon="eye" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-8">
                        Sin inquilinos con actividad en el periodo.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
