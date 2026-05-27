<?php

use App\Models\Estancia;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $estanciaId = null;

    public string $fechaFinEstimada = '';

    public string $precioAcordado = '';

    public string $anticipo = '0';

    public string $notas = '';

    protected function rules(): array
    {
        return [
            'fechaFinEstimada' => 'nullable|date',
            'precioAcordado' => 'required|numeric|min:0',
            'anticipo' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'precioAcordado' => 'precio acordado',
            'fechaFinEstimada' => 'fecha fin estimada',
        ];
    }

    #[On('abrir-form-editar-estancia')]
    public function abrir(int $id): void
    {
        $e = Estancia::findOrFail($id);
        $this->authorize('update', $e);

        if (! $e->estaActiva()) {
            return;
        }

        $this->estanciaId = $e->id;
        $this->fechaFinEstimada = $e->fecha_fin_estimada?->toDateString() ?? '';
        $this->precioAcordado = (string) $e->precio_acordado;
        $this->anticipo = (string) $e->anticipo;
        $this->notas = $e->notas ?? '';
        $this->resetValidation();

        Flux::modal('form-editar-estancia')->show();
    }

    public function guardar(): void
    {
        $e = Estancia::findOrFail($this->estanciaId);
        $this->authorize('update', $e);

        if (! $e->estaActiva()) {
            $this->addError('precioAcordado', 'La estancia ya no está activa.');

            return;
        }

        $datos = $this->validate();

        $e->update([
            'fecha_fin_estimada' => $datos['fechaFinEstimada'] ?: null,
            'precio_acordado' => $datos['precioAcordado'],
            'anticipo' => $datos['anticipo'] ?: 0,
            'notas' => $datos['notas'] ?: null,
        ]);

        Flux::modal('form-editar-estancia')->close();
        Flux::toast(text: 'Estancia actualizada correctamente.', variant: 'success');
        $this->dispatch('estancia-editada');
    }

    public function cancelar(): void
    {
        Flux::modal('form-editar-estancia')->close();
    }
}; ?>

<flux:modal name="form-editar-estancia" class="md:w-[560px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">Editar estancia</flux:heading>
            <flux:subheading>Modifica los datos de la estancia activa.</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input
                wire:model="precioAcordado"
                label="Precio acordado (Q)"
                type="number"
                min="0"
                step="0.01"
                required />
            <flux:input
                wire:model="anticipo"
                label="Anticipo (Q)"
                type="number"
                min="0"
                step="0.01" />
        </div>

        <flux:input wire:model="fechaFinEstimada" label="Fecha fin estimada" type="date" />

        <flux:textarea wire:model="notas" label="Notas" rows="2" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
