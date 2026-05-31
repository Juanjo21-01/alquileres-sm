<?php

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public int $estanciaId;

    #[On('pago-registrado')]
    #[On('pago-eliminado')]
    public function refrescar(): void {}

    public function abrirFormPago(): void
    {
        $this->dispatch('abrir-form-pago', estanciaId: $this->estanciaId);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-pago', id: $id);
    }

    public function with(): array
    {
        $estancia = Estancia::find($this->estanciaId);
        $estanciaEstado = $estancia?->estado;
        $puedeRegistrarPago = $this->puedeRegistrarPago($estancia);

        $pagos = Pago::with('tipoPago')
            ->where('estancia_id', $this->estanciaId)
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->get();

        $totalNeto = $pagos->sum(fn ($p) => (float) $p->monto_neto);

        // Agrupar: mensualidades por mes_aplicado, el resto en clave vacía al final
        $porMes = $pagos
            ->sortByDesc(fn ($p) => $p->mes_aplicado?->format('Y-m') ?? '0000-00')
            ->groupBy(fn ($p) => $p->mes_aplicado ? $p->mes_aplicado->format('Y-m') : '');

        return compact('pagos', 'porMes', 'totalNeto', 'estanciaEstado', 'puedeRegistrarPago');
    }

    protected function puedeRegistrarPago(?Estancia $estancia): bool
    {
        if (! $estancia) {
            return false;
        }

        if ($estancia->estado === Estancia::ESTADO_CANCELADA) {
            return false;
        }

        if ($estancia->estado === Estancia::ESTADO_ACTIVA) {
            return true;
        }

        // Finalizada: mostrar solo si hay meses sin pagar dentro del período
        $finReal = $estancia->fecha_fin ?? $estancia->fecha_fin_estimada;
        if (! $finReal) {
            return false;
        }

        $inicio = $estancia->fecha_inicio->startOfMonth();
        $fin = $finReal->startOfMonth();

        $mesesPagados = Pago::where('estancia_id', $estancia->id)
            ->whereHas('tipoPago', fn ($q) => $q->where('codigo', TipoPago::COD_MENSUALIDAD))
            ->pluck('mes_aplicado')
            ->map(fn ($m) => Carbon::parse($m)->startOfMonth()->toDateString())
            ->all();

        $cursor = $inicio->copy();
        while ($cursor->lte($fin)) {
            if (! in_array($cursor->toDateString(), $mesesPagados)) {
                return true;
            }
            $cursor = $cursor->addMonth();
        }

        return false;
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="md">Historial de pagos</flux:heading>

        @can('create', App\Models\Pago::class)
            @if ($puedeRegistrarPago)
                <flux:button wire:click="abrirFormPago" variant="primary" size="sm" icon="plus">
                    Registrar pago
                </flux:button>
            @endif
        @endcan
    </div>

    @if ($pagos->isEmpty())
        <div class="rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 py-10 text-center text-zinc-400 text-sm">
            No hay pagos registrados para esta estancia.
        </div>
    @else
        {{-- Grupos por mes --}}
        @foreach ($porMes as $claveMes => $pagosMes)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-800 px-4 py-2">
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        @if ($claveMes)
                            {{ ucfirst(\Carbon\Carbon::parse($claveMes . '-01')->translatedFormat('F Y')) }}
                        @else
                            Otros pagos
                        @endif
                    </span>
                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Q {{ number_format($pagosMes->sum(fn ($p) => (float) $p->monto_neto), 2) }}
                    </span>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column>Fecha</flux:table.column>
                        <flux:table.column align="end">Bruto</flux:table.column>
                        <flux:table.column align="end">Desc.</flux:table.column>
                        <flux:table.column align="end">Neto</flux:table.column>
                        <flux:table.column>Método</flux:table.column>
                        <flux:table.column>Recibo</flux:table.column>
                        <flux:table.column align="end">Acciones</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($pagosMes as $pago)
                            <flux:table.row :key="$pago->id">
                                <flux:table.cell>
                                    <flux:badge size="sm" color="blue">{{ $pago->tipoPago->nombre }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-sm">
                                    {{ $pago->fecha_pago->format('d/m/Y') }}
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm">
                                    Q {{ number_format((float) $pago->monto_bruto, 2) }}
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm">
                                    @if ((float) $pago->descuento > 0)
                                        <span
                                            class="text-amber-600"
                                            title="{{ $pago->motivo_descuento }}">
                                            -Q {{ number_format((float) $pago->descuento, 2) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end" class="font-semibold">
                                    Q {{ number_format((float) $pago->monto_neto, 2) }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($pago->metodo_pago === 'efectivo')
                                        <flux:badge size="sm" color="green">Efectivo</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="violet">Cuenta</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-xs text-zinc-500">
                                    {{ $pago->recibo_numero ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button
                                            href="{{ route('pagos.detalle', $pago) }}"
                                            size="xs"
                                            variant="ghost"
                                            icon="eye" />

                                        @can('delete', $pago)
                                            <flux:button
                                                wire:click="confirmarEliminar({{ $pago->id }})"
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
            </div>
        @endforeach

        {{-- Total general --}}
        <div class="flex justify-end">
            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-5 py-3 text-right">
                <div class="text-xs text-zinc-500 uppercase tracking-wide">Total cobrado</div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                    Q {{ number_format($totalNeto, 2) }}
                </div>
            </div>
        </div>
    @endif
</div>
