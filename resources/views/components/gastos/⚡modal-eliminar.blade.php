<?php

use App\Models\Gasto;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $gastoId = null;
    public ?string $gastoInfo = null;

    #[On('confirmar-eliminar-gasto')]
    public function abrir(int $id): void
    {
        $gasto = Gasto::with('categoria')->findOrFail($id);
        $this->authorize('delete', $gasto);

        $this->gastoId   = $gasto->id;
        $this->gastoInfo = sprintf(
            '%s — %s (Q %s)',
            $gasto->categoria->nombre,
            $gasto->descripcion,
            number_format((float) $gasto->monto, 2),
        );

        Flux::modal('eliminar-gasto')->show();
    }

    public function eliminar(): void
    {
        $gasto = Gasto::findOrFail($this->gastoId);
        $this->authorize('delete', $gasto);

        // Soft delete: el registro y su comprobante se conservan (recuperable).
        $gasto->delete();

        $this->reset(['gastoId', 'gastoInfo']);
        Flux::modal('eliminar-gasto')->close();
        Flux::toast(text: 'Gasto eliminado correctamente.', variant: 'success');
        $this->dispatch('gasto-eliminado');
    }
}; ?>

<flux:modal name="eliminar-gasto" class="md:w-125">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Eliminar gasto</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar el gasto
                <strong>{{ $gastoInfo }}</strong>?
                Esta acción no se puede deshacer.
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="outline">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="eliminar" variant="danger">
                <span wire:loading.remove wire:target="eliminar">Eliminar gasto</span>
                <span wire:loading wire:target="eliminar">Eliminando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
