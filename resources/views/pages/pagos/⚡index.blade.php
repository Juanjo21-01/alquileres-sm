<?php

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pagos')] class extends Component {
    public function mount(): void
    {
        if (session()->has('toast_success')) {
            Flux::toast(text: session('toast_success'), variant: 'success');
        }
    }

    public function abrirFormPago(): void
    {
        $this->dispatch('abrir-form-pago');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Pagos</flux:heading>
            <flux:subheading>Historial de todos los pagos registrados.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @can('create', App\Models\Pago::class)
                <flux:button
                    href="{{ route('pagos.registrar') }}"
                    variant="ghost"
                    icon="document-plus">
                    Registrar pago
                </flux:button>
                <flux:button
                    wire:click="abrirFormPago"
                    variant="primary"
                    icon="plus">
                    Pago rápido
                </flux:button>
            @endcan
        </div>
    </div>

    <livewire:pagos.tabla />

    {{-- Modales --}}
    <livewire:pagos.form />
    <livewire:pagos.modal-eliminar />
</div>
