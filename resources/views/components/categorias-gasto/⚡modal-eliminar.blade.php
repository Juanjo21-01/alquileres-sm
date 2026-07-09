<?php

use App\Models\CategoriaGasto;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $categoriaId = null;
    public ?string $categoriaNombre = null;
    public ?string $error = null;

    #[On('confirmar-eliminar-categoria-gasto')]
    public function abrir(int $id): void
    {
        $c = CategoriaGasto::findOrFail($id);
        $this->authorize('delete', $c);
        $this->categoriaId     = $c->id;
        $this->categoriaNombre = $c->nombre;
        $this->error           = null;

        Flux::modal('eliminar-categoria-gasto')->show();
    }

    public function eliminar(): void
    {
        $c = CategoriaGasto::findOrFail($this->categoriaId);
        $this->authorize('delete', $c);

        if ($c->gastos()->exists()) {
            $this->error = 'No se puede eliminar: la categoría tiene gastos asociados.';

            return;
        }

        $nombre = $c->nombre;
        $c->delete();
        $this->reset(['categoriaId', 'categoriaNombre', 'error']);

        Flux::modal('eliminar-categoria-gasto')->close();
        Flux::toast(text: "Categoría '{$nombre}' eliminada.", variant: 'success');
        $this->dispatch('categoria-gasto-eliminada');
    }
}; ?>

<flux:modal name="eliminar-categoria-gasto" class="md:w-[500px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Confirmar eliminación</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar la categoría <strong>{{ $categoriaNombre }}</strong>?
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

            @if (! $error)
                <flux:button wire:click="eliminar" variant="danger">
                    <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                    <span wire:loading wire:target="eliminar">Eliminando...</span>
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
