<?php

use App\Models\Inquilino;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $inquilinoId = null;

    public string $nombres = '';

    public string $apellidos = '';

    public string $dpi = '';

    public string $telefono = '';

    public string $email = '';

    public string $ocupacion = 'otro';

    public string $institucion = '';

    public string $contacto_emergencia_nombre = '';

    public string $contacto_emergencia_telefono = '';

    public string $notas = '';

    public bool $activo = true;

    public string $vehiculoTipo = '';

    public string $vehiculoPlaca = '';

    public array $instituciones = [];

    protected function rules(): array
    {
        return [
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'dpi' => [
                'nullable', 'string', 'max:13', 'min:13',
                Rule::unique('inquilinos', 'dpi')
                    ->ignore($this->inquilinoId)
                    ->whereNull('deleted_at'),
            ],
            'telefono' => 'nullable|string|max:8',
            'email' => 'nullable|email|max:150',
            'ocupacion' => 'required|in:estudiante,salud,otro',
            'institucion' => 'nullable|string|max:150',
            'contacto_emergencia_nombre' => 'nullable|string|max:150',
            'contacto_emergencia_telefono' => 'nullable|string|max:8',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
            'vehiculoTipo' => 'nullable|in:carro,moto',
            'vehiculoPlaca' => 'nullable|string|max:20',
        ];
    }

    #[On('abrir-form-inquilino')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();
        $this->ocupacion = 'otro';
        $this->instituciones = Inquilino::institucionesDisponibles();

        if ($id) {
            $i = Inquilino::findOrFail($id);
            $this->authorize('update', $i);
            $this->inquilinoId = $i->id;
            $this->nombres = $i->nombres;
            $this->apellidos = $i->apellidos;
            $this->dpi = $i->dpi ?? '';
            $this->telefono = $i->telefono ?? '';
            $this->email = $i->email ?? '';
            $this->ocupacion = $i->ocupacion;
            $this->institucion = $i->institucion ?? '';
            $this->contacto_emergencia_nombre = $i->contacto_emergencia_nombre ?? '';
            $this->contacto_emergencia_telefono = $i->contacto_emergencia_telefono ?? '';
            $this->notas = $i->notas ?? '';
            $this->activo = $i->activo;
            $this->vehiculoTipo = $i->vehiculo_tipo ?? '';
            $this->vehiculoPlaca = $i->vehiculo_placa ?? '';
        } else {
            $this->authorize('create', Inquilino::class);
        }

        Flux::modal('form-inquilino')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        foreach (['dpi', 'telefono', 'email', 'institucion', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono', 'notas', 'vehiculoTipo', 'vehiculoPlaca'] as $campo) {
            $datos[$campo] = $datos[$campo] ?: null;
        }

        $datos['vehiculo_tipo']  = $datos['vehiculoTipo'];
        $datos['vehiculo_placa'] = $datos['vehiculoPlaca'];
        unset($datos['vehiculoTipo'], $datos['vehiculoPlaca']);

        if ($this->inquilinoId) {
            $i = Inquilino::findOrFail($this->inquilinoId);
            $this->authorize('update', $i);

            if (! $this->activo && $i->estanciaActiva()->exists()) {
                $this->addError('activo', 'No se puede desactivar: el inquilino tiene una estancia activa.');

                return;
            }

            $i->update($datos);
            $mensaje = 'Inquilino actualizado correctamente.';
        } else {
            $this->authorize('create', Inquilino::class);
            Inquilino::create($datos);
            $mensaje = 'Inquilino registrado correctamente.';
        }

        Flux::modal('form-inquilino')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('inquilino-guardado');
    }

    public function updatedVehiculoTipo(): void
    {
        if (! $this->vehiculoTipo) {
            $this->vehiculoPlaca = '';
        }
    }

    public function cancelar(): void
    {
        Flux::modal('form-inquilino')->close();
    }
}; ?>

<flux:modal name="form-inquilino" class="md:w-[680px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $inquilinoId ? 'Editar inquilino' : 'Nuevo inquilino' }}
            </flux:heading>
            <flux:subheading>Datos personales del inquilino.</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="nombres" label="Nombres" required />
            <flux:input wire:model="apellidos" label="Apellidos" required />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="dpi" label="DPI" placeholder="0000 00000 0000"/>
            <flux:input wire:model="telefono" label="Teléfono" placeholder="0000-0000" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="email" label="Correo electrónico" type="email" />
            <flux:select wire:model="ocupacion" label="Ocupación">
                <flux:select.option value="estudiante">Estudiante</flux:select.option>
                <flux:select.option value="salud">Personal de salud</flux:select.option>
                <flux:select.option value="otro">Otro</flux:select.option>
            </flux:select>
        </div>

        <div>
            <flux:input
                wire:model="institucion"
                label="Institución / Universidad"
                placeholder="Escribe o selecciona una existente..."
                list="instituciones-list" />
            <datalist id="instituciones-list">
                @foreach ($instituciones as $inst)
                    <option value="{{ $inst }}">
                @endforeach
            </datalist>
        </div>

        <flux:separator text="Contacto de emergencia" />

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="contacto_emergencia_nombre" label="Nombre" />
            <flux:input wire:model="contacto_emergencia_telefono" label="Teléfono" placeholder="0000-0000" />
        </div>

        <flux:textarea wire:model="notas" label="Notas" rows="2" />

        <flux:separator text="Vehículo (opcional)" />

        <div class="grid grid-cols-2 gap-3">
            <flux:select wire:model.live="vehiculoTipo" label="Tipo de vehículo">
                <flux:select.option value="">Sin vehículo</flux:select.option>
                <flux:select.option value="carro">Carro</flux:select.option>
                <flux:select.option value="moto">Moto</flux:select.option>
            </flux:select>

            <flux:input
                wire:model="vehiculoPlaca"
                label="Placa"
                placeholder="Ej. P-123ABC"
                :disabled="! $vehiculoTipo" />
        </div>

        @if ($inquilinoId)
            <div>
                <flux:switch wire:model="activo" label="Inquilino activo" />
                @error('activo') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
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
