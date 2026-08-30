<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $propiedadId = null;
    public ?string $propiedadNombre = null;
    public ?string $error = null;

    #[On('confirmar-eliminar-propiedad')]
    public function abrir(int $id): void
    {
        $p = Propiedad::findOrFail($id);
        $this->authorize('delete', $p);
        $this->propiedadId     = $p->id;
        $this->propiedadNombre = $p->nombre;
        $this->error           = null;

        Flux::modal('eliminar-propiedad')->show();
    }

    public function eliminar(): void
    {
        $p = Propiedad::findOrFail($this->propiedadId);
        $this->authorize('delete', $p);

        if ($p->cuartos()->exists()) {
            $this->error = 'No se puede eliminar: la propiedad tiene cuartos asociados.';

            return;
        }

        $nombre = $p->nombre;
        $p->delete();
        $this->reset(['propiedadId', 'propiedadNombre', 'error']);

        Flux::modal('eliminar-propiedad')->close();
        Flux::toast(text: "Propiedad '{$nombre}' eliminada.", variant: 'success');
        $this->dispatch('propiedad-eliminada');
    }
}; ?>

<flux:modal name="eliminar-propiedad" class="md:w-125">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Confirmar eliminación</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar la propiedad <strong>{{ $propiedadNombre }}</strong>?
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
