<?php

use App\Models\AlquilerParqueo;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public int $arrendatarioParqueoId;

    #[On('mes-registrado')]
    #[On('mes-pagado')]
    #[On('mes-pendiente')]
    #[On('mes-eliminado')]
    public function refrescar(): void {}

    public function abrirFormMes(): void
    {
        $this->dispatch('abrir-form-mes', arrendatarioParqueoId: $this->arrendatarioParqueoId);
    }

    public function confirmarMarcarPagado(int $id): void
    {
        $this->dispatch('confirmar-marcar-pagado', id: $id);
    }

    public function confirmarEliminarMes(int $id): void
    {
        $this->dispatch('confirmar-eliminar-mes', id: $id);
    }

    public function with(): array
    {
        $meses = AlquilerParqueo::where('arrendatario_parqueo_id', $this->arrendatarioParqueoId)
            ->orderByDesc('mes')
            ->orderByDesc('id')
            ->get();

        $totalCobrado  = $meses->where('pagado', true)->sum(fn ($m) => (float) $m->monto);
        $totalPendiente = $meses->where('pagado', false)->sum(fn ($m) => (float) $m->monto);

        return compact('meses', 'totalCobrado', 'totalPendiente');
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="md">Historial de meses</flux:heading>

        @can('create', App\Models\AlquilerParqueo::class)
            <flux:button wire:click="abrirFormMes" variant="primary" size="sm" icon="plus">
                Registrar mes
            </flux:button>
        @endcan
    </div>

    @if ($meses->isEmpty())
        <div class="rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 py-10 text-center text-zinc-400 text-sm">
            No hay meses registrados para este arrendatario.
        </div>
    @else
        {{-- Resumen rápido --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3">
                <p class="text-xs text-green-600 dark:text-green-400 uppercase tracking-wide">Total cobrado</p>
                <p class="text-xl font-bold text-green-700 dark:text-green-300">
                    Q {{ number_format($totalCobrado, 2) }}
                </p>
            </div>
            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                <p class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wide">Total pendiente</p>
                <p class="text-xl font-bold text-amber-700 dark:text-amber-300">
                    Q {{ number_format($totalPendiente, 2) }}
                </p>
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Mes</flux:table.column>
                <flux:table.column align="end">Monto</flux:table.column>
                <flux:table.column align="center">Estado</flux:table.column>
                <flux:table.column>Fecha pago</flux:table.column>
                <flux:table.column>Método</flux:table.column>
                <flux:table.column>Notas</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($meses as $mes)
                    <flux:table.row :key="$mes->id">
                        <flux:table.cell class="font-semibold">
                            {{ \Carbon\Carbon::parse($mes->mes)->translatedFormat('F Y') }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-medium">
                            Q {{ number_format((float) $mes->monto, 2) }}
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            @if ($mes->pagado)
                                <flux:badge color="green" size="sm">Pagado</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm">Pendiente</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $mes->fecha_pago ? $mes->fecha_pago->format('d/m/Y') : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($mes->pagado)
                                @if ($mes->metodo_pago === 'efectivo')
                                    <flux:badge size="sm" color="green">Efectivo</flux:badge>
                                @else
                                    <flux:badge size="sm" color="violet">Cuenta</flux:badge>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500 max-w-xs truncate">
                            {{ $mes->notas ?: '—' }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                @can('update', $mes)
                                    @if (! $mes->pagado)
                                        <flux:button
                                            wire:click="confirmarMarcarPagado({{ $mes->id }})"
                                            size="xs"
                                            variant="ghost"
                                            icon="check-circle"
                                            title="Marcar pagado" />
                                    @endif
                                @endcan

                                @can('delete', $mes)
                                    <flux:button
                                        wire:click="confirmarEliminarMes({{ $mes->id }})"
                                        size="xs"
                                        variant="danger"
                                        icon="trash" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
