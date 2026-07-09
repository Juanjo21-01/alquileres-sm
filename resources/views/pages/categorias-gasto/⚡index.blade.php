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
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Categorías de gasto</flux:heading>
            <flux:subheading>Catálogo de tipos de egreso del negocio.</flux:subheading>
        </div>

        @can('create', App\Models\CategoriaGasto::class)
            <flux:button wire:click="abrirFormNueva" variant="primary" icon="plus">
                Nueva categoría
            </flux:button>
        @endcan
    </div>

    {{-- Tabla reusable --}}
    <livewire:categorias-gasto.tabla />

    {{-- Formulario en modal --}}
    <livewire:categorias-gasto.form />

    {{-- Modal de eliminación --}}
    <livewire:categorias-gasto.modal-eliminar />
</div>
