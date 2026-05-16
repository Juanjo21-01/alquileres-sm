<?php

use App\Models\Inquilino;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $busqueda = '';

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
            ->orderBy('apellidos')
            ->orderBy('nombres');

        return [
            'inquilinos' => $query->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por nombre, apellido, DPI o teléfono..."
            class="max-w-sm" />
    </div>

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
                    <flux:table.cell>{{ $inquilino->telefono ?: '—' }}</flux:table.cell>
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
                                variant="ghost"
                                wire:navigate>
                                Ver
                            </flux:button>

                            @can('update', $inquilino)
                                <flux:button
                                    wire:click="editar({{ $inquilino->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="ghost" />
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
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                        No hay inquilinos registrados.
                        @can('create', App\Models\Inquilino::class)
                            <flux:button
                                wire:click="$dispatch('abrir-form-inquilino')"
                                size="sm"
                                variant="ghost"
                                class="ml-2">
                                Agregar inquilino
                            </flux:button>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
