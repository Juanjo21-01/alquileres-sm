<?php

use App\Models\ArrendatarioParqueo;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $arrendatarioId = null;
    public string $nombreCompleto = '';
    public string $telefono = '';
    public string $ocupacion = 'otro';
    public string $placa = '';
    public bool $activo = true;
    public string $notas = '';

    protected function rules(): array
    {
        return [
            'nombreCompleto' => 'required|string|max:150',
            'telefono'       => 'nullable|string|max:8',
            'ocupacion'      => 'required|in:estudiante,salud,otro',
            'placa'          => 'nullable|string|max:20',
            'activo'         => 'boolean',
            'notas'          => 'nullable|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombreCompleto' => 'nombre completo',
        ];
    }

    #[On('abrir-form-arrendatario')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();
        $this->ocupacion = 'otro';
        $this->activo = true;

        if ($id) {
            $a = ArrendatarioParqueo::findOrFail($id);
            $this->authorize('update', $a);
            $this->arrendatarioId  = $a->id;
            $this->nombreCompleto  = $a->nombre_completo;
            $this->telefono        = $a->telefono ?? '';
            $this->ocupacion       = $a->ocupacion;
            $this->placa           = $a->placa ?? '';
            $this->activo          = $a->activo;
            $this->notas           = $a->notas ?? '';
        } else {
            $this->authorize('create', ArrendatarioParqueo::class);
        }

        Flux::modal('form-arrendatario')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        // Normalizar strings vacíos a null para columnas nullable
        $datos['telefono'] = $datos['telefono'] ?: null;
        $datos['placa']    = $datos['placa'] ?: null;
        $datos['notas']    = $datos['notas'] ?: null;

        // Mapear camelCase → snake_case
        $payload = [
            'nombre_completo' => $datos['nombreCompleto'],
            'telefono'        => $datos['telefono'],
            'ocupacion'       => $datos['ocupacion'],
            'placa'           => $datos['placa'],
            'activo'          => $datos['activo'],
            'notas'           => $datos['notas'],
        ];

        if ($this->arrendatarioId) {
            $a = ArrendatarioParqueo::findOrFail($this->arrendatarioId);
            $this->authorize('update', $a);
            $a->update($payload);
            $mensaje = 'Arrendatario actualizado correctamente.';
        } else {
            $this->authorize('create', ArrendatarioParqueo::class);
            ArrendatarioParqueo::create($payload);
            $mensaje = 'Arrendatario registrado correctamente.';
        }

        Flux::modal('form-arrendatario')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('arrendatario-guardado');
    }

    public function cancelar(): void
    {
        Flux::modal('form-arrendatario')->close();
    }
}; ?>

<flux:modal name="form-arrendatario" class="md:w-[560px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $arrendatarioId ? 'Editar arrendatario' : 'Nuevo arrendatario de parqueo' }}
            </flux:heading>
            <flux:subheading>
                Persona externa que alquila un espacio de parqueo.
            </flux:subheading>
        </div>

        <flux:input
            wire:model="nombreCompleto"
            label="Nombre completo"
            placeholder="Ej. Juan García López"
            required />
        @error('nombreCompleto') <flux:error>{{ $message }}</flux:error> @enderror

        <div class="grid grid-cols-2 gap-3">
            <flux:input
                wire:model="telefono"
                label="Teléfono"
                placeholder="00000000"
                maxlength="8" />

            <div>
                <flux:select wire:model="ocupacion" label="Ocupación">
                    <flux:select.option value="estudiante">Estudiante</flux:select.option>
                    <flux:select.option value="salud">Personal de salud</flux:select.option>
                    <flux:select.option value="otro">Otro</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:input
            wire:model="placa"
            label="Número de placa"
            placeholder="Ej. P-123ABC (opcional)"
            class="uppercase" />

        <flux:textarea wire:model="notas" label="Notas" rows="2" />

        @if ($arrendatarioId)
            <flux:switch wire:model="activo" label="Arrendatario activo" />
        @endif

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
