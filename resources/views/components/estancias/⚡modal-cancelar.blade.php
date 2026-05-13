<?php

use App\Models\Estancia;
use App\Services\EstanciaService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $estanciaId = null;

    public string $motivo = '';

    protected function rules(): array
    {
        return [
            'motivo' => 'required|string|max:255',
        ];
    }

    #[On('confirmar-cancelar-estancia')]
    public function abrir(int $id): void
    {
        $estancia = Estancia::findOrFail($id);
        $this->authorize('cancelar', $estancia);
        $this->reset();
        $this->resetValidation();
        $this->estanciaId = $estancia->id;

        Flux::modal('cancelar-estancia')->show();
    }

    public function cancelarEstancia(EstanciaService $service): void
    {
        $estancia = Estancia::findOrFail($this->estanciaId);
        $this->authorize('cancelar', $estancia);
        $this->validate();

        $service->cancelar(estancia: $estancia, motivo: $this->motivo);

        Flux::modal('cancelar-estancia')->close();
        Flux::toast(text: 'Estancia cancelada.', variant: 'success');
        $this->dispatch('estancia-cancelada', id: $this->estanciaId);
    }

    public function cerrarModal(): void
    {
        Flux::modal('cancelar-estancia')->close();
    }
}; ?>

<flux:modal name="cancelar-estancia" class="md:w-[500px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Cancelar estancia</flux:heading>
            <flux:subheading>
                Esta acción cancela la estancia y deja el cuarto disponible. Requiere motivo.
            </flux:subheading>
        </div>

        <flux:input
            wire:model="motivo"
            label="Motivo de cancelación"
            placeholder="Describe el motivo..."
            required />

        @error('motivo')
            <flux:error>{{ $message }}</flux:error>
        @enderror

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cerrar</flux:button>
            </flux:modal.close>
            <flux:button wire:click="cancelarEstancia" variant="danger">
                <span wire:loading.remove wire:target="cancelarEstancia">Cancelar estancia</span>
                <span wire:loading wire:target="cancelarEstancia">Cancelando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
