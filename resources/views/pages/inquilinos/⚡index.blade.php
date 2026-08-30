<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Inquilinos')] class extends Component
{
    public function abrirFormNuevo(): void
    {
        $this->dispatch('abrir-form-inquilino');
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Inquilinos" subtitle="Directorio de inquilinos.">
        @can('create', App\Models\Inquilino::class)
            <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                Nuevo inquilino
            </flux:button>
        @endcan
    </x-ui.page-header>

    <livewire:inquilinos.tabla />
    <livewire:inquilinos.form />
    <livewire:inquilinos.modal-eliminar />
</div>
