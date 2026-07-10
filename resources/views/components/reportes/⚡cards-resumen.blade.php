<?php

use App\Services\ReporteService;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        $svc = app(ReporteService::class);
        $mesKey = now()->format('Y-m');
        $inicio = now()->startOfMonth();
        $fin = now()->endOfMonth();

        $devengado = $svc->flujoDevengado($inicio, $fin)->get($mesKey);
        $ocupacion = $svc->ocupacionActual();

        return [
            'ocupacion' => $ocupacion,
            'ingresosMes' => (float) ($devengado['ingresos'] ?? 0),
            'gastosMes' => (float) ($devengado['egresos'] ?? 0),
            'gananciaMes' => (float) ($devengado['ganancia'] ?? 0),
        ];
    }
}; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    {{-- Ocupación --}}
    <flux:card class="space-y-1">
        <div class="flex items-center gap-2 text-zinc-500">
            <flux:icon.building-office-2 variant="micro" />
            <flux:text size="sm">Ocupación</flux:text>
        </div>
        <flux:heading size="xl">{{ $ocupacion['ocupados'] }}/{{ $ocupacion['total'] }}</flux:heading>
        <flux:text size="sm" class="text-zinc-500">{{ $ocupacion['porcentaje'] }}% ocupado</flux:text>
    </flux:card>

    {{-- Ingresos del mes (devengado) --}}
    <flux:card class="space-y-1">
        <div class="flex items-center gap-2 text-zinc-500">
            <flux:icon.banknotes variant="micro" />
            <flux:text size="sm">Ingresos del mes</flux:text>
        </div>
        <flux:heading size="xl" class="text-green-600 dark:text-green-500">
            Q {{ number_format($ingresosMes, 2) }}
        </flux:heading>
        <flux:text size="sm" class="text-zinc-500">Devengado · {{ ucfirst(now()->translatedFormat('F Y')) }}</flux:text>
    </flux:card>

    {{-- Gastos del mes --}}
    <flux:card class="space-y-1">
        <div class="flex items-center gap-2 text-zinc-500">
            <flux:icon.receipt-percent variant="micro" />
            <flux:text size="sm">Gastos del mes</flux:text>
        </div>
        <flux:heading size="xl" class="text-red-600 dark:text-red-500">
            Q {{ number_format($gastosMes, 2) }}
        </flux:heading>
        <flux:text size="sm" class="text-zinc-500">Egresos · {{ ucfirst(now()->translatedFormat('F Y')) }}</flux:text>
    </flux:card>

    {{-- Ganancia neta --}}
    <flux:card class="space-y-1">
        <div class="flex items-center gap-2 text-zinc-500">
            <flux:icon.chart-bar variant="micro" />
            <flux:text size="sm">Ganancia neta</flux:text>
        </div>
        <flux:heading size="xl" @class([
            'text-green-600 dark:text-green-500' => $gananciaMes >= 0,
            'text-red-600 dark:text-red-500' => $gananciaMes < 0,
        ])>
            Q {{ number_format($gananciaMes, 2) }}
        </flux:heading>
        <flux:text size="sm" class="text-zinc-500">Ingresos − gastos del mes</flux:text>
    </flux:card>
</div>
