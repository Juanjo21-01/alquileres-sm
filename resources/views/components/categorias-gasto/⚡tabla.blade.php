<?php

use App\Models\CategoriaGasto;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public string $busqueda = '';
    public bool $soloActivas = true;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingSoloActivas(): void
    {
        $this->resetPage();
    }

    #[On('categoria-gasto-guardada')]
    #[On('categoria-gasto-eliminada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-categoria-gasto', id: $id);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-categoria-gasto', id: $id);
    }

    public function with(): array
    {
        $query = CategoriaGasto::query()
            ->withCount('gastos')
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                    ->orWhere('codigo', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->soloActivas, fn ($q) => $q->where('activo', true))
            ->orderBy('nombre');

        return [
            'categorias' => $query->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por nombre o código..."
            class="flex-1" />

        <flux:switch wire:model.live="soloActivas" label="Solo activas" />
    </div>

    <flux:table :paginate="$categorias">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Código</flux:table.column>
            <flux:table.column align="center">Requiere propiedad</flux:table.column>
            <flux:table.column align="center">Requiere cuarto</flux:table.column>
            <flux:table.column align="center">Gastos</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($categorias as $categoria)
                <flux:table.row :key="$categoria->id">
                    <flux:table.cell class="font-medium">{{ $categoria->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $categoria->codigo ?: '—' }}</flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($categoria->requiere_propiedad)
                            <flux:badge color="amber" size="sm">Sí</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">No</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($categoria->requiere_cuarto)
                            <flux:badge color="amber" size="sm">Sí</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">No</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="center">{{ $categoria->gastos_count }}</flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($categoria->activo)
                            <flux:badge color="green" size="sm">Activa</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactiva</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            @can('update', $categoria)
                                <flux:button
                                    wire:click="editar({{ $categoria->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="ghost" />
                            @endcan

                            @can('delete', $categoria)
                                <flux:button
                                    wire:click="confirmarEliminar({{ $categoria->id }})"
                                    size="xs"
                                    variant="danger"
                                    icon="trash" />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-8">
                        No hay categorías registradas.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
