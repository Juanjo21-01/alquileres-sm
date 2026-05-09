<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Tablero de cuartos')] class extends Component {
    public Propiedad $propiedad;

    public function mount(Propiedad $propiedad): void
    {
        $this->propiedad = $propiedad;
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-zinc-500 mb-1">
                <flux:button href="{{ route('propiedades.index') }}" variant="ghost" size="xs" icon="arrow-left">
                    Propiedades
                </flux:button>
            </div>
            <flux:heading size="xl">{{ $propiedad->nombre }}</flux:heading>
            <flux:subheading>{{ $propiedad->direccion }}</flux:subheading>
        </div>

        @can('create', App\Models\Cuarto::class)
            <flux:button
                wire:click="$dispatch('abrir-form-cuarto', { propiedadId: {{ $propiedad->id }} })"
                variant="primary"
                icon="plus">
                Nuevo cuarto
            </flux:button>
        @endcan
    </div>

    {{-- Tablero de cuartos --}}
    <livewire:cuartos.tabla :propiedadId="$propiedad->id" />

    {{-- Formulario en modal --}}
    <livewire:cuartos.form />

    {{-- Modal de eliminación --}}
    <livewire:cuartos.modal-eliminar />

    {{-- Modal cambiar estado --}}
    <livewire:cuartos.modal-cambiar-estado />
</div>
