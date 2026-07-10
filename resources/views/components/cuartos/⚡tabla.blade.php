<?php

use App\Models\Cuarto;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public int $propiedadId;
    public string $filtroEstado = '';
    public string $filtroNivel = '';

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-cuarto', id: $id, propiedadId: $this->propiedadId);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->dispatch('confirmar-eliminar-cuarto', id: $id);
    }

    public function cambiarEstado(int $id): void
    {
        $this->dispatch('abrir-cambiar-estado-cuarto', id: $id);
    }

    #[On('cuarto-guardado')]
    #[On('cuarto-eliminado')]
    #[On('cuarto-estado-cambiado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $cuartos = Cuarto::query()
            ->where('propiedad_id', $this->propiedadId)
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroNivel, fn ($q) => $q->where('nivel', $this->filtroNivel))
            ->orderBy('nivel')
            ->orderBy('codigo')
            ->get();

        $niveles = Cuarto::where('propiedad_id', $this->propiedadId)
            ->distinct()
            ->orderBy('nivel')
            ->pluck('nivel');

        return [
            'cuartos' => $cuartos,
            'estados' => Cuarto::estados(),
            'niveles' => $niveles,
        ];
    }
}; ?>

<div class="space-y-4">
    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3">
        <flux:select wire:model.live="filtroEstado" placeholder="Todos los estados" class="w-48">
            <flux:select.option value="">Todos los estados</flux:select.option>
            @foreach (Cuarto::estados() as $valor => $etiqueta)
                <flux:select.option value="{{ $valor }}">{{ $etiqueta }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filtroNivel" placeholder="Todos los niveles" class="w-48">
            <flux:select.option value="">Todos los niveles</flux:select.option>
            @foreach ($niveles as $nivel)
                <flux:select.option value="{{ $nivel }}">Nivel {{ $nivel }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Grid de tarjetas --}}
    @if ($cuartos->isEmpty())
        <div class="text-center text-zinc-500 py-16">
            <flux:icon name="home" class="mx-auto mb-3 size-10 text-zinc-300" />
            <p class="font-medium">No hay cuartos registrados.</p>
            @can('create', App\Models\Cuarto::class)
                <p class="text-sm mt-1">Crea el primer cuarto con el botón "Nuevo cuarto".</p>
            @endcan
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($cuartos as $cuarto)
                <div wire:key="{{ $cuarto->id }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-3 shadow-sm">
                    {{-- Encabezado --}}
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $cuarto->codigo }}</p>
                            <p class="text-xs text-zinc-500">Nivel {{ $cuarto->nivel }}
                                @if ($cuarto->tamano) · {{ $cuarto->tamano }} @endif
                            </p>
                        </div>
                        @switch($cuarto->estado)
                            @case('disponible')
                                <flux:badge color="green" size="sm">Disponible</flux:badge>
                                @break
                            @case('ocupado')
                                <flux:badge color="blue" size="sm">Ocupado</flux:badge>
                                @break
                            @case('reservado')
                                <flux:badge color="amber" size="sm">Reservado</flux:badge>
                                @break
                            @case('mantenimiento')
                                <flux:badge color="red" size="sm">Mantenimiento</flux:badge>
                                @break
                        @endswitch
                    </div>

                    {{-- Precio --}}
                    <p class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">
                        Q{{ number_format((float) $cuarto->precio_base, 2) }}
                        <span class="text-sm font-normal text-zinc-400">/mes</span>
                    </p>

                    {{-- Descripción --}}
                    @if ($cuarto->descripcion)
                        <p class="text-xs text-zinc-500 line-clamp-2">{{ $cuarto->descripcion }}</p>
                    @endif

                    {{-- Acciones --}}
                    <div class="flex items-center gap-1 pt-1 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button
                            href="{{ route('cuartos.detalle', $cuarto) }}"
                            size="xs"
                            variant="ghost"
                            icon="eye"
                            title="Ver detalle e historial"
                            wire:navigate />

                        @can('update', $cuarto)
                            <flux:button
                                wire:click="editar({{ $cuarto->id }})"
                                size="xs"
                                variant="ghost"
                                icon="pencil-square" />

                            @if ($cuarto->estado === 'disponible' || $cuarto->estado === 'mantenimiento')
                                <flux:button
                                    wire:click="cambiarEstado({{ $cuarto->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="arrow-path">
                                    Estado
                                </flux:button>
                            @endif
                        @endcan

                        @can('delete', $cuarto)
                            <flux:button
                                wire:click="confirmarEliminar({{ $cuarto->id }})"
                                size="xs"
                                variant="danger"
                                icon="trash"
                                class="ml-auto" />
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
