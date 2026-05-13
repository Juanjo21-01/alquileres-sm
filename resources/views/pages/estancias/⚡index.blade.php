<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Estancias')] class extends Component
{
    public function abrirFormEstancia(): void
    {
        $this->dispatch('abrir-form-estancia');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Estancias</flux:heading>

        @can('create', App\Models\Estancia::class)
            <flux:button wire:click="abrirFormEstancia" variant="primary" icon="plus">
                Abrir estancia
            </flux:button>
        @endcan
    </div>

    <livewire:estancias.tabla />
    <livewire:estancias.form-abrir />
    <livewire:estancias.modal-cancelar />
</div>
