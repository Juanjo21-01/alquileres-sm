<?php

use App\Models\AlquilerParqueo;
use App\Models\ArrendatarioParqueo;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $busqueda = '';
    public string $estadoFiltro = 'activos';

    public function updatingBusqueda(): void { $this->resetPage(); }
    public function updatingEstadoFiltro(): void { $this->resetPage(); }

    #[On('arrendatario-guardado')]
    #[On('arrendatario-desactivado')]
    #[On('arrendatario-activado')]
    public function refrescar(): void
    {
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
            ->when($this->busqueda, fn ($q) => $q->where(function ($q) {
                $q->where('nombre_completo', 'like', "%{$this->busqueda}%")
                    ->orWhere('placa', 'like', "%{$this->busqueda}%");
            }))
            ->when($this->estadoFiltro === 'activos', fn ($q) => $q->where('activo', true))
            ->when($this->estadoFiltro === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('nombre_completo')
            ->paginate(20);

        return compact('arrendatarios');
    }
}; ?>

<div>
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            icon="magnifying-glass"
            placeholder="Buscar por nombre o placa..."
            class="flex-1" />

        <flux:select wire:model.live="estadoFiltro" class="w-40">
            <flux:select.option value="todos">Todos</flux:select.option>
            <flux:select.option value="activos">Solo activos</flux:select.option>
            <flux:select.option value="inactivos">Solo inactivos</flux:select.option>
        </flux:select>
    </div>

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
                        <flux:button
                            href="{{ route('parqueo.detalle', $arrendatario) }}"
                            variant="ghost"
                            size="sm"
                            class="!px-0 font-medium">
                            {{ $arrendatario->nombre_completo }}
                        </flux:button>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">
                        {{ $arrendatario->telefono ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @php
                            $ocupaciones = [
                                'estudiante' => ['label' => 'Estudiante', 'color' => 'blue'],
                                'salud'      => ['label' => 'Salud',      'color' => 'green'],
                                'otro'       => ['label' => 'Otro',       'color' => 'zinc'],
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
                            {{ \Carbon\Carbon::parse($arrendatario->alquileres_max_mes)->format('m/Y') }}
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
                            <flux:button
                                href="{{ route('parqueo.detalle', $arrendatario) }}"
                                size="xs"
                                variant="ghost"
                                icon="eye" />

                            @can('update', $arrendatario)
                                <flux:button
                                    wire:click="editar({{ $arrendatario->id }})"
                                    size="xs"
                                    variant="ghost"
                                    icon="pencil-square" />
                            @endcan

                            @can('update', $arrendatario)
                                @if ($arrendatario->activo)
                                    <flux:button
                                        wire:click="confirmarDesactivar({{ $arrendatario->id }})"
                                        size="xs"
                                        variant="ghost"
                                        icon="pause-circle"
                                        title="Desactivar" />
                                @else
                                    <flux:button
                                        wire:click="activar({{ $arrendatario->id }})"
                                        size="xs"
                                        variant="ghost"
                                        icon="play-circle"
                                        title="Activar" />
                                @endif
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-10">
                        No hay arrendatarios de parqueo registrados.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
