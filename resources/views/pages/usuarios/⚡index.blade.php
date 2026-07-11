<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Usuarios')] class extends Component {
    public function abrirFormNuevo(): void
    {
        $this->dispatch('abrir-form-usuario');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Usuarios</flux:heading>
            <flux:subheading>Operadores del sistema con acceso al panel.</flux:subheading>
        </div>

        @can('create', App\Models\User::class)
            <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                Nuevo usuario
            </flux:button>
        @endcan
    </div>

    {{-- Tabla reusable --}}
    <livewire:usuarios.tabla />

    {{-- Formulario en modal --}}
    <livewire:usuarios.form />

    {{-- Modal de activar/desactivar --}}
    <livewire:usuarios.modal-toggle-activo />
</div>
