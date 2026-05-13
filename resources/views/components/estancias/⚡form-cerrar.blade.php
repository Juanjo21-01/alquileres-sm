<?php

use App\Models\Estancia;
use App\Services\EstanciaService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $estanciaId = null;

    public string $fechaFin = '';

    public string $motivo = '';

    protected function rules(): array
    {
        return [
            'fechaFin' => 'required|date',
            'motivo' => 'nullable|string|max:255',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['fechaFin' => 'fecha de fin'];
    }

    #[On('abrir-form-cerrar-estancia')]
    public function abrir(int $id): void
    {
        $estancia = Estancia::findOrFail($id);
        $this->authorize('update', $estancia);
        $this->reset();
        $this->resetValidation();
        $this->estanciaId = $estancia->id;
        $this->fechaFin = now()->toDateString();

        Flux::modal('form-cerrar-estancia')->show();
    }

    public function guardar(EstanciaService $service): void
    {
        $estancia = Estancia::findOrFail($this->estanciaId);
        $this->authorize('update', $estancia);
        $this->validate();

        $service->cerrar(
            estancia: $estancia,
            fechaFin: $this->fechaFin,
            motivo: $this->motivo ?: null,
        );

        Flux::modal('form-cerrar-estancia')->close();
        Flux::toast(text: 'Estancia cerrada. Cuarto disponible nuevamente.', variant: 'success');
        $this->dispatch('estancia-cerrada', id: $this->estanciaId);
    }

    public function cancelar(): void
    {
        Flux::modal('form-cerrar-estancia')->close();
    }
}; ?>

<flux:modal name="form-cerrar-estancia" class="md:w-[500px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">Cerrar estancia</flux:heading>
            <flux:subheading>El cuarto quedará disponible nuevamente.</flux:subheading>
        </div>

        <flux:input wire:model="fechaFin" label="Fecha de cierre" type="date" required />

        <flux:input
            wire:model="motivo"
            label="Motivo de cierre"
            placeholder="Ej. Fin de contrato, traslado..." />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Cerrar estancia</span>
                <span wire:loading wire:target="guardar">Cerrando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
