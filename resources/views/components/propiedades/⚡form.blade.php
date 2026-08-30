<?php

use App\Models\Propiedad;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $propiedadId = null;
    public string $nombre = '';
    public string $direccion = '';
    public string $zona = '';
    public string $referencia = '';
    public string $notas = '';
    public bool $activo = true;

    protected function rules(): array
    {
        return [
            'nombre'     => 'required|string|max:150',
            'direccion'  => 'required|string|max:255',
            'zona'       => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:255',
            'notas'      => 'nullable|string',
            'activo'     => 'boolean',
        ];
    }

    #[On('abrir-form-propiedad')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();

        if ($id) {
            $p = Propiedad::findOrFail($id);
            $this->authorize('update', $p);
            $this->propiedadId = $p->id;
            $this->nombre      = $p->nombre;
            $this->direccion   = $p->direccion;
            $this->zona        = $p->zona ?? '';
            $this->referencia  = $p->referencia ?? '';
            $this->notas       = $p->notas ?? '';
            $this->activo      = $p->activo;
        } else {
            $this->authorize('create', Propiedad::class);
        }

        Flux::modal('form-propiedad')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        // Convertir strings vacíos a null para columnas nullable
        $datos['zona']       = $datos['zona'] ?: null;
        $datos['referencia'] = $datos['referencia'] ?: null;
        $datos['notas']      = $datos['notas'] ?: null;

        if ($this->propiedadId) {
            $p = Propiedad::findOrFail($this->propiedadId);
            $this->authorize('update', $p);
            $p->update($datos);
            $mensaje = 'Propiedad actualizada correctamente.';
        } else {
            $this->authorize('create', Propiedad::class);
            Propiedad::create($datos);
            $mensaje = 'Propiedad creada correctamente.';
        }

        Flux::modal('form-propiedad')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('propiedad-guardada');
    }

    public function cancelar(): void
    {
        Flux::modal('form-propiedad')->close();
    }
}; ?>

<flux:modal name="form-propiedad" class="md:w-150">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $propiedadId ? 'Editar propiedad' : 'Nueva propiedad' }}
            </flux:heading>
            <flux:subheading>
                Datos principales de la casa de alquiler.
            </flux:subheading>
        </div>

        <flux:input
            wire:model="nombre"
            label="Nombre"
            placeholder="Casa central, Casa norte..."
            required />

        <flux:input
            wire:model="direccion"
            label="Dirección"
            required />

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="zona" label="Zona" />
            <flux:input wire:model="referencia" label="Referencia" />
        </div>

        <flux:textarea wire:model="notas" label="Notas" rows="3" />

        <flux:switch wire:model="activo" label="Propiedad activa" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="outline">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
