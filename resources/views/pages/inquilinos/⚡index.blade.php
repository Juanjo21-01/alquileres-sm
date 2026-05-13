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
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Inquilinos</flux:heading>

        @can('create', App\Models\Inquilino::class)
            <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                Nuevo inquilino
            </flux:button>
        @endcan
    </div>

    <livewire:inquilinos.tabla />
    <livewire:inquilinos.form />
    <livewire:inquilinos.modal-eliminar />
</div>
