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

<div class="space-y-4">
    <x-ui.filtros-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce.300ms="busqueda"
                icon="magnifying-glass"
                placeholder="Buscar por nombre o dirección..."
                class="flex-1" />

            <flux:switch wire:model.live="soloActivos" label="Solo activos" />
        </div>
    </x-ui.filtros-card>

    {{-- Skeleton mientras se filtra --}}
    <div wire:loading.delay wire:target="busqueda, soloActivos">
        <x-ui.tabla-skeleton :cols="6" />
    </div>

    <div wire:loading.remove.delay wire:target="busqueda, soloActivos">
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
                                icon="squares-2x2"
                                wire:navigate>
                                Ver cuartos
                            </flux:button>

                            @can('update', $propiedad)
                                <flux:button
                                    wire:click="editar({{ $propiedad->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="outline" />
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
                    <flux:table.cell colspan="6">
                        <x-ui.empty-state
                            icon="building-office-2"
                            title="No hay propiedades"
                            description="Registra tu primera casa de alquiler para empezar.">
                            @can('create', App\Models\Propiedad::class)
                                <flux:button variant="primary" icon="plus" size="sm" wire:click="$dispatch('abrir-form-propiedad')">
                                    Nueva propiedad
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
