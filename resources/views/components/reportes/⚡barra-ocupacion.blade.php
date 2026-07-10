<?php

use App\Services\ReporteService;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        return ['ocupacion' => app(ReporteService::class)->ocupacionActual()];
    }
}; ?>

<div class="space-y-6">
    {{-- Resumen global --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Cuartos activos</flux:text>
            <flux:heading size="xl">{{ $ocupacion['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Ocupados</flux:text>
            <flux:heading size="xl" class="text-blue-600 dark:text-blue-500">{{ $ocupacion['ocupados'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-zinc-500">Ocupación</flux:text>
            <flux:heading size="xl" class="text-emerald-600 dark:text-emerald-500">{{ $ocupacion['porcentaje'] }}%</flux:heading>
        </flux:card>
    </div>

    @if ($ocupacion['total'] === 0)
        {{-- Empty state --}}
        <flux:card class="text-center py-10 space-y-3">
            <flux:icon.building-office-2 class="mx-auto text-zinc-400" />
            <flux:text class="text-zinc-500">No hay cuartos activos registrados.</flux:text>
            <flux:button href="{{ route('propiedades.index') }}" variant="primary" size="sm" wire:navigate>
                Ir a propiedades
            </flux:button>
        </flux:card>
    @else
        {{-- Barra global --}}
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between">
                <flux:text class="font-medium">Ocupación general</flux:text>
                <flux:text size="sm" class="text-zinc-500">
                    {{ $ocupacion['ocupados'] }}/{{ $ocupacion['total'] }} cuartos · {{ $ocupacion['porcentaje'] }}%
                </flux:text>
            </div>
            <div class="h-3 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $ocupacion['porcentaje'] }}%"></div>
            </div>
        </flux:card>

        {{-- Desglose por propiedad --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Por propiedad</flux:heading>

            <div class="space-y-4">
                @forelse ($ocupacion['porPropiedad'] as $fila)
                    @php
                        $total = (int) $fila->total;
                        $ocupados = (int) $fila->ocupados;
                        $pct = $total > 0 ? round(($ocupados / $total) * 100, 1) : 0;
                    @endphp
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">{{ $fila->propiedad?->nombre ?? 'Sin propiedad' }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $ocupados }}/{{ $total }} · {{ $pct }}%
                            </flux:text>
                        </div>
                        <div class="h-2.5 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">Sin cuartos por propiedad.</flux:text>
                @endforelse
            </div>
        </flux:card>
    @endif
</div>
