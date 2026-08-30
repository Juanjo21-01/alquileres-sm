<?php

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\Propiedad;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Buscador de texto: en vivo (se aplica al escribir).
    public string $busqueda = '';

    // Filtros aplicados (se usan en la consulta; solo cambian al pulsar "Buscar").
    public string $categoriaId = '';
    public string $propiedadId = '';
    public string $metodo = '';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    // Borradores ligados a los controles; se aplican al pulsar "Buscar".
    public string $fCategoria = '';
    public string $fPropiedad = '';
    public string $fMetodo = '';
    public string $fDesde = '';
    public string $fHasta = '';

    // Se incrementa en limpiar() para forzar el re-montaje de los controles
    // (garantiza que los selects/fechas diferidos se vacíen también en el cliente).
    public int $filtrosVersion = 0;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    #[On('gasto-guardado')]
    #[On('gasto-eliminado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function buscar(): void
    {
        $this->categoriaId = $this->fCategoria;
        $this->propiedadId = $this->fPropiedad;
        $this->metodo = $this->fMetodo;
        $this->fechaDesde = $this->fDesde;
        $this->fechaHasta = $this->fHasta;
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset([
            'busqueda',
            'categoriaId', 'propiedadId', 'metodo', 'fechaDesde', 'fechaHasta',
            'fCategoria', 'fPropiedad', 'fMetodo', 'fDesde', 'fHasta',
        ]);
        $this->filtrosVersion++;
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-gasto', id: $id);
    }

    public function verDetalle(int $id): void
    {
        $this->dispatch('abrir-detalle-gasto', id: $id);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-gasto', id: $id);
    }

    protected function baseQuery()
    {
        return Gasto::query()
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('descripcion', 'like', "%{$this->busqueda}%")
                    ->orWhere('proveedor', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->categoriaId, fn ($q) => $q->where('categoria_gasto_id', $this->categoriaId))
            ->when($this->propiedadId, fn ($q) => $q->where('propiedad_id', $this->propiedadId))
            ->when($this->metodo, fn ($q) => $q->where('metodo_pago', $this->metodo))
            ->when($this->fechaDesde, fn ($q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn ($q) => $q->whereDate('fecha', '<=', $this->fechaHasta));
    }

    public function with(): array
    {
        $gastos = $this->baseQuery()
            ->with(['categoria', 'propiedad', 'cuarto'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20);

        return [
            'gastos'        => $gastos,
            'totalFiltrado' => (float) $this->baseQuery()->sum('monto'),
            'categorias'    => CategoriaGasto::orderBy('nombre')->get(),
            'propiedades'   => Propiedad::orderBy('nombre')->get(),
        ];
    }
}; ?>

<div class="space-y-4">
    <x-ui.filtros-card>
        <div wire:key="gastos-filtros-{{ $filtrosVersion }}" class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
            <flux:input
                wire:model.live.debounce.300ms="busqueda"
                icon="magnifying-glass"
                placeholder="Buscar por descripción o proveedor..."
                class="flex-1 min-w-48" />

            <flux:select wire:model="fCategoria" class="w-full md:w-44">
                <flux:select.option value="">Todas las categorías</flux:select.option>
                @foreach ($categorias as $categoria)
                    <flux:select.option value="{{ $categoria->id }}">{{ $categoria->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="fPropiedad" class="w-full md:w-44">
                <flux:select.option value="">Todas las propiedades</flux:select.option>
                @foreach ($propiedades as $propiedad)
                    <flux:select.option value="{{ $propiedad->id }}">{{ $propiedad->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="fMetodo" class="w-full md:w-40">
                <flux:select.option value="">Todos los métodos</flux:select.option>
                <flux:select.option value="efectivo">Efectivo</flux:select.option>
                <flux:select.option value="cuenta">Cuenta</flux:select.option>
            </flux:select>

            <div class="flex flex-col gap-1">
                <flux:label class="text-xs">Desde</flux:label>
                <flux:input wire:model="fDesde" type="date" class="w-full md:w-40" />
            </div>

            <div class="flex flex-col gap-1">
                <flux:label class="text-xs">Hasta</flux:label>
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
        <x-ui.tabla-skeleton :cols="8" />
    </div>

    <div wire:loading.remove.delay wire:target="busqueda, buscar, limpiar">
    <flux:table :paginate="$gastos">
        <flux:table.columns>
            <flux:table.column>Fecha</flux:table.column>
            <flux:table.column>Categoría</flux:table.column>
            <flux:table.column>Propiedad / Cuarto</flux:table.column>
            <flux:table.column>Descripción</flux:table.column>
            <flux:table.column>Proveedor</flux:table.column>
            <flux:table.column align="end">Monto</flux:table.column>
            <flux:table.column>Método</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($gastos as $gasto)
                <flux:table.row :key="$gasto->id">
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $gasto->fecha->format('d/m/Y') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ $gasto->categoria->nombre }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        @if ($gasto->propiedad)
                            {{ $gasto->propiedad->nombre }}
                            @if ($gasto->cuarto)
                                <span class="text-zinc-400">· {{ $gasto->cuarto->codigo }}</span>
                            @endif
                        @else
                            <span class="text-zinc-400">General</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $gasto->descripcion }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $gasto->proveedor ?: '—' }}</flux:table.cell>
                    <flux:table.cell align="end" class="font-semibold whitespace-nowrap">
                        Q {{ number_format((float) $gasto->monto, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($gasto->metodo_pago === 'efectivo')
                            <flux:badge size="sm" color="green">Efectivo</flux:badge>
                        @else
                            <flux:badge size="sm" color="violet">Cuenta</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button
                                wire:click="verDetalle({{ $gasto->id }})"
                                size="xs"
                                icon="eye"
                                variant="outline" />

                            @can('update', $gasto)
                                <flux:button
                                    wire:click="editar({{ $gasto->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="outline" />
                            @endcan

                            @can('delete', $gasto)
                                <flux:button
                                    wire:click="confirmarEliminar({{ $gasto->id }})"
                                    size="xs"
                                    variant="danger"
                                    icon="trash" />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        <x-ui.empty-state
                            icon="receipt-percent"
                            title="No hay gastos"
                            description="Registra el primer egreso o ajusta los filtros.">
                            @can('create', App\Models\Gasto::class)
                                <flux:button variant="primary" icon="plus" size="sm" wire:click="$dispatch('abrir-form-gasto')">
                                    Nuevo gasto
                                </flux:button>
                            @endcan
                        </x-ui.empty-state>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Total filtrado --}}
    <div class="mt-4 flex justify-end">
        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 px-4 py-2.5 flex items-center gap-3">
            <span class="text-sm text-zinc-600 dark:text-zinc-400">Total filtrado</span>
            <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                Q {{ number_format($totalFiltrado, 2) }}
            </span>
        </div>
    </div>
    </div>
</div>
