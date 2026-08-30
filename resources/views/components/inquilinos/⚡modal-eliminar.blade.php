<?php

use App\Models\Inquilino;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $inquilinoId = null;

    public ?string $inquilinoNombre = null;

    public ?string $error = null;

    #[On('confirmar-eliminar-inquilino')]
    public function abrir(int $id): void
    {
        $i = Inquilino::findOrFail($id);
        $this->authorize('delete', $i);
        $this->inquilinoId = $i->id;
        $this->inquilinoNombre = $i->nombre_completo;
        $this->error = null;

        Flux::modal('eliminar-inquilino')->show();
    }

    public function eliminar(): void
    {
        $i = Inquilino::findOrFail($this->inquilinoId);
        $this->authorize('delete', $i);

        if ($i->estanciaActiva()->exists()) {
            $this->error = 'No se puede eliminar: el inquilino tiene una estancia activa.';

            return;
        }

        $nombre = $i->nombre_completo;
        $i->delete();
        $this->reset(['inquilinoId', 'inquilinoNombre', 'error']);

        Flux::modal('eliminar-inquilino')->close();
        Flux::toast(text: "Inquilino '{$nombre}' eliminado.", variant: 'success');
        $this->dispatch('inquilino-eliminado');
    }
}; ?>

<flux:modal name="eliminar-inquilino" class="md:w-125">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Confirmar eliminación</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar al inquilino <strong>{{ $inquilinoNombre }}</strong>?
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
                <flux:button variant="outline">Cancelar</flux:button>
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
