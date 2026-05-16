<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Parqueo')] class extends Component {
    public function abrirFormArrendatario(): void
    {
        $this->dispatch('abrir-form-arrendatario');
    }

    public function abrirFormMes(): void
    {
        $this->dispatch('abrir-form-mes');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Parqueo</flux:heading>
            <flux:subheading>Gestión de arrendatarios y cobros de espacios de parqueo.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @can('create', App\Models\AlquilerParqueo::class)
                <flux:button
                    wire:click="abrirFormMes"
                    variant="ghost"
                    icon="calendar-days">
                    Registrar mes
                </flux:button>
            @endcan

            @can('create', App\Models\ArrendatarioParqueo::class)
                <flux:button
                    wire:click="abrirFormArrendatario"
                    variant="primary"
                    icon="plus">
                    Nuevo arrendatario
                </flux:button>
            @endcan
        </div>
    </div>

    <livewire:parqueo.tabla-arrendatarios />

    {{-- Modales --}}
    <livewire:parqueo.form-arrendatario />
    <livewire:parqueo.modal-desactivar-arrendatario />
    <livewire:parqueo.form-mes />
    <livewire:parqueo.modal-marcar-pagado />
    <livewire:parqueo.modal-eliminar-mes />
</div>
