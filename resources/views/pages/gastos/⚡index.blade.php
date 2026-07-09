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
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gastos</flux:heading>
            <flux:subheading>Egresos operativos del negocio.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @can('create', App\Models\CategoriaGasto::class)
                <flux:button href="{{ route('categorias.index') }}" variant="ghost" icon="tag">
                    Categorías
                </flux:button>
            @endcan

            @can('create', App\Models\Gasto::class)
                <flux:button wire:click="abrirFormNuevo" variant="primary" icon="plus">
                    Nuevo gasto
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Tabla reusable --}}
    <livewire:gastos.tabla />

    {{-- Formulario en modal --}}
    <livewire:gastos.form />

    {{-- Modal de detalle --}}
    <livewire:gastos.modal-detalle />

    {{-- Modal de eliminación --}}
    <livewire:gastos.modal-eliminar />
</div>
