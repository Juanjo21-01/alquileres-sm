<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Categorías de gasto')] class extends Component {
    public function abrirFormNueva(): void
    {
        $this->dispatch('abrir-form-categoria-gasto');
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Categorías de gasto" subtitle="Catálogo de tipos de egreso del negocio.">
        @can('create', App\Models\CategoriaGasto::class)
            <flux:button wire:click="abrirFormNueva" variant="primary" icon="plus">
                Nueva categoría
            </flux:button>
        @endcan
    </x-ui.page-header>

    {{-- Tabla reusable --}}
    <livewire:categorias-gasto.tabla />

    {{-- Formulario en modal --}}
    <livewire:categorias-gasto.form />

    {{-- Modal de eliminación --}}
    <livewire:categorias-gasto.modal-eliminar />
</div>
