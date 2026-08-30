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
    <x-ui.page-header title="Pagos" subtitle="Historial de todos los pagos registrados.">
        @can('create', App\Models\Pago::class)
            <flux:button href="{{ route('pagos.registrar') }}" variant="outline" icon="document-plus" wire:navigate>
                Registrar pago
            </flux:button>
            <flux:button wire:click="abrirFormPago" variant="primary" icon="plus">
                Pago rápido
            </flux:button>
        @endcan
    </x-ui.page-header>

    <livewire:pagos.tabla />

    {{-- Modales --}}
    <livewire:pagos.form />
    <livewire:pagos.modal-eliminar />
</div>
