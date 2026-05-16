<?php

use App\Models\AlquilerParqueo;
use App\Services\AlquilerParqueoService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $mesId = null;
    public ?string $mesInfo = null;

    #[On('confirmar-eliminar-mes')]
    public function abrir(int $id): void
    {
        $mes = AlquilerParqueo::findOrFail($id);
        $this->authorize('delete', $mes);

        $this->mesId   = $mes->id;
        $this->mesInfo = \Carbon\Carbon::parse($mes->mes)->translatedFormat('F Y')
            . ' — Q ' . number_format((float) $mes->monto, 2);

        Flux::modal('eliminar-mes')->show();
    }

    public function eliminar(): void
    {
        $mes = AlquilerParqueo::findOrFail($this->mesId);
        $this->authorize('delete', $mes);

        $mes->delete();

        $this->reset(['mesId', 'mesInfo']);
        Flux::modal('eliminar-mes')->close();
        Flux::toast(text: 'Mes eliminado correctamente.', variant: 'success');
        $this->dispatch('mes-eliminado');
    }
}; ?>

<flux:modal name="eliminar-mes" class="md:w-[480px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Eliminar mes de parqueo</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar el registro: <strong>{{ $mesInfo }}</strong>?
            </flux:subheading>
        </div>

        <flux:callout color="red" icon="exclamation-triangle">
            Esta acción es permanente y no se puede deshacer. El registro del mes
            será eliminado del historial del arrendatario.
        </flux:callout>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="eliminar" variant="danger">
                <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                <span wire:loading wire:target="eliminar">Eliminando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
