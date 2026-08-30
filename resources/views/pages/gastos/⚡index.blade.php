<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Gastos')] class extends Component {
    public function abrirFormNuevo(): void
    {
        $this->dispatch('abrir-form-gasto');
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Gastos" subtitle="Egresos operativos del negocio.">
        @can('create', App\Models\CategoriaGasto::class)
            <flux:button href="{{ route('categorias.index') }}" variant="outline" icon="tag" wire:navigate>
                Categorías
            </flux:button>
        @endcan

        @can('create', App\Models\Gasto::class)
            <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                Nuevo gasto
            </flux:button>
        @endcan
    </x-ui.page-header>

    {{-- Tabla reusable --}}
    <livewire:gastos.tabla />

    {{-- Formulario en modal --}}
    <livewire:gastos.form />

    {{-- Modal de detalle --}}
    <livewire:gastos.modal-detalle />

    {{-- Modal de eliminación --}}
    <livewire:gastos.modal-eliminar />
</div>
