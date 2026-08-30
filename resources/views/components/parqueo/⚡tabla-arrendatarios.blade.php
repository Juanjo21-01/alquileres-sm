<?php

use App\Models\AlquilerParqueo;
use App\Models\ArrendatarioParqueo;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Buscador de texto: en vivo.
    public string $busqueda = '';

    // Filtro aplicado (solo cambia con "Buscar").
    public string $estadoFiltro = 'activos';

    // Filtro de ocupación (aplicado).
    public string $ocupacion = '';

    // Borradores ligados a los controles; se aplican con "Buscar".
    public string $fEstado = 'activos';
    public string $fOcupacion = '';

    public int $filtrosVersion = 0;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    #[On('arrendatario-guardado')]
    #[On('arrendatario-desactivado')]
    #[On('arrendatario-activado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function buscar(): void
    {
        $this->estadoFiltro = $this->fEstado;
        $this->ocupacion = $this->fOcupacion;
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset(['busqueda', 'estadoFiltro', 'ocupacion', 'fEstado', 'fOcupacion']);
        $this->filtrosVersion++;
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->dispatch('abrir-form-arrendatario', id: $id);
    }

    public function confirmarDesactivar(int $id): void
    {
        $this->dispatch('confirmar-desactivar-arrendatario', id: $id);
    }

    public function activar(int $id): void
    {
        $arrendatario = ArrendatarioParqueo::findOrFail($id);
        $this->authorize('update', $arrendatario);
        $arrendatario->update(['activo' => true]);
        $this->dispatch('arrendatario-activado');
        \Flux\Flux::toast(text: "{$arrendatario->nombre_completo} activado.", variant: 'success');
    }

    public function with(): array
    {
        $arrendatarios = ArrendatarioParqueo::query()
            ->withMax('alquileres', 'mes')
            ->when(
                $this->busqueda,
                fn($q) => $q->where(function ($q) {
                    $q->where('nombre_completo', 'like', "%{$this->busqueda}%")->orWhere('placa', 'like', "%{$this->busqueda}%");
                }),
            )
            ->when($this->estadoFiltro === 'activos', fn($q) => $q->where('activo', true))
            ->when($this->estadoFiltro === 'inactivos', fn($q) => $q->where('activo', false))
            ->when($this->ocupacion, fn($q) => $q->where('ocupacion', $this->ocupacion))
            ->orderBy('nombre_completo')
            ->paginate(20);

        return compact('arrendatarios');
    }
}; ?>

<div class="space-y-4">
    <x-ui.filtros-card>
        <div wire:key="parqueo-filtros-{{ $filtrosVersion }}" class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
            <flux:input wire:model.live.debounce.300ms="busqueda" icon="magnifying-glass"
                placeholder="Buscar por nombre o placa..." class="flex-1 min-w-48" />

            <flux:select wire:model="fOcupacion" class="w-full md:w-48">
                <flux:select.option value="">Todas las ocupaciones</flux:select.option>
                <flux:select.option value="estudiante">Estudiante</flux:select.option>
                <flux:select.option value="salud">Personal de salud</flux:select.option>
                <flux:select.option value="otro">Otro</flux:select.option>
            </flux:select>

            <flux:select wire:model="fEstado" class="w-full md:w-40">
                <flux:select.option value="todos">Todos</flux:select.option>
                <flux:select.option value="activos">Solo activos</flux:select.option>
                <flux:select.option value="inactivos">Solo inactivos</flux:select.option>
            </flux:select>
        </div>

        <x-slot:actions>
            <flux:button wire:click="limpiar" variant="outline" icon="x-mark">Limpiar</flux:button>
            <flux:button wire:click="buscar" variant="primary" icon="magnifying-glass">Buscar</flux:button>
        </x-slot:actions>
    </x-ui.filtros-card>

    {{-- Skeleton mientras se filtra --}}
    <div wire:loading.delay wire:target="busqueda, buscar, limpiar">
        <x-ui.tabla-skeleton :cols="7" />
    </div>

    <div wire:loading.remove.delay wire:target="busqueda, buscar, limpiar">
    <flux:table :paginate="$arrendatarios">
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Teléfono</flux:table.column>
            <flux:table.column>Ocupación</flux:table.column>
            <flux:table.column>Placa</flux:table.column>
            <flux:table.column>Último mes</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($arrendatarios as $arrendatario)
                <flux:table.row :key="$arrendatario->id">
                    <flux:table.cell class="font-medium">
                        <flux:button href="{{ route('parqueo.detalle', $arrendatario) }}" variant="outline" size="sm"
                            class="px-0! font-medium">
                            {{ $arrendatario->nombre_completo }}
                        </flux:button>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">
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
                    </flux:table.cell>
                    <flux:table.cell>
                        @php
                            $ocupaciones = [
                                'estudiante' => ['label' => 'Estudiante', 'color' => 'blue'],
                                'salud' => ['label' => 'Salud', 'color' => 'green'],
                                'otro' => ['label' => 'Otro', 'color' => 'zinc'],
                            ];
                            $oc = $ocupaciones[$arrendatario->ocupacion] ?? $ocupaciones['otro'];
                        @endphp
                        <flux:badge size="sm" :color="$oc['color']">{{ $oc['label'] }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-sm">
                        {{ $arrendatario->placa ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        @if ($arrendatario->alquileres_max_mes)
                            {{ \Carbon\Carbon::parse($arrendatario->alquileres_max_mes)->translatedFormat('F Y') }}
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        @if ($arrendatario->activo)
                            <flux:badge color="green" size="sm">Activo</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button href="{{ route('parqueo.detalle', $arrendatario) }}" size="xs"
                                variant="outline" icon="eye" />

                            @can('update', $arrendatario)
                                <flux:button wire:click="editar({{ $arrendatario->id }})" size="xs" variant="outline"
                                    icon="pencil-square" />
                            @endcan

                            @can('update', $arrendatario)
                                @if ($arrendatario->activo)
                                    <flux:button wire:click="confirmarDesactivar({{ $arrendatario->id }})" size="xs"
                                        variant="outline" icon="pause-circle" title="Desactivar" />
                                @else
                                    <flux:button wire:click="activar({{ $arrendatario->id }})" size="xs"
                                        variant="outline" icon="play-circle" title="Activar" />
                                @endif
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <x-ui.empty-state
                            icon="truck"
                            title="No hay arrendatarios"
                            description="Registra el primer arrendatario de parqueo o ajusta los filtros.">
                            @can('create', App\Models\ArrendatarioParqueo::class)
                                <flux:button wire:click="$dispatch('abrir-form-arrendatario')" size="sm" variant="primary" icon="plus">
                                    Nuevo arrendatario
                                </flux:button>
                            @endcan
                        </x-ui.empty-state>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    </div>
</div>
