<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Propiedades')] class extends Component {
    public function abrirFormNuevo(): void
    {
        $this->dispatch('abrir-form-propiedad');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Propiedades</flux:heading>

        @can('create', App\Models\Propiedad::class)
            <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                Nueva propiedad
            </flux:button>
        @endcan
    </div>

    {{-- Tabla reusable --}}
    <livewire:propiedades.tabla />

    {{-- Formulario en modal --}}
    <livewire:propiedades.form />

    {{-- Modal de eliminación --}}
    <livewire:propiedades.modal-eliminar />
</div>
