<?php

use App\Models\Inquilino;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // Buscador de texto: en vivo.
    public string $busqueda = '';

    // Filtros aplicados (solo cambian con "Buscar").
    public string $ocupacion = '';
    public string $estanciaFiltro = '';

    // Borradores ligados a los controles; se aplican con "Buscar".
    public string $fOcupacion = '';
    public string $fEstancia = '';

    public int $filtrosVersion = 0;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    #[On('inquilino-guardado')]
    #[On('inquilino-eliminado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function buscar(): void
    {
        $this->ocupacion = $this->fOcupacion;
        $this->estanciaFiltro = $this->fEstancia;
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset(['busqueda', 'ocupacion', 'estanciaFiltro', 'fOcupacion', 'fEstancia']);
        $this->filtrosVersion++;
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-inquilino', id: $id);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-inquilino', id: $id);
    }


    public function with(): array
    {
        $query = Inquilino::query()
            ->with('estanciaActiva')
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('nombres', 'like', "%{$this->busqueda}%")
                    ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
                    ->orWhere('dpi', 'like', "%{$this->busqueda}%")
                    ->orWhere('telefono', 'like', "%{$this->busqueda}%")
                    ->orWhere('vehiculo_placa', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->ocupacion, fn ($q) => $q->where('ocupacion', $this->ocupacion))
            ->when($this->estanciaFiltro === 'con', fn ($q) => $q->whereHas('estanciaActiva'))
            ->when($this->estanciaFiltro === 'sin', fn ($q) => $q->whereDoesntHave('estanciaActiva'))
            ->orderBy('apellidos')
            ->orderBy('nombres');

        return [
            'inquilinos' => $query->paginate(15),
        ];
    }
}; ?>

<div class="space-y-4">
    <x-ui.filtros-card>
        <div wire:key="inquilinos-filtros-{{ $filtrosVersion }}" class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
            <flux:input
                wire:model.live.debounce.300ms="busqueda"
                icon="magnifying-glass"
                placeholder="Buscar por nombre, DPI o teléfono..."
                class="flex-1 min-w-48" />

            <flux:select wire:model="fOcupacion" class="w-full md:w-48">
                <flux:select.option value="">Todas las ocupaciones</flux:select.option>
                <flux:select.option value="estudiante">Estudiante</flux:select.option>
                <flux:select.option value="salud">Personal de salud</flux:select.option>
                <flux:select.option value="otro">Otro</flux:select.option>
            </flux:select>

            <flux:select wire:model="fEstancia" class="w-full md:w-48">
                <flux:select.option value="">Estancia: todas</flux:select.option>
                <flux:select.option value="con">Con estancia activa</flux:select.option>
                <flux:select.option value="sin">Sin estancia activa</flux:select.option>
            </flux:select>
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
    <flux:table :paginate="$inquilinos">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>DPI</flux:table.column>
            <flux:table.column>Teléfono</flux:table.column>
            <flux:table.column>Ocupación</flux:table.column>
            <flux:table.column align="center">Estancia</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($inquilinos as $inquilino)
                <flux:table.row :key="$inquilino->id">
                    <flux:table.cell class="font-medium">
                        <a href="{{ route('inquilinos.detalle', $inquilino) }}" class="hover:underline">
                            {{ $inquilino->nombre_completo }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $inquilino->dpi ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($inquilino->telefono)
                            <a href="https://wa.me/502{{ preg_replace('/\D/', '', $inquilino->telefono) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:underline">
                                {{ $inquilino->telefono }}
                                <flux:icon.chat-bubble-left-ellipsis class="size-3.5" />
                            </a>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="capitalize">{{ $inquilino->ocupacion }}</flux:table.cell>
                    <flux:table.cell align="center">
                        @if (!$inquilino->activo)
                            <flux:badge color="red" size="sm">Inactivo</flux:badge>
                        @elseif ($inquilino->estanciaActiva)
                            <flux:badge color="green" size="sm">Estancia Activa</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Sin Estancia</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button
                                href="{{ route('inquilinos.detalle', $inquilino) }}"
                                size="xs"
                                variant="outline"
                                icon="eye"
                                wire:navigate />

                            @can('update', $inquilino)
                                <flux:button
                                    wire:click="editar({{ $inquilino->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="outline" />
                            @endcan

                            @can('delete', $inquilino)
                                <flux:button
                                    wire:click="confirmarEliminar({{ $inquilino->id }})"
                                    size="xs"
                                    variant="danger"
                                    icon="trash" />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <x-ui.empty-state
                            icon="users"
                            title="No hay inquilinos"
                            description="Registra el primer inquilino o ajusta la búsqueda.">
                            @can('create', App\Models\Inquilino::class)
                                <flux:button wire:click="$dispatch('abrir-form-inquilino')" size="sm" variant="primary" icon="plus">
                                    Nuevo inquilino
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
