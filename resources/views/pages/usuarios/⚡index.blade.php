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
    <x-ui.page-header title="Usuarios" subtitle="Operadores del sistema con acceso al panel.">
        @can('create', App\Models\User::class)
            <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                Nuevo usuario
            </flux:button>
        @endcan
    </x-ui.page-header>

    {{-- Tabla reusable --}}
    <livewire:usuarios.tabla />

    {{-- Formulario en modal --}}
    <livewire:usuarios.form />

    {{-- Modal de activar/desactivar --}}
    <livewire:usuarios.modal-toggle-activo />
</div>
