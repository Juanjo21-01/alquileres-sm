<?php

use App\Models\Pago;
use App\Services\PagoService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $pagoId = null;
    public ?string $pagoInfo = null;

    #[On('confirmar-eliminar-pago')]
    public function abrir(int $id): void
    {
        $pago = Pago::with('tipoPago')->findOrFail($id);
        $this->authorize('delete', $pago);

        $this->pagoId   = $pago->id;
        $this->pagoInfo = sprintf(
            '%s — %s (Q %s)',
            $pago->tipoPago->nombre,
            $pago->recibo_numero ?? 'sin recibo',
            number_format((float) $pago->monto_neto, 2),
        );

        Flux::modal('eliminar-pago')->show();
    }

    public function eliminar(PagoService $service): void
    {
        $pago = Pago::findOrFail($this->pagoId);
        $this->authorize('delete', $pago);

        $service->eliminar($pago);

        $this->reset(['pagoId', 'pagoInfo']);
        Flux::modal('eliminar-pago')->close();
        Flux::toast(text: 'Pago eliminado correctamente.', variant: 'success');
        $this->dispatch('pago-eliminado');
    }
}; ?>

<flux:modal name="eliminar-pago" class="md:w-125">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Eliminar pago</flux:heading>
            <flux:subheading>
                ¿Seguro que deseas eliminar el pago
                <strong>{{ $pagoInfo }}</strong>?
                Esta acción no se puede deshacer.
            </flux:subheading>
        </div>

        <flux:callout color="amber" icon="exclamation-triangle">
            El número de recibo quedará anulado. Si necesitas corregir un pago,
            elimínalo y registra uno nuevo.
        </flux:callout>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="outline">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="eliminar" variant="danger">
                <span wire:loading.remove wire:target="eliminar">Eliminar pago</span>
                <span wire:loading wire:target="eliminar">Eliminando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
