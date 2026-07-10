<?php

use App\Models\Cuarto;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle de cuarto')] class extends Component {
    public Cuarto $cuarto;

    /** 'mes_actual' | 'este_anio' | 'personalizado'. */
    public string $periodo = 'este_anio';

    public string $desde = '';

    public string $hasta = '';

    public function mount(Cuarto $cuarto): void
    {
        $this->authorize('view', $cuarto);
        $this->cuarto = $cuarto->load('propiedad');
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
        $this->setFechasPeriodo();
    }

    private function datos(): array
    {
        return app(ReporteService::class)->historialCuarto(
            $this->cuarto,
            Carbon::parse($this->desde),
            Carbon::parse($this->hasta),
        );
    }

    private function nombreArchivo(string $extension): string
    {
        return "cuarto-{$this->cuarto->codigo}-{$this->desde}-a-{$this->hasta}.{$extension}";
    }

    public function exportarCsv()
    {
        $datos = $this->datos();

        return response()->streamDownload(function () use ($datos) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Inquilino', 'Inicio', 'Fin', 'Dias', 'Estado', 'Precio acordado'], escape: '');
            foreach ($datos['estancias'] as $e) {
                fputcsv($out, [
                    $e['inquilino'],
                    $e['inicio']?->format('Y-m-d'),
                    $e['fin']?->format('Y-m-d') ?? 'activa',
                    $e['dias'],
                    $e['estado'],
                    $e['precio'],
                ], escape: '');
            }
            fclose($out);
        }, $this->nombreArchivo('csv'), ['Content-Type' => 'text/csv']);
    }

    public function exportarPdf()
    {
        $datos = $this->datos();

        $pdf = Pdf::loadView('pdfs.historial-cuarto', [
            'cuarto' => $this->cuarto,
            'estancias' => $datos['estancias'],
            'stats' => $datos['stats'],
            'desde' => $this->desde,
            'hasta' => $this->hasta,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $this->nombreArchivo('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function with(): array
    {
        return $this->datos();
    }
}; ?>

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <flux:button href="{{ route('cuartos.tablero', $cuarto->propiedad) }}" variant="ghost" size="xs" icon="arrow-left" wire:navigate>
            {{ $cuarto->propiedad->nombre }}
        </flux:button>
    </div>

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">Cuarto {{ $cuarto->codigo }}</flux:heading>
            <flux:subheading>
                {{ $cuarto->propiedad->nombre }} · Nivel {{ $cuarto->nivel }}
                @if ($cuarto->tamano) · {{ $cuarto->tamano }} @endif
                · Q {{ number_format((float) $cuarto->precio_base, 2) }} base
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @php
                $estadoColor = match ($cuarto->estado) {
                    Cuarto::ESTADO_DISPONIBLE => 'green',
                    Cuarto::ESTADO_OCUPADO => 'blue',
                    Cuarto::ESTADO_RESERVADO => 'amber',
                    default => 'red',
                };
            @endphp
            <flux:badge :color="$estadoColor">{{ Cuarto::estados()[$cuarto->estado] ?? $cuarto->estado }}</flux:badge>

            <flux:button wire:click="exportarCsv" icon="table-cells" variant="ghost" size="sm">CSV</flux:button>
            <flux:button wire:click="exportarPdf" icon="document-arrow-down" variant="ghost" size="sm">PDF</flux:button>
        </div>
    </div>

    {{-- Filtro de período --}}
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <flux:label class="text-xs">Período de estadísticas</flux:label>
            <flux:select wire:model.live="periodo" class="w-full sm:w-48">
                <flux:select.option value="mes_actual">Mes actual</flux:select.option>
                <flux:select.option value="este_anio">Este año</flux:select.option>
                <flux:select.option value="personalizado">Rango personalizado</flux:select.option>
            </flux:select>
        </div>

        @if ($periodo === 'personalizado')
            <div>
                <flux:label class="text-xs">Desde</flux:label>
                <flux:input wire:model.live.debounce.500ms="desde" type="date" class="w-full sm:w-44" />
            </div>
            <div>
                <flux:label class="text-xs">Hasta</flux:label>
                <flux:input wire:model.live.debounce.500ms="hasta" type="date" class="w-full sm:w-44" />
            </div>
            <flux:button wire:click="limpiar" variant="ghost" size="sm" icon="arrow-path">Limpiar</flux:button>
        @endif
    </div>

    {{-- Estadísticas del período --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Rotación</flux:text>
            <flux:heading size="xl">{{ $stats['rotacion'] }}</flux:heading>
            <flux:text size="sm" class="text-zinc-500">estancias</flux:text>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Días ocupado</flux:text>
            <flux:heading size="xl">{{ $stats['dias_ocupado'] }}</flux:heading>
            <flux:text size="sm" class="text-zinc-500">de {{ $stats['dias_periodo'] }} días</flux:text>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Ocupación</flux:text>
            <flux:heading size="xl" class="text-emerald-600 dark:text-emerald-500">{{ $stats['porcentaje'] }}%</flux:heading>
            <flux:text size="sm" class="text-zinc-500">del período</flux:text>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Estado actual</flux:text>
            <flux:heading size="lg" class="pt-1">
                <flux:badge :color="$estadoColor" size="sm">{{ Cuarto::estados()[$cuarto->estado] ?? $cuarto->estado }}</flux:badge>
            </flux:heading>
        </flux:card>
    </div>

    {{-- Historial completo de estancias --}}
    <flux:card class="space-y-3">
        <flux:heading size="lg">Historial de estancias</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Inquilino</flux:table.column>
                <flux:table.column>Inicio</flux:table.column>
                <flux:table.column>Fin</flux:table.column>
                <flux:table.column align="end">Días</flux:table.column>
                <flux:table.column align="center">Estado</flux:table.column>
                <flux:table.column align="end">Precio</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($estancias as $e)
                    <flux:table.row :key="$e['estancia_id']">
                        <flux:table.cell class="font-medium">{{ $e['inquilino'] ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $e['inicio']->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $e['fin']?->format('d/m/Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $e['dias'] }}</flux:table.cell>
                        <flux:table.cell align="center">
                            @php
                                $ec = match ($e['estado']) {
                                    'activa' => 'green',
                                    'finalizada' => 'blue',
                                    default => 'red',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$ec">{{ ucfirst($e['estado']) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">Q {{ number_format($e['precio'], 2) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ route('estancias.detalle', $e['estancia_id']) }}" size="xs" variant="ghost" icon="eye" wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500 py-8">
                            Este cuarto no tiene estancias registradas.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
