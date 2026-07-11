<?php

use App\Models\User;
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

    #[On('usuario-guardado')]
    #[On('usuario-estado-cambiado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-usuario', id: $id);
    }

    public function confirmarToggle(int $id): void
    {
        $this->dispatch('confirmar-toggle-usuario', id: $id);
    }

    public function with(): array
    {
        $query = User::query()
            ->with('rol')
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->busqueda}%")
                    ->orWhere('email', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->soloActivos, fn ($q) => $q->where('activo', true))
            ->orderBy('name');

        return [
            'usuarios' => $query->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por nombre o correo..."
            class="flex-1" />

        <flux:switch wire:model.live="soloActivos" label="Solo activos" />
    </div>

    <flux:table :paginate="$usuarios">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Correo</flux:table.column>
            <flux:table.column>Rol</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($usuarios as $usuario)
                <flux:table.row :key="$usuario->id">
                    <flux:table.cell class="font-medium">{{ $usuario->name }}</flux:table.cell>
                    <flux:table.cell>{{ $usuario->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $usuario->esAdministrador() ? 'purple' : 'blue' }}" size="sm">
                            {{ $usuario->rol?->nombre ?? '—' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($usuario->activo)
                            <flux:badge color="green" size="sm">Activo</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            @can('update', $usuario)
                                <flux:button
                                    wire:click="editar({{ $usuario->id }})"
                                    size="xs"
                                    icon="pencil-square"
                                    variant="ghost" />

                                @unless ($usuario->esAdministrador())
                                    <flux:button
                                        wire:click="confirmarToggle({{ $usuario->id }})"
                                        size="xs"
                                        icon="{{ $usuario->activo ? 'lock-closed' : 'lock-open' }}"
                                        variant="{{ $usuario->activo ? 'danger' : 'ghost' }}" />
                                @endunless
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-8">
                        No hay usuarios registrados.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
