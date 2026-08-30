<?php

use App\Models\ArrendatarioParqueo;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle arrendatario')] class extends Component {
    public ArrendatarioParqueo $arrendatario;

    public function mount(int $id): void
    {
        $this->arrendatario = ArrendatarioParqueo::findOrFail($id);
        $this->authorize('view', $this->arrendatario);
    }

    #[On('arrendatario-guardado')]
    #[On('arrendatario-desactivado')]
    public function refrescarArrendatario(): void
    {
        $this->arrendatario = $this->arrendatario->fresh();
    }

    public function abrirFormMes(): void
    {
        $this->dispatch('abrir-form-mes', arrendatarioParqueoId: $this->arrendatario->id);
    }

    public function confirmarDesactivar(): void
    {
        $this->dispatch('confirmar-desactivar-arrendatario', id: $this->arrendatario->id);
    }

    public function activar(): void
    {
        $this->authorize('update', $this->arrendatario);
        $this->arrendatario->update(['activo' => true]);
        $this->arrendatario = $this->arrendatario->fresh();
        $this->dispatch('arrendatario-activado');
    }
}; ?>

<div class="space-y-6">
    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('parqueo.index') }}" variant="outline" icon="arrow-left" size="sm" />
            <div>
                <flux:heading size="xl">{{ $arrendatario->nombre_completo }}</flux:heading>
                <flux:subheading>Detalle del arrendatario de parqueo</flux:subheading>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $arrendatario)
                @if ($arrendatario->activo)
                    <flux:button wire:click="confirmarDesactivar" variant="outline" icon="no-symbol" size="sm">
                        Desactivar
                    </flux:button>
                @else
                    <flux:button wire:click="activar" variant="outline" icon="check-circle" size="sm">
                        Reactivar
                    </flux:button>
                @endif
            @endcan

            @can('update', $arrendatario)
                <flux:button
                    wire:click="$dispatch('abrir-form-arrendatario', { id: {{ $arrendatario->id }} })"
                    variant="primary"
                    icon="pencil"
                    size="sm">
                    Editar
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Ficha + historial de meses --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Ficha del arrendatario --}}
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">

                {{-- Estado --}}
                <div class="px-4 py-3 flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wide text-zinc-500">Estado</span>
                    @if ($arrendatario->activo)
                        <flux:badge color="green">Activo</flux:badge>
                    @else
                        <flux:badge color="zinc">Inactivo</flux:badge>
                    @endif
                </div>

                {{-- Ocupación --}}
                <div class="px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-zinc-500 mb-1">Ocupación</p>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                        @php
                            $ocu = match($arrendatario->ocupacion) {
                                'estudiante' => 'Estudiante',
                                'salud'      => 'Personal de Salud',
                                default      => 'Otro',
                            };
                        @endphp
                        {{ $ocu }}
                    </p>
                </div>

                {{-- Teléfono --}}
                <div class="px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-zinc-500 mb-1">Teléfono</p>
                    <p class="text-sm text-zinc-800 dark:text-zinc-200">
                        @if ($arrendatario->telefono)
                            <a href="https://wa.me/502{{ preg_replace('/\D/', '', $arrendatario->telefono) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:underline">
                                {{ $arrendatario->telefono }}
                                <flux:icon.chat-bubble-left-ellipsis class="size-3.5" />
                            </a>
                        @else
                            —
                        @endif
                    </p>
                </div>

                {{-- Placa --}}
                <div class="px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-zinc-500 mb-1">Placa del vehículo</p>
                    <p class="text-sm font-mono font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ $arrendatario->placa ?: '—' }}
                    </p>
                </div>

                {{-- Notas --}}
                @if ($arrendatario->notas)
                    <div class="px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-zinc-500 mb-1">Notas</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line">
                            {{ $arrendatario->notas }}
                        </p>
                    </div>
                @endif

                {{-- Registro --}}
                <div class="px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-zinc-500 mb-1">Registrado</p>
                    <p class="text-sm text-zinc-500">
                        {{ $arrendatario->created_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Historial de meses --}}
        <div class="lg:col-span-2">
            <livewire:parqueo.tabla-meses :arrendatario-parqueo-id="$arrendatario->id" />
        </div>
    </div>

    {{-- Modales --}}
    <livewire:parqueo.form-arrendatario />
    <livewire:parqueo.modal-desactivar-arrendatario />
    <livewire:parqueo.form-mes />
    <livewire:parqueo.modal-marcar-pagado />
    <livewire:parqueo.modal-eliminar-mes />
</div>
