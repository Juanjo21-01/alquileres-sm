<?php

use App\Models\Gasto;
use App\Models\Pago;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function with(): array
    {
        $ultimosPagos = Pago::with(['estancia.inquilino', 'estancia.cuarto.propiedad', 'tipoPago'])
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $ultimosGastos = Gasto::with(['categoria', 'propiedad'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return compact('ultimosPagos', 'ultimosGastos');
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:subheading>Resumen del mes y flujo reciente.</flux:subheading>
    </div>

    {{-- Cards resumen del mes --}}
    <livewire:reportes.cards-resumen />

    {{-- Gráfico de flujo (últimos 6 meses, caja) --}}
    <flux:card class="space-y-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Flujo de caja</flux:heading>
            <flux:badge size="sm" color="zinc">Últimos 6 meses</flux:badge>
        </div>
        <livewire:reportes.grafico-flujo :meses="6" vista="caja" />
    </flux:card>

    {{-- Ocupación --}}
    <div class="space-y-3">
        <flux:heading size="lg">Ocupación de cuartos</flux:heading>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <flux:card class="space-y-3">
                <flux:heading size="sm">Cuartos por estado</flux:heading>
                <livewire:reportes.grafico-estados />
            </flux:card>
            <div class="lg:col-span-2">
                <livewire:reportes.barra-ocupacion />
            </div>
        </div>
    </div>

    {{-- Últimos movimientos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        {{-- Últimos pagos --}}
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Últimos pagos</flux:heading>
                <flux:button href="{{ route('pagos.index') }}" size="xs" variant="ghost" wire:navigate>
                    Ver todos
                </flux:button>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Inquilino</flux:table.column>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column align="end">Neto</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($ultimosPagos as $pago)
                        <flux:table.row :key="'pago-'.$pago->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $pago->fecha_pago->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $pago->estancia?->inquilino?->nombre_completo ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="blue">{{ $pago->tipoPago->nombre }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="font-semibold">Q {{ number_format((float) $pago->monto_neto, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500 py-6">Sin pagos registrados.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Últimos gastos --}}
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Últimos gastos</flux:heading>
                <flux:button href="{{ route('gastos.index') }}" size="xs" variant="ghost" wire:navigate>
                    Ver todos
                </flux:button>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column>Propiedad</flux:table.column>
                    <flux:table.column align="end">Monto</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($ultimosGastos as $gasto)
                        <flux:table.row :key="'gasto-'.$gasto->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $gasto->fecha->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $gasto->categoria?->nombre ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $gasto->propiedad?->nombre ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-semibold">Q {{ number_format((float) $gasto->monto, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500 py-6">Sin gastos registrados.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
