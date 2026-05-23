<?php

use App\Models\Inquilino;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle inquilino')] class extends Component {
    public Inquilino $inquilino;

    public function mount(Inquilino $inquilino): void
    {
        $this->authorize('view', $inquilino);
        $this->inquilino = $inquilino->load(['estanciaActiva.cuarto.propiedad', 'estancias.cuarto.propiedad']);
    }

    #[On('inquilino-guardado')]
    #[On('estancia-abierta')]
    public function refrescarInquilino(): void
    {
        $this->inquilino = $this->inquilino->fresh(['estanciaActiva.cuarto.propiedad', 'estancias.cuarto.propiedad']);
    }

    public function editar(): void
    {
        $this->dispatch('abrir-form-inquilino', id: $this->inquilino->id);
    }

    public function abrirEstancia(): void
    {
        $this->dispatch('abrir-form-estancia', inquilinoId: $this->inquilino->id);
    }
}; ?>

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <flux:button href="{{ route('inquilinos.index') }}" variant="ghost" size="xs" icon="arrow-left" wire:navigate>
            Inquilinos
        </flux:button>
    </div>

    {{-- Encabezado --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ $inquilino->nombre_completo }}</flux:heading>
            <flux:subheading class="capitalize">
                {{ $inquilino->ocupacion }}{{ $inquilino->institucion ? ' · ' . $inquilino->institucion : '' }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @if (!$inquilino->activo)
                <flux:badge color="red">Inactivo</flux:badge>
            @endif

            @can('update', $inquilino)
                <flux:button wire:click="editar" variant="ghost" icon="pencil-square">
                    Editar
                </flux:button>
            @endcan

            @can('create', App\Models\Estancia::class)
                @if ($inquilino->activo && !$inquilino->estanciaActiva)
                    <flux:button wire:click="abrirEstancia" variant="primary" icon="home">
                        Abrir estancia
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Datos personales --}}
        <div class="md:col-span-2 space-y-4">
            <flux:card>
                <flux:heading size="sm" class="mb-3">Datos personales</flux:heading>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-zinc-500">DPI</dt>
                        <dd class="font-medium">{{ $inquilino->dpi ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Teléfono</dt>
                        <dd class="font-medium">
                            @if ($inquilino->telefono)
                                <a href="https://wa.me/502{{ preg_replace('/\D/', '', $inquilino->telefono) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:underline">
                                    {{ $inquilino->telefono }}
                                    <flux:icon.chat-bubble-left-ellipsis class="size-3.5" />
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Correo</dt>
                        <dd class="font-medium">{{ $inquilino->email ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Ocupación</dt>
                        <dd class="font-medium capitalize">{{ $inquilino->ocupacion }}</dd>
                    </div>
                    @if ($inquilino->institucion)
                        <div class="col-span-2">
                            <dt class="text-zinc-500">Institución</dt>
                            <dd class="font-medium">{{ $inquilino->institucion }}</dd>
                        </div>
                    @endif
                    @if ($inquilino->notas)
                        <div class="col-span-2">
                            <dt class="text-zinc-500">Notas</dt>
                            <dd class="font-medium">{{ $inquilino->notas }}</dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            @if ($inquilino->contacto_emergencia_nombre || $inquilino->contacto_emergencia_telefono)
                <flux:card>
                    <flux:heading size="sm" class="mb-3">Contacto de emergencia</flux:heading>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-zinc-500">Nombre</dt>
                            <dd class="font-medium">{{ $inquilino->contacto_emergencia_nombre ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Teléfono</dt>
                            <dd class="font-medium">
                                @if ($inquilino->contacto_emergencia_telefono)
                                    <a href="https://wa.me/502{{ preg_replace('/\D/', '', $inquilino->contacto_emergencia_telefono) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:underline">
                                        {{ $inquilino->contacto_emergencia_telefono }}
                                        <flux:icon.chat-bubble-left-ellipsis class="size-3.5" />
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </flux:card>
            @endif
            <flux:card>
                <flux:heading size="sm" class="mb-3">Parqueo</flux:heading>

                @if ($inquilino->vehiculo_tipo)
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon.check-circle class="text-green-500 size-5" />
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">
                            Tiene vehículo registrado
                        </span>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-zinc-500">Tipo</dt>
                            <dd class="font-medium capitalize">{{ $inquilino->vehiculo_tipo }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Placa</dt>
                            <dd class="font-medium font-mono">{{ $inquilino->vehiculo_placa ?: '—' }}</dd>
                        </div>
                    </dl>
                @else
                    <div class="flex items-center gap-2">
                        <flux:icon.x-circle class="text-zinc-400 size-5" />
                        <span class="text-sm text-zinc-500">No posee vehículo registrado</span>
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Estancia activa --}}
        <div>
            <flux:card>
                <flux:heading size="sm" class="mb-3">Estancia actual</flux:heading>

                @if ($inquilino->estanciaActiva)
                    @php $ea = $inquilino->estanciaActiva; @endphp
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-zinc-500">Cuarto</span>
                            <p class="font-medium">{{ $ea->cuarto->propiedad->nombre }} — {{ $ea->cuarto->codigo }}
                            </p>
                        </div>
                        <div>
                            <span class="text-zinc-500">Desde</span>
                            <p class="font-medium">{{ $ea->fecha_inicio->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <span class="text-zinc-500">Precio acordado</span>
                            <p class="font-medium">Q {{ number_format((float) $ea->precio_acordado, 2) }}</p>
                        </div>
                        <flux:badge color="green">Activa</flux:badge>
                        <div class="pt-2">
                            <flux:button href="{{ route('estancias.detalle', $ea) }}" size="sm" variant="ghost"
                                class="w-full">
                                Ver detalle de estancia
                            </flux:button>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">Sin estancia activa.</p>
                @endif
            </flux:card>
        </div>
    </div>

    {{-- Historial de estancias --}}
    <flux:card>
        <flux:heading size="sm" class="mb-3">Historial de estancias</flux:heading>

        @if ($inquilino->estancias->isEmpty())
            <p class="text-sm text-zinc-500">No hay estancias registradas.</p>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Cuarto</flux:table.column>
                    <flux:table.column>Inicio</flux:table.column>
                    <flux:table.column>Fin</flux:table.column>
                    <flux:table.column>Precio</flux:table.column>
                    <flux:table.column align="center">Estado</flux:table.column>
                    <flux:table.column align="end">Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($inquilino->estancias as $estancia)
                        <flux:table.row :key="$estancia->id">
                            <flux:table.cell>
                                {{ $estancia->cuarto->propiedad->nombre }} — {{ $estancia->cuarto->codigo }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $estancia->fecha_inicio->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $estancia->fecha_fin?->format('d/m/Y') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>Q {{ number_format((float) $estancia->precio_acordado, 2) }}
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                @if ($estancia->estado === 'activa')
                                    <flux:badge color="green" size="sm">Activa</flux:badge>
                                @elseif ($estancia->estado === 'finalizada')
                                    <flux:badge color="blue" size="sm">Finalizada</flux:badge>
                                @elseif ($estancia->estado === 'cancelada')
                                    <flux:badge color="red" size="sm">Cancelada</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button href="{{ route('estancias.detalle', $estancia) }}" size="xs"
                                    variant="ghost">
                                    Ver
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <livewire:inquilinos.form />
    <livewire:estancias.form-abrir />
</div>
