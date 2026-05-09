<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public string $busqueda = '';
    public bool $soloActivos = true;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingSoloActivos(): void
    {
        $this->resetPage();
    }

    #[On('propiedad-guardada')]
    #[On('propiedad-eliminada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-propiedad', id: $id);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-propiedad', id: $id);
    }

    public function with(): array
    {
        $query = Propiedad::query()
            ->withCount('cuartos')
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                    ->orWhere('direccion', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->soloActivos, fn ($q) => $q->where('activo', true))
            ->orderBy('nombre');

        return [
            'propiedades' => $query->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por nombre o dirección..."
            class="flex-1" />

        <flux:switch wire:model.live="soloActivos" label="Solo activos" />
    </div>

    <flux:table :paginate="$propiedades">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Dirección</flux:table.column>
            <flux:table.column>Zona</flux:table.column>
            <flux:table.column align="center">Cuartos</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($propiedades as $propiedad)
                <flux:table.row :key="$propiedad->id">
                    <flux:table.cell class="font-medium">{{ $propiedad->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $propiedad->direccion }}</flux:table.cell>
                    <flux:table.cell>{{ $propiedad->zona ?: '—' }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $propiedad->cuartos_count }}</flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($propiedad->activo)
                            <flux:badge color="green" size="sm">Activa</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactiva</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button
                                href="{{ route('cuartos.tablero', $propiedad) }}"
                                size="xs"
                                variant="ghost">
                                Ver cuartos
                            </flux:button>

                            @can('update', $propiedad)
                                <flux:button
                                    wire:click="editar({{ $propiedad->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="ghost" />
                            @endcan

                            @can('delete', $propiedad)
                                <flux:button
                                    wire:click="confirmarEliminar({{ $propiedad->id }})"
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
                        No hay propiedades registradas.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
