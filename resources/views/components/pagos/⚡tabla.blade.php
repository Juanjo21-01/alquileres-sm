<?php

use App\Models\Pago;
use App\Models\TipoPago;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $busqueda = '';
    public string $tipoId = '';
    public string $metodo = '';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    public function updatingBusqueda(): void { $this->resetPage(); }
    public function updatingTipoId(): void { $this->resetPage(); }
    public function updatingMetodo(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }

    #[On('pago-registrado')]
    #[On('pago-eliminado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-pago', id: $id);
    }

    public function with(): array
    {
        $pagos = Pago::with(['estancia.inquilino', 'estancia.cuarto.propiedad', 'tipoPago'])
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('recibo_numero', 'like', "%{$this->busqueda}%")
                    ->orWhereHas('estancia.inquilino', fn ($q) => $q
                        ->where('nombres', 'like', "%{$this->busqueda}%")
                        ->orWhere('apellidos', 'like', "%{$this->busqueda}%"));
            }))
            ->when($this->tipoId, fn ($q) => $q->where('tipo_pago_id', $this->tipoId))
            ->when($this->metodo, fn ($q) => $q->where('metodo_pago', $this->metodo))
            ->when($this->fechaDesde, fn ($q) => $q->whereDate('fecha_pago', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn ($q) => $q->whereDate('fecha_pago', '<=', $this->fechaHasta))
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->paginate(20);

        $tipos = TipoPago::orderBy('nombre')->get();

        return compact('pagos', 'tipos');
    }
}; ?>

<div>
    {{-- Filtros --}}
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por recibo o inquilino..."
            class="flex-1" />

        <flux:select wire:model.live="tipoId" class="w-48">
            <flux:select.option value="">Todos los tipos</flux:select.option>
            @foreach ($tipos as $tipo)
                <flux:select.option value="{{ $tipo->id }}">{{ $tipo->nombre }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="metodo" class="w-44">
            <flux:select.option value="">Todos los métodos</flux:select.option>
            <flux:select.option value="efectivo">Efectivo</flux:select.option>
            <flux:select.option value="cuenta">Cuenta</flux:select.option>
        </flux:select>

        <flux:input wire:model.live="fechaDesde" type="date" class="w-40" />
        <flux:input wire:model.live="fechaHasta" type="date" class="w-40" />
    </div>

    <flux:table :paginate="$pagos">
        <flux:table.columns>
            <flux:table.column>Fecha</flux:table.column>
            <flux:table.column>Inquilino</flux:table.column>
            <flux:table.column>Cuarto / Casa</flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column>Mes</flux:table.column>
            <flux:table.column align="end">Bruto</flux:table.column>
            <flux:table.column align="end">Desc.</flux:table.column>
            <flux:table.column align="end">Neto</flux:table.column>
            <flux:table.column>Método</flux:table.column>
            <flux:table.column>Recibo</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($pagos as $pago)
                <flux:table.row :key="$pago->id">
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $pago->fecha_pago->format('d/m/Y') }}
                    </flux:table.cell>
                    <flux:table.cell class="font-medium">
                        {{ $pago->estancia->inquilino->nombre_completo }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="font-mono text-sm">{{ $pago->estancia->cuarto->codigo }}</span>
                        <span class="text-zinc-400 text-xs"> · {{ $pago->estancia->cuarto->propiedad->nombre }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue">{{ $pago->tipoPago->nombre }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ ucfirst($pago->mes_aplicado ? $pago->mes_aplicado->translatedFormat('F Y') : '—') }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="text-sm">
                        Q {{ number_format((float) $pago->monto_bruto, 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="text-sm">
                        @if ((float) $pago->descuento > 0)
                            <span class="text-amber-600">-Q {{ number_format((float) $pago->descuento, 2) }}</span>
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
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="11" class="text-center text-zinc-500 py-10">
                        No hay pagos registrados.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
