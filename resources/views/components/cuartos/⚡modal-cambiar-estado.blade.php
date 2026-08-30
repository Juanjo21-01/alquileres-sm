<?php

use App\Models\Cuarto;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $cuartoId = null;
    public ?string $estadoActual = null;
    public ?string $estadoDestino = null;
    public string $motivo = '';

    #[On('abrir-cambiar-estado-cuarto')]
    public function abrir(int $id): void
    {
        $c = Cuarto::findOrFail($id);
        $this->authorize('update', $c);

        $this->cuartoId     = $c->id;
        $this->estadoActual = $c->estado;
        $this->motivo       = '';

        // Solo permitir disponible ↔ mantenimiento
        $this->estadoDestino = match ($c->estado) {
            Cuarto::ESTADO_DISPONIBLE    => Cuarto::ESTADO_MANTENIMIENTO,
            Cuarto::ESTADO_MANTENIMIENTO => Cuarto::ESTADO_DISPONIBLE,
            default                      => null,
        };

        if (! $this->estadoDestino) {
            Flux::toast(text: 'Este cuarto no permite cambio manual de estado.', variant: 'warning');

            return;
        }

        Flux::modal('cambiar-estado-cuarto')->show();
    }

    public function cambiar(): void
    {
        $this->validate([
            'motivo' => $this->estadoDestino === Cuarto::ESTADO_MANTENIMIENTO
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $c = Cuarto::findOrFail($this->cuartoId);
        $this->authorize('update', $c);

        $c->update(['estado' => $this->estadoDestino]);

        $etiqueta = Cuarto::estados()[$this->estadoDestino];

        Flux::modal('cambiar-estado-cuarto')->close();
        Flux::toast(text: "Cuarto pasó a estado: {$etiqueta}.", variant: 'success');
        $this->dispatch('cuarto-estado-cambiado');
        $this->reset(['cuartoId', 'estadoActual', 'estadoDestino', 'motivo']);
    }

    public function cancelar(): void
    {
        Flux::modal('cambiar-estado-cuarto')->close();
    }
}; ?>

<flux:modal name="cambiar-estado-cuarto" class="md:w-120">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Cambiar estado del cuarto</flux:heading>
            <flux:subheading>
                Estado actual:
                <strong>{{ $estadoActual ? Cuarto::estados()[$estadoActual] : '' }}</strong>
                →
                <strong>{{ $estadoDestino ? Cuarto::estados()[$estadoDestino] : '' }}</strong>
            </flux:subheading>
        </div>

        @if ($estadoDestino === 'mantenimiento')
            <flux:textarea
                wire:model="motivo"
                label="Motivo del mantenimiento"
                placeholder="Describe el motivo..."
                rows="3"
                required />
        @endif

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="outline">Cancelar</flux:button>
            <flux:button wire:click="cambiar" variant="primary">
                <span wire:loading.remove wire:target="cambiar">Confirmar cambio</span>
                <span wire:loading wire:target="cambiar">Guardando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
