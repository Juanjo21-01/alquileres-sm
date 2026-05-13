<?php

use App\Models\Cuarto;
use App\Models\Propiedad;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;
use Illuminate\Validation\Rule;

new class extends Component {
    public ?int $cuartoId = null;
    public ?int $propiedadId = null;
    public string $codigo = '';
    public int $nivel = 1;
    public string $tamano = '';
    public string $precio_base = '';
    public string $estado = Cuarto::ESTADO_DISPONIBLE;
    public string $descripcion = '';
    public bool $activo = true;

    public bool $tieneEstanciaActiva = false;

    protected function rules(): array
    {
        return [
            'codigo'      => [
                'required', 'string', 'max:20',
                Rule::unique('cuartos')
                    ->where('propiedad_id', $this->propiedadId)
                    ->ignore($this->cuartoId),
            ],
            'nivel'       => 'required|integer|min:1|max:20',
            'tamano'      => 'nullable|string|max:50',
            'precio_base' => 'required|numeric|min:0',
            'estado'      => ['required', Rule::in(array_keys(Cuarto::estados()))],
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ];
    }

    #[On('abrir-form-cuarto')]
    public function abrir(?int $id = null, ?int $propiedadId = null): void
    {
        $this->reset();
        $this->resetValidation();
        $this->estado = Cuarto::ESTADO_DISPONIBLE;
        $this->nivel = 1;
        $this->tieneEstanciaActiva = false;

        if ($id) {
            $c = Cuarto::findOrFail($id);
            $this->authorize('update', $c);
            $this->cuartoId            = $c->id;
            $this->propiedadId         = $c->propiedad_id;
            $this->codigo              = $c->codigo;
            $this->nivel               = $c->nivel;
            $this->tamano              = $c->tamano ?? '';
            $this->precio_base         = (string) $c->precio_base;
            $this->estado              = $c->estado;
            $this->descripcion         = $c->descripcion ?? '';
            $this->activo              = $c->activo;
            $this->tieneEstanciaActiva = $c->estanciaActiva()->exists();
        } else {
            $this->authorize('create', Cuarto::class);
            $this->propiedadId = $propiedadId;
        }

        Flux::modal('form-cuarto')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        // Convertir strings vacíos a null para columnas nullable
        $datos['tamano']      = $datos['tamano'] ?: null;
        $datos['descripcion'] = $datos['descripcion'] ?: null;

        if ($this->cuartoId) {
            $c = Cuarto::findOrFail($this->cuartoId);
            $this->authorize('update', $c);

            if ($this->tieneEstanciaActiva && $this->estado !== Cuarto::ESTADO_OCUPADO) {
                $this->addError('estado', 'El cuarto tiene una estancia activa, no se puede cambiar el estado.');

                return;
            }

            $c->update($datos + ['propiedad_id' => $this->propiedadId]);
            $mensaje = 'Cuarto actualizado correctamente.';
        } else {
            $this->authorize('create', Cuarto::class);
            Cuarto::create($datos + ['propiedad_id' => $this->propiedadId]);
            $mensaje = 'Cuarto creado correctamente.';
        }

        Flux::modal('form-cuarto')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('cuarto-guardado');
    }

    public function cancelar(): void
    {
        Flux::modal('form-cuarto')->close();
    }
}; ?>

<flux:modal name="form-cuarto" class="md:w-[560px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $cuartoId ? 'Editar cuarto' : 'Nuevo cuarto' }}
            </flux:heading>
            <flux:subheading>Datos del cuarto de alquiler.</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input
                wire:model="codigo"
                label="Código"
                placeholder="A-01, 101..."
                required />

            <flux:input
                wire:model="nivel"
                label="Nivel / Piso"
                type="number"
                min="1"
                max="20"
                required />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input
                wire:model="precio_base"
                label="Precio base (Q)"
                type="number"
                step="0.01"
                min="0"
                required />

            <flux:input
                wire:model="tamano"
                label="Tamaño"
                placeholder="10m², pequeño..." />
        </div>

        @if ($cuartoId)
            <div>
                <flux:select wire:model="estado" label="Estado" :disabled="$tieneEstanciaActiva">
                    @foreach (Cuarto::estados() as $valor => $etiqueta)
                        <flux:select.option value="{{ $valor }}">{{ $etiqueta }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($tieneEstanciaActiva)
                    <p class="mt-1 text-xs text-zinc-500">El estado no se puede cambiar mientras el cuarto tenga una estancia activa.</p>
                @endif
                @error('estado') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        @endif

        <flux:textarea wire:model="descripcion" label="Descripción" rows="2" />

        <flux:switch wire:model="activo" label="Cuarto activo" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
