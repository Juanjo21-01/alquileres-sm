<?php

use App\Models\Estancia;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $estado = '';

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    #[On('estancia-abierta')]
    #[On('estancia-cerrada')]
    #[On('estancia-cancelada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Estancia::query()
            ->with(['inquilino', 'cuarto.propiedad'])
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->orderByDesc('created_at');

        return [
            'estancias' => $query->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:select wire:model.live="estado" class="max-w-xs">
            <flux:select.option value="">Todos los estados</flux:select.option>
            <flux:select.option value="activa">Activa</flux:select.option>
            <flux:select.option value="finalizada">Finalizada</flux:select.option>
            <flux:select.option value="cancelada">Cancelada</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$estancias">
        <flux:table.columns>
            <flux:table.column>Inquilino</flux:table.column>
            <flux:table.column>Cuarto</flux:table.column>
            <flux:table.column>Inicio</flux:table.column>
            <flux:table.column>Precio</flux:table.column>
            <flux:table.column align="center">Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($estancias as $estancia)
                <flux:table.row :key="$estancia->id">
                    <flux:table.cell class="font-medium">
                        {{ $estancia->inquilino->nombre_completo }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $estancia->cuarto->propiedad->nombre }} — {{ $estancia->cuarto->codigo }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $estancia->fecha_inicio->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>Q {{ number_format((float) $estancia->precio_acordado, 2) }}</flux:table.cell>
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
                        <flux:button
                            href="{{ route('estancias.detalle', $estancia) }}"
                            size="xs"
                            variant="ghost">
                            Ver
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                        No hay estancias registradas.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
