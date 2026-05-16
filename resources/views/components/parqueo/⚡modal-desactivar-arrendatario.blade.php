<?php

use App\Models\ArrendatarioParqueo;
use App\Services\AlquilerParqueoService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $arrendatarioId = null;
    public ?string $nombre = null;

    #[On('confirmar-desactivar-arrendatario')]
    public function abrir(int $id): void
    {
        $a = ArrendatarioParqueo::findOrFail($id);
        $this->authorize('update', $a);
        $this->arrendatarioId = $a->id;
        $this->nombre         = $a->nombre_completo;

        Flux::modal('desactivar-arrendatario')->show();
    }

    public function desactivar(AlquilerParqueoService $service): void
    {
        $a = ArrendatarioParqueo::findOrFail($this->arrendatarioId);
        $this->authorize('update', $a);

        $service->desactivarArrendatario($a);

        $this->reset(['arrendatarioId', 'nombre']);
        Flux::modal('desactivar-arrendatario')->close();
        Flux::toast(text: "{$a->nombre_completo} marcado como inactivo.", variant: 'success');
        $this->dispatch('arrendatario-desactivado');
    }
}; ?>

<flux:modal name="desactivar-arrendatario" class="md:w-[500px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Desactivar arrendatario</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas marcar a <strong>{{ $nombre }}</strong> como inactivo?
            </flux:subheading>
        </div>

        <flux:callout color="amber" icon="information-circle">
            El arrendatario no podrá tener nuevos meses registrados, pero su historial
            de pagos se conserva completo. Puedes reactivarlo en cualquier momento.
        </flux:callout>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="desactivar" variant="danger">
                <span wire:loading.remove wire:target="desactivar">Desactivar</span>
                <span wire:loading wire:target="desactivar">Desactivando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
