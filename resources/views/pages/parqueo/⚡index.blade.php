<?php

use App\Models\ArrendatarioParqueo;
use App\Models\Inquilino;
use Livewire\Attributes\On;
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

    #[On('arrendatario-guardado')]
    #[On('arrendatario-desactivado')]
    #[On('arrendatario-activado')]
    #[On('mes-registrado')]
    #[On('mes-pagado')]
    #[On('mes-eliminado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $mesActual = now()->startOfMonth()->toDateString();

        $externosActivos = ArrendatarioParqueo::query()
            ->where('activo', true)
            ->whereHas('alquileres', fn ($q) => $q->where('mes', $mesActual))
            ->count();

        $internosActivos = Inquilino::query()
            ->whereNotNull('vehiculo_tipo')
            ->whereHas('estanciaActiva')
            ->count();

        return [
            'externosActivos' => $externosActivos,
            'internosActivos' => $internosActivos,
            'totalActivos'    => $externosActivos + $internosActivos,
            'mesLabel'        => now()->translatedFormat('F Y'),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Parqueo" subtitle="Gestión de arrendatarios y cobros de espacios de parqueo.">
        @can('create', App\Models\AlquilerParqueo::class)
            <flux:button wire:click="abrirFormMes" variant="outline" icon="calendar-days">
                Registrar mes
            </flux:button>
        @endcan

        @can('create', App\Models\ArrendatarioParqueo::class)
            <flux:button wire:click="abrirFormArrendatario" variant="primary" icon="plus">
                Nuevo arrendatario
            </flux:button>
        @endcan
    </x-ui.page-header>

    {{-- Resumen del mes actual --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500 mb-1">Total activos — {{ $mesLabel }}</p>
            <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ $totalActivos }}</p>
            <p class="text-xs text-zinc-400 mt-1">vehículos en parqueo este mes</p>
        </div>

        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-blue-500 mb-1">Externos con pago</p>
            <p class="text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $externosActivos }}</p>
            <p class="text-xs text-blue-400 mt-1">arrendatarios activos con mes registrado</p>
        </div>

        <div class="rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-violet-500 mb-1">Internos (inquilinos)</p>
            <p class="text-3xl font-bold text-violet-700 dark:text-violet-300">{{ $internosActivos }}</p>
            <p class="text-xs text-violet-400 mt-1">inquilinos con vehículo y estancia activa</p>
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
