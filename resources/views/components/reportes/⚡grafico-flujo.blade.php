<?php

use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Livewire\Component;

new class extends Component {
    /** Vista de flujo: 'caja' (por fecha_pago) o 'devengado' (por mes_aplicado). */
    public string $vista = 'caja';

    /** Cantidad de meses hacia atrás a graficar (incluye el mes actual). */
    public int $meses = 6;

    public function with(): array
    {
        $svc = app(ReporteService::class);
        $hasta = now();
        $desde = now()->subMonths($this->meses - 1)->startOfMonth();

        $flujo = $this->vista === 'devengado'
            ? $svc->flujoDevengado($desde, $hasta)
            : $svc->flujoCaja($desde, $hasta);

        $datos = [
            'labels' => $flujo->map(fn ($m) => ucfirst(CarbonImmutable::createFromFormat('Y-m', $m['periodo'])->translatedFormat('M Y')))->values()->all(),
            'ingresos' => $flujo->map(fn ($m) => $m['ingresos'])->values()->all(),
            'egresos' => $flujo->map(fn ($m) => $m['egresos'])->values()->all(),
        ];

        return ['datos' => $datos];
    }
}; ?>

<div
    wire:ignore
    x-data="{ datos: @js($datos), chart: null }"
    x-init="chart = new window.Chart($refs.canvas, {
        type: 'bar',
        data: {
            labels: datos.labels,
            datasets: [
                { label: 'Ingresos', data: datos.ingresos, backgroundColor: '#22c55e', borderRadius: 4 },
                { label: 'Egresos', data: datos.egresos, backgroundColor: '#ef4444', borderRadius: 4 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#d4d4d8' } },
                tooltip: { callbacks: { label: (c) => c.dataset.label + ': Q ' + Number(c.parsed.y).toLocaleString('es-GT', { minimumFractionDigits: 2 }) } },
            },
            scales: {
                x: { ticks: { color: '#a1a1aa' }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: '#a1a1aa', callback: (v) => 'Q ' + Number(v).toLocaleString('es-GT') }, grid: { color: 'rgba(161,161,170,0.15)' } },
            },
        },
    })"
    class="relative h-72"
>
    <canvas x-ref="canvas"></canvas>
</div>
