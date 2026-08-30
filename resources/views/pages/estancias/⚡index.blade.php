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
    <x-ui.page-header title="Estancias" subtitle="Contratos de alquiler por cuarto.">
        @can('create', App\Models\Estancia::class)
            <flux:button wire:click="abrirFormEstancia" variant="primary" icon="plus">
                Abrir estancia
            </flux:button>
        @endcan
    </x-ui.page-header>

    <livewire:estancias.tabla />
    <livewire:estancias.form-abrir />
    <livewire:estancias.modal-cancelar />
</div>
