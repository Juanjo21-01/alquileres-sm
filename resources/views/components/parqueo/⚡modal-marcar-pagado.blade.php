<?php

use App\Models\AlquilerParqueo;
use App\Services\AlquilerParqueoService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $alquilerId = null;
    public ?string $mesInfo = null;
    public string $metodoPago = 'efectivo';
    public string $fechaPago = '';
    public string $notas = '';
    public bool $tieneNotas = false;

    protected function rules(): array
    {
        return [
            'metodoPago' => 'required|in:efectivo,cuenta',
            'fechaPago'  => 'required|date',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'metodoPago' => 'método de pago',
            'fechaPago'  => 'fecha de pago',
        ];
    }

    #[On('confirmar-marcar-pagado')]
    public function abrir(int $id): void
    {
        $alquiler = AlquilerParqueo::findOrFail($id);
        $this->authorize('update', $alquiler);

        $this->alquilerId  = $alquiler->id;
        $this->mesInfo     = \Carbon\Carbon::parse($alquiler->mes)->translatedFormat('F Y')
            . ' — Q ' . number_format((float) $alquiler->monto, 2);
        $this->metodoPago  = 'efectivo';
        $this->fechaPago   = now()->toDateString();
        $this->tieneNotas  = $alquiler->notas !== null;
        $this->notas       = '';

        Flux::modal('marcar-pagado')->show();
    }

    public function confirmar(AlquilerParqueoService $service): void
    {
        $this->validate();

        $alquiler = AlquilerParqueo::findOrFail($this->alquilerId);
        $this->authorize('update', $alquiler);

        $service->marcarPagado(
            $alquiler,
            $this->metodoPago,
            $this->fechaPago,
            $this->notas ?: null,
        );

        $this->reset(['alquilerId', 'mesInfo', 'metodoPago', 'fechaPago', 'notas', 'tieneNotas']);
        Flux::modal('marcar-pagado')->close();
        Flux::toast(text: 'Mes marcado como pagado.', variant: 'success');
        $this->dispatch('mes-pagado');
    }
}; ?>

<flux:modal name="marcar-pagado" class="md:w-[460px]">
    <form wire:submit="confirmar" class="space-y-4">
        <div>
            <flux:heading size="lg">Registrar pago de parqueo</flux:heading>
            <flux:subheading>
                Marcando como pagado: <strong>{{ $mesInfo }}</strong>
            </flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:select wire:model="metodoPago" label="Método de pago" required>
                <flux:select.option value="efectivo">Efectivo</flux:select.option>
                <flux:select.option value="cuenta">Transferencia / Cuenta</flux:select.option>
            </flux:select>
            @error('metodoPago') <flux:error>{{ $message }}</flux:error> @enderror

            <div>
                <flux:input
                    wire:model="fechaPago"
                    type="date"
                    label="Fecha de pago"
                    required />
                @error('fechaPago') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        @if (! $tieneNotas)
            <flux:textarea
                wire:model="notas"
                label="Notas (opcional)"
                rows="2"
                placeholder="Observaciones del pago..." />
        @endif

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="confirmar">Confirmar pago</span>
                <span wire:loading wire:target="confirmar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
