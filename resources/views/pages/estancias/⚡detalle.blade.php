<?php

use App\Models\Estancia;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle de estancia')] class extends Component
{
    public Estancia $estancia;

    public function mount(Estancia $estancia): void
    {
        $this->authorize('view', $estancia);
        $this->estancia = $estancia->load([
            'inquilino',
            'cuarto.propiedad',
            'extras',
            'userRegistro',
        ]);

        if (session()->has('toast_success')) {
            Flux::toast(text: session('toast_success'), variant: 'success');
        }
    }

    #[On('estancia-cerrada')]
    #[On('estancia-cancelada')]
    #[On('estancia-editada')]
    public function refrescarEstancia(): void
    {
        $this->estancia = $this->estancia->fresh([
            'inquilino',
            'cuarto.propiedad',
            'extras',
        ]);
    }

    public function abrirFormEditar(): void
    {
        $this->dispatch('abrir-form-editar-estancia', id: $this->estancia->id);
    }

    public function abrirFormCerrar(): void
    {
        $this->dispatch('abrir-form-cerrar-estancia', id: $this->estancia->id);
    }

    public function confirmarCancelar(): void
    {
        $this->dispatch('confirmar-cancelar-estancia', id: $this->estancia->id);
    }
}; ?>

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <flux:button href="{{ route('estancias.index') }}" variant="ghost" size="xs" icon="arrow-left">
            Estancias
        </flux:button>
    </div>

    {{-- Encabezado --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl">Estancia #{{ $estancia->id }}</flux:heading>
            <flux:subheading>
                {{ $estancia->inquilino->nombre_completo }} —
                {{ $estancia->cuarto->propiedad->nombre }}, cuarto {{ $estancia->cuarto->codigo }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @if ($estancia->estado === 'activa')
                <flux:badge color="green">Activa</flux:badge>
            @elseif ($estancia->estado === 'finalizada')
                <flux:badge color="blue">Finalizada</flux:badge>
            @elseif ($estancia->estado === 'cancelada')
                <flux:badge color="red">Cancelada</flux:badge>
            @endif

            @if ($estancia->estaActiva())
                @can('update', $estancia)
                    <flux:button wire:click="abrirFormEditar" variant="ghost" icon="pencil-square">
                        Editar
                    </flux:button>
                    <flux:button wire:click="abrirFormCerrar" variant="ghost" icon="check-circle">
                        Cerrar estancia
                    </flux:button>
                @endcan

                @can('cancelar', $estancia)
                    <flux:button wire:click="confirmarCancelar" variant="danger" icon="x-circle">
                        Cancelar
                    </flux:button>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Datos principales --}}
        <div class="md:col-span-2 space-y-4">
            <flux:card>
                <flux:heading size="sm" class="mb-3">Datos de la estancia</flux:heading>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-zinc-500">Inquilino</dt>
                        <dd class="font-medium">
                            <a href="{{ route('inquilinos.detalle', $estancia->inquilino) }}" class="hover:underline">
                                {{ $estancia->inquilino->nombre_completo }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Cuarto</dt>
                        <dd class="font-medium">
                            {{ $estancia->cuarto->propiedad->nombre }} — {{ $estancia->cuarto->codigo }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Fecha inicio</dt>
                        <dd class="font-medium">{{ $estancia->fecha_inicio->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Fecha fin estimada</dt>
                        <dd class="font-medium">{{ $estancia->fecha_fin_estimada?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Precio acordado</dt>
                        <dd class="font-medium">Q {{ number_format((float) $estancia->precio_acordado, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Depósito</dt>
                        <dd class="font-medium">Q {{ number_format((float) $estancia->deposito, 2) }}</dd>
                    </div>
                    @if ($estancia->fecha_fin)
                        <div>
                            <dt class="text-zinc-500">Fecha de cierre</dt>
                            <dd class="font-medium">{{ $estancia->fecha_fin->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if ($estancia->motivo_cierre)
                        <div class="col-span-2">
                            <dt class="text-zinc-500">Motivo de cierre</dt>
                            <dd class="font-medium">{{ $estancia->motivo_cierre }}</dd>
                        </div>
                    @endif
                    @if ($estancia->notas)
                        <div class="col-span-2">
                            <dt class="text-zinc-500">Notas</dt>
                            <dd class="font-medium">{{ $estancia->notas }}</dd>
                        </div>
                    @endif
                    @if ($estancia->userRegistro)
                        <div class="col-span-2">
                            <dt class="text-zinc-500">Registrado por</dt>
                            <dd class="font-medium">{{ $estancia->userRegistro->name }}</dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            {{-- Extras --}}
            <livewire:estancias.gestionar-extras :estancia="$estancia" />
        </div>

        {{-- Panel lateral: resumen --}}
        <div class="space-y-4">
            <flux:card>
                <flux:heading size="sm" class="mb-3">Resumen financiero</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">Renta mensual</dt>
                        <dd class="font-medium">Q {{ number_format((float) $estancia->precio_acordado, 2) }}</dd>
                    </div>
                    @foreach ($estancia->extras->where('periodicidad', 'mensual') as $extra)
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 pl-2">+ {{ $extra->descripcion }}</dt>
                            <dd>Q {{ number_format((float) $extra->monto, 2) }}</dd>
                        </div>
                    @endforeach
                    <flux:separator />
                    <div class="flex justify-between font-semibold">
                        <dt>Total mensual</dt>
                        <dd>Q {{ number_format(
                            (float) $estancia->precio_acordado +
                            $estancia->extras->where('periodicidad', 'mensual')->sum(fn ($e) => (float) $e->monto),
                            2
                        ) }}</dd>
                    </div>
                    <div class="flex justify-between text-zinc-500 pt-1">
                        <dt>Depósito</dt>
                        <dd>Q {{ number_format((float) $estancia->deposito, 2) }}</dd>
                    </div>
                </dl>
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-2">Inquilino</flux:heading>
                <div class="text-sm space-y-1">
                    <p class="font-medium">{{ $estancia->inquilino->nombre_completo }}</p>
                    <p class="text-zinc-500">{{ $estancia->inquilino->telefono ?: '—' }}</p>
                    <flux:button
                        href="{{ route('inquilinos.detalle', $estancia->inquilino) }}"
                        size="sm"
                        variant="ghost"
                        class="w-full mt-2">
                        Ver perfil
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Historial de pagos --}}
    <livewire:pagos.historial-estancia :estancia-id="$estancia->id" />

    <livewire:estancias.form-editar />
    <livewire:estancias.form-cerrar />
    <livewire:estancias.modal-cancelar />
    <livewire:pagos.form />
    <livewire:pagos.modal-eliminar />
</div>
