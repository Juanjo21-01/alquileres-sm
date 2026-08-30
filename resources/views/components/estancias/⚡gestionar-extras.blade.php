<?php

use App\Models\Estancia;
use App\Models\ExtraEstancia;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Estancia $estancia;

    public bool $mostrarForm = false;

    public string $descripcion = '';

    public string $monto = '';

    public string $periodicidad = 'mensual';

    public ?int $extraAEliminarId = null;

    public ?string $extraAEliminarDesc = null;

    protected function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:150',
            'monto' => 'required|numeric|min:0',
            'periodicidad' => 'required|in:unico,mensual',
        ];
    }

    #[On('estancia-editada')]
    #[On('estancia-cerrada')]
    #[On('estancia-cancelada')]
    public function refrescar(): void
    {
        $this->estancia = $this->estancia->fresh(['extras']);
        $this->mostrarForm = false;
    }

    public function mostrarFormulario(): void
    {
        $this->authorize('update', $this->estancia);
        $this->reset(['descripcion', 'monto', 'periodicidad']);
        $this->periodicidad = 'mensual';
        $this->resetValidation();
        $this->mostrarForm = true;
    }

    public function cancelarForm(): void
    {
        $this->mostrarForm = false;
        $this->reset(['descripcion', 'monto', 'periodicidad']);
        $this->resetValidation();
    }

    public function agregar(): void
    {
        $this->authorize('update', $this->estancia);

        if (!$this->estancia->estaActiva()) {
            return;
        }

        $datos = $this->validate();
        $this->estancia->extras()->create($datos);
        $this->estancia = $this->estancia->fresh(['extras']);
        $this->mostrarForm = false;
        $this->reset(['descripcion', 'monto', 'periodicidad']);

        Flux::toast(text: 'Extra agregado correctamente.', variant: 'success');
        $this->dispatch('estancia-editada');
    }

    public function confirmarEliminar(int $id): void
    {
        $this->authorize('update', $this->estancia);

        $extra = ExtraEstancia::findOrFail($id);

        if ($extra->estancia_id !== $this->estancia->id) {
            return;
        }

        $this->extraAEliminarId = $extra->id;
        $this->extraAEliminarDesc = $extra->descripcion;

        Flux::modal('confirmar-eliminar-extra')->show();
    }

    public function eliminar(): void
    {
        $this->authorize('update', $this->estancia);

        $extra = ExtraEstancia::findOrFail($this->extraAEliminarId);

        if ($extra->estancia_id !== $this->estancia->id) {
            return;
        }

        $extra->delete();
        $this->estancia = $this->estancia->fresh(['extras']);
        $this->reset(['extraAEliminarId', 'extraAEliminarDesc']);

        Flux::modal('confirmar-eliminar-extra')->close();
        Flux::toast(text: 'Extra eliminado.', variant: 'success');
        $this->dispatch('estancia-editada');
    }
}; ?>

<div>
    <flux:card>
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="sm">Extras / servicios adicionales</flux:heading>

            @if ($estancia->estaActiva())
                @can('update', $estancia)
                    @if (!$mostrarForm)
                        <flux:button wire:click="mostrarFormulario" size="xs" variant="outline" icon="plus">
                            Agregar extra
                        </flux:button>
                    @endif
                @endcan
            @endif
        </div>

        @if ($estancia->extras->isEmpty() && !$mostrarForm)
            <p class="text-sm text-zinc-500">Sin extras registrados.</p>
        @endif

        @if ($estancia->extras->isNotEmpty())
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Descripción</flux:table.column>
                    <flux:table.column align="end">Monto</flux:table.column>
                    <flux:table.column align="center">Periodicidad</flux:table.column>
                    @if ($estancia->estaActiva())
                        @can('update', $estancia)
                            <flux:table.column align="end"></flux:table.column>
                        @endcan
                    @endif
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($estancia->extras as $extra)
                        <flux:table.row :key="$extra->id">
                            <flux:table.cell>{{ $extra->descripcion }}</flux:table.cell>
                            <flux:table.cell align="end">Q {{ number_format((float) $extra->monto, 2) }}
                            </flux:table.cell>
                            <flux:table.cell align="center" class="capitalize">{{ $extra->periodicidad }}
                            </flux:table.cell>
                            @if ($estancia->estaActiva())
                                @can('update', $estancia)
                                    <flux:table.cell align="end">
                                        <flux:button wire:click="confirmarEliminar({{ $extra->id }})" size="xs"
                                            variant="danger" icon="trash" />
                                    </flux:table.cell>
                                @endcan
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif

        @if ($mostrarForm)
            <form wire:submit="agregar" class="mt-4 space-y-3 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                <flux:heading size="xs">Nuevo extra</flux:heading>

                <div class="grid grid-cols-3 gap-3">
                    <flux:input wire:model="descripcion" label="Descripción" class="col-span-1" required />
                    <flux:input wire:model="monto" label="Monto (Q)" type="number" min="0" step="0.01"
                        required />
                    <flux:select wire:model="periodicidad" label="Periodicidad">
                        <flux:select.option value="mensual">Mensual</flux:select.option>
                        <flux:select.option value="unico">Único</flux:select.option>
                    </flux:select>
                </div>

                <div class="flex gap-2">
                    <flux:button type="submit" size="sm" variant="primary">
                        <span wire:loading.remove wire:target="agregar">Agregar</span>
                        <span wire:loading wire:target="agregar">Agregando...</span>
                    </flux:button>
                    <flux:button type="button" wire:click="cancelarForm" size="sm" variant="outline">Cancelar
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:card>

    <flux:modal name="confirmar-eliminar-extra" class="md:w-105">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Eliminar extra</flux:heading>
                <flux:subheading>
                    ¿Eliminar el extra <strong>{{ $extraAEliminarDesc }}</strong>?
                    Esta acción no se puede deshacer.
                </flux:subheading>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="outline">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="eliminar" variant="danger">
                    <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                    <span wire:loading wire:target="eliminar">Eliminando...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
