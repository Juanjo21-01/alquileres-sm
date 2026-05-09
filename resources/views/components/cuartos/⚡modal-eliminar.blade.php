<?php

use App\Models\Cuarto;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $cuartoId = null;
    public ?string $cuartoCodigo = null;
    public ?string $error = null;

    #[On('confirmar-eliminar-cuarto')]
    public function abrir(int $id): void
    {
        $c = Cuarto::findOrFail($id);
        $this->authorize('delete', $c);
        $this->cuartoId    = $c->id;
        $this->cuartoCodigo = $c->codigo;
        $this->error       = null;

        Flux::modal('eliminar-cuarto')->show();
    }

    public function eliminar(): void
    {
        $c = Cuarto::findOrFail($this->cuartoId);
        $this->authorize('delete', $c);

        // No eliminar si tiene estancias (Fase 2 agrega esta validación)
        if (! $c->estaDisponible()) {
            $this->error = 'No se puede eliminar: el cuarto no está disponible. Cambia su estado primero.';

            return;
        }

        $codigo = $c->codigo;
        $c->delete();
        $this->reset(['cuartoId', 'cuartoCodigo', 'error']);

        Flux::modal('eliminar-cuarto')->close();
        Flux::toast(text: "Cuarto '{$codigo}' eliminado.", variant: 'success');
        $this->dispatch('cuarto-eliminado');
    }
}; ?>

<flux:modal name="eliminar-cuarto" class="md:w-[480px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Confirmar eliminación</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar el cuarto <strong>{{ $cuartoCodigo }}</strong>?
                Esta acción no se puede deshacer.
            </flux:subheading>
        </div>

        @if ($error)
            <flux:callout color="red" icon="exclamation-triangle">
                {{ $error }}
            </flux:callout>
        @endif

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>

            @if (!$error)
                <flux:button wire:click="eliminar" variant="danger">
                    <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                    <span wire:loading wire:target="eliminar">Eliminando...</span>
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
