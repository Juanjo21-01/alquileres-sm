<?php

use App\Models\Cuarto;
use App\Services\ReporteService;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        $ocupacion = app(ReporteService::class)->ocupacionPorEstado();

        return [
            'total' => $ocupacion['total'],
            'datos' => [
                'labels' => array_values(Cuarto::estados()),
                'data' => array_values($ocupacion['porEstado']),
            ],
        ];
    }
}; ?>

<div>
    @if ($total === 0)
        <div class="flex flex-col items-center justify-center h-64 text-center gap-2">
            <flux:icon.squares-2x2 class="text-zinc-400" />
            <flux:text class="text-zinc-500">Sin cuartos activos registrados.</flux:text>
        </div>
    @else
        {{-- Colores por estado: disponible=verde, ocupado=azul, reservado=ámbar, mantenimiento=rojo --}}
        <div
            wire:ignore
            x-data="{ datos: @js($datos), chart: null }"
            x-init="chart = new window.Chart($refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: datos.labels,
                    datasets: [{
                        data: datos.data,
                        backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'],
                        borderColor: '#27272a',
                        borderWidth: 2,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#d4d4d8', usePointStyle: true, pointStyle: 'circle', padding: 14 } },
                        tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + c.parsed + (c.parsed === 1 ? ' cuarto' : ' cuartos') } },
                    },
                },
            })"
            class="relative h-64"
        >
            <canvas x-ref="canvas"></canvas>
        </div>
    @endif
</div>
