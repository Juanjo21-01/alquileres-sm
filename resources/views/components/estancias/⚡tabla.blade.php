<?php

use App\Models\Estancia;
use App\Models\Propiedad;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // Buscador de texto: en vivo (inquilino o cuarto).
    public string $busqueda = '';

    // Filtros aplicados (solo cambian con "Buscar").
    public string $estado = '';
    public string $propiedadId = '';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    // Borradores ligados a los controles; se aplican con "Buscar".
    public string $fEstado = '';
    public string $fPropiedad = '';
    public string $fDesde = '';
    public string $fHasta = '';

    public int $filtrosVersion = 0;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    #[On('estancia-abierta')]
    #[On('estancia-cerrada')]
    #[On('estancia-cancelada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function buscar(): void
    {
        $this->estado = $this->fEstado;
        $this->propiedadId = $this->fPropiedad;
        $this->fechaDesde = $this->fDesde;
        $this->fechaHasta = $this->fHasta;
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset([
            'busqueda',
            'estado', 'propiedadId', 'fechaDesde', 'fechaHasta',
            'fEstado', 'fPropiedad', 'fDesde', 'fHasta',
        ]);
        $this->filtrosVersion++;
        $this->resetPage();
    }

    public function with(): array
    {
        $estancias = Estancia::query()
            ->with(['inquilino', 'cuarto.propiedad'])
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->whereHas('inquilino', fn ($q) => $q
                    ->where('nombres', 'like', "%{$this->busqueda}%")
                    ->orWhere('apellidos', 'like', "%{$this->busqueda}%"))
                    ->orWhereHas('cuarto', fn ($q) => $q->where('codigo', 'like', "%{$this->busqueda}%"));
            }))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->propiedadId, fn ($q) => $q->whereHas('cuarto', fn ($q) => $q->where('propiedad_id', $this->propiedadId)))
            ->when($this->fechaDesde, fn ($q) => $q->whereDate('fecha_inicio', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn ($q) => $q->whereDate('fecha_inicio', '<=', $this->fechaHasta))
            ->orderByDesc('created_at')
            ->paginate(15);

        return [
            'estancias' => $estancias,
            'propiedades' => Propiedad::orderBy('nombre')->get(),
        ];
    }
}; ?>

<div class="space-y-4">
    <x-ui.filtros-card>
        <div wire:key="estancias-filtros-{{ $filtrosVersion }}" class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
            <flux:input
                wire:model.live.debounce.300ms="busqueda"
                icon="magnifying-glass"
                placeholder="Buscar por inquilino o cuarto..."
                class="flex-1 min-w-48" />

            <flux:select wire:model="fEstado" class="w-full md:w-40">
                <flux:select.option value="">Todos los estados</flux:select.option>
                <flux:select.option value="activa">Activa</flux:select.option>
                <flux:select.option value="finalizada">Finalizada</flux:select.option>
                <flux:select.option value="cancelada">Cancelada</flux:select.option>
            </flux:select>

            <flux:select wire:model="fPropiedad" class="w-full md:w-48">
                <flux:select.option value="">Todas las propiedades</flux:select.option>
                @foreach ($propiedades as $propiedad)
                    <flux:select.option value="{{ $propiedad->id }}">{{ $propiedad->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex flex-col gap-1">
                <flux:label class="text-xs">Inicio desde</flux:label>
                <flux:input wire:model="fDesde" type="date" class="w-full md:w-40" />
            </div>

            <div class="flex flex-col gap-1">
                <flux:label class="text-xs">Inicio hasta</flux:label>
                <flux:input wire:model="fHasta" type="date" class="w-full md:w-40" />
            </div>
        </div>

        <x-slot:actions>
            <flux:button wire:click="limpiar" variant="outline" icon="x-mark">Limpiar</flux:button>
            <flux:button wire:click="buscar" variant="primary" icon="magnifying-glass">Buscar</flux:button>
        </x-slot:actions>
    </x-ui.filtros-card>

    {{-- Skeleton mientras se filtra --}}
    <div wire:loading.delay wire:target="busqueda, buscar, limpiar">
        <x-ui.tabla-skeleton :cols="6" />
    </div>

    <div wire:loading.remove.delay wire:target="busqueda, buscar, limpiar">
    <flux:table :paginate="$estancias">
        <flux:table.columns>
            <flux:table.column>Inquilino</flux:table.column>
            <flux:table.column>Cuarto</flux:table.column>
            <flux:table.column>Inicio</flux:table.column>
            <flux:table.column>Precio</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($estancias as $estancia)
                <flux:table.row :key="$estancia->id">
                    <flux:table.cell class="font-medium">
                        {{ $estancia->inquilino->nombre_completo }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $estancia->cuarto->propiedad->nombre }} — {{ $estancia->cuarto->codigo }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $estancia->fecha_inicio->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>Q {{ number_format((float) $estancia->precio_acordado, 2) }}</flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($estancia->estado === 'activa')
                            <flux:badge color="green" size="sm">Activa</flux:badge>
                        @elseif ($estancia->estado === 'finalizada')
                            <flux:badge color="blue" size="sm">Finalizada</flux:badge>
                        @elseif ($estancia->estado === 'cancelada')
                            <flux:badge color="red" size="sm">Cancelada</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button
                            href="{{ route('estancias.detalle', $estancia) }}"
                            size="xs"
                            icon="eye"
                            variant="outline"/>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <x-ui.empty-state
                            icon="home"
                            title="No hay estancias"
                            description="Abre la primera estancia o cambia el filtro de estado.">
                            @can('create', App\Models\Estancia::class)
                                <flux:button wire:click="$dispatch('abrir-form-estancia')" size="sm" variant="primary" icon="plus">
                                    Abrir estancia
                                </flux:button>
                            @endcan
                        </x-ui.empty-state>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    </div>
</div>
