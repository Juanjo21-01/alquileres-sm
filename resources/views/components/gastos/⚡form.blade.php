<?php

use App\Models\CategoriaGasto;
use App\Models\Cuarto;
use App\Models\Gasto;
use App\Models\Propiedad;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $gastoId = null;
    public string $categoriaGastoId = '';
    public string $propiedadId = '';
    public string $cuartoId = '';
    public string $fecha = '';
    public string $monto = '';
    public string $descripcion = '';
    public string $proveedor = '';
    public string $metodoPago = 'efectivo';
    public string $notas = '';

    public $comprobante = null;
    public ?string $comprobanteActual = null;

    protected function rules(): array
    {
        return [
            'categoriaGastoId' => 'required|exists:categorias_gasto,id',
            'propiedadId'      => [$this->requierePropiedad ? 'required' : 'nullable', 'exists:propiedades,id'],
            'cuartoId'         => [$this->requiereCuarto ? 'required' : 'nullable', 'exists:cuartos,id'],
            'fecha'            => 'required|date',
            'monto'            => 'required|numeric|min:0.01',
            'descripcion'      => 'required|string|max:255',
            'proveedor'        => 'nullable|string|max:150',
            'metodoPago'       => 'required|in:efectivo,cuenta',
            'comprobante'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notas'            => 'nullable|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'categoriaGastoId' => 'categoría',
            'propiedadId'      => 'propiedad',
            'cuartoId'         => 'cuarto',
            'metodoPago'       => 'método de pago',
        ];
    }

    #[Computed]
    public function categoriaSeleccionada(): ?CategoriaGasto
    {
        return $this->categoriaGastoId
            ? CategoriaGasto::find($this->categoriaGastoId)
            : null;
    }

    #[Computed]
    public function requierePropiedad(): bool
    {
        return (bool) $this->categoriaSeleccionada?->requiere_propiedad;
    }

    #[Computed]
    public function requiereCuarto(): bool
    {
        return (bool) $this->categoriaSeleccionada?->requiere_cuarto;
    }

    #[Computed]
    public function cuartosDisponibles()
    {
        return $this->propiedadId
            ? Cuarto::where('propiedad_id', $this->propiedadId)
                ->where('activo', true)
                ->orderBy('codigo')
                ->get()
            : collect();
    }

    public function updatedCategoriaGastoId(): void
    {
        // Si la nueva categoría no requiere cuarto, limpiar selección de cuarto.
        if (! $this->requiereCuarto) {
            $this->cuartoId = '';
        }
        // Si no requiere propiedad, limpiar propiedad y cuarto.
        if (! $this->requierePropiedad) {
            $this->propiedadId = '';
            $this->cuartoId = '';
        }
    }

    public function updatedPropiedadId(): void
    {
        // El cuarto depende de la propiedad: al cambiarla, resetear cuarto.
        $this->cuartoId = '';
    }

    #[On('abrir-form-gasto')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();
        $this->fecha = now()->toDateString();
        $this->metodoPago = 'efectivo';

        if ($id) {
            $g = Gasto::findOrFail($id);
            $this->authorize('update', $g);
            $this->gastoId           = $g->id;
            $this->categoriaGastoId  = (string) $g->categoria_gasto_id;
            $this->propiedadId       = (string) ($g->propiedad_id ?? '');
            $this->cuartoId          = (string) ($g->cuarto_id ?? '');
            $this->fecha             = $g->fecha->toDateString();
            $this->monto             = (string) (float) $g->monto;
            $this->descripcion       = $g->descripcion;
            $this->proveedor         = $g->proveedor ?? '';
            $this->metodoPago        = $g->metodo_pago;
            $this->notas             = $g->notas ?? '';
            $this->comprobanteActual = $g->comprobante_path;
        } else {
            $this->authorize('create', Gasto::class);
        }

        Flux::modal('form-gasto')->show();
    }

    protected function prepareForValidation($attributes)
    {
        // El cuarto debe pertenecer a la propiedad seleccionada; si no, descartarlo.
        if ($this->cuartoId && $this->propiedadId) {
            $cuarto = Cuarto::find($this->cuartoId);
            if (! $cuarto || (string) $cuarto->propiedad_id !== $this->propiedadId) {
                $this->cuartoId = '';
                $attributes['cuartoId'] = '';
            }
        }

        return $attributes;
    }

    public function guardar(): void
    {
        $validado = $this->validate();

        $datos = [
            'categoria_gasto_id' => $validado['categoriaGastoId'],
            'propiedad_id'       => $validado['propiedadId'] ?: null,
            'cuarto_id'          => $validado['cuartoId'] ?: null,
            'fecha'              => $validado['fecha'],
            'monto'              => round((float) $validado['monto'], 2),
            'descripcion'        => $validado['descripcion'],
            'proveedor'          => $validado['proveedor'] ?: null,
            'metodo_pago'        => $validado['metodoPago'],
            'notas'              => $validado['notas'] ?: null,
        ];

        // Comprobante nuevo (opcional): guardar en disco local privado.
        $pathNuevo = null;
        if ($this->comprobante) {
            $pathNuevo = $this->comprobante->store('comprobantes', 'local');
            $datos['comprobante_path'] = $pathNuevo;
        }

        if ($this->gastoId) {
            $g = Gasto::findOrFail($this->gastoId);
            $this->authorize('update', $g);

            $pathAnterior = $g->comprobante_path;
            $g->update($datos);

            // Si se reemplazó el comprobante, borrar el archivo anterior.
            if ($pathNuevo && $pathAnterior && $pathAnterior !== $pathNuevo) {
                Storage::delete($pathAnterior);
            }

            $mensaje = 'Gasto actualizado correctamente.';
        } else {
            $this->authorize('create', Gasto::class);
            $datos['user_registro_id'] = auth()->id();
            Gasto::create($datos);
            $mensaje = 'Gasto registrado correctamente.';
        }

        Flux::modal('form-gasto')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('gasto-guardado');
    }

    public function cancelar(): void
    {
        Flux::modal('form-gasto')->close();
    }

    public function with(): array
    {
        return [
            'categorias'  => CategoriaGasto::activas()->orderBy('nombre')->get(),
            'propiedades' => Propiedad::where('activo', true)->orderBy('nombre')->get(),
        ];
    }
}; ?>

<flux:modal name="form-gasto" class="md:w-[640px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $gastoId ? 'Editar gasto' : 'Nuevo gasto' }}
            </flux:heading>
            <flux:subheading>Egreso operativo del negocio.</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-3">
            {{-- Categoría --}}
            <div>
                <flux:select wire:model.live="categoriaGastoId" label="Categoría" required>
                    <flux:select.option value="">Seleccionar...</flux:select.option>
                    @foreach ($categorias as $categoria)
                        <flux:select.option value="{{ $categoria->id }}">{{ $categoria->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('categoriaGastoId') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            {{-- Fecha --}}
            <flux:input wire:model="fecha" type="date" label="Fecha" required />
        </div>

        {{-- Propiedad / Cuarto — visibles según la categoría --}}
        @if ($this->requierePropiedad || $propiedadId)
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:select wire:model.live="propiedadId" label="Propiedad" :required="$this->requierePropiedad">
                        <flux:select.option value="">Sin propiedad</flux:select.option>
                        @foreach ($propiedades as $propiedad)
                            <flux:select.option value="{{ $propiedad->id }}">{{ $propiedad->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('propiedadId') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                @if ($this->requiereCuarto || $cuartoId)
                    <div>
                        <flux:select wire:model="cuartoId" label="Cuarto" :required="$this->requiereCuarto" :disabled="! $propiedadId">
                            <flux:select.option value="">Sin cuarto</flux:select.option>
                            @foreach ($this->cuartosDisponibles as $cuarto)
                                <flux:select.option value="{{ $cuarto->id }}">{{ $cuarto->codigo }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('cuartoId') <flux:error>{{ $message }}</flux:error> @enderror
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            {{-- Monto --}}
            <div>
                <flux:input wire:model="monto" type="number" min="0.01" step="0.01" label="Monto (Q)" required />
                @error('monto') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            {{-- Método de pago --}}
            <flux:select wire:model="metodoPago" label="Método de pago" required>
                <flux:select.option value="efectivo">Efectivo</flux:select.option>
                <flux:select.option value="cuenta">Transferencia / Cuenta</flux:select.option>
            </flux:select>
        </div>

        {{-- Descripción --}}
        <div>
            <flux:input wire:model="descripcion" label="Descripción" placeholder="Ej. Recibo de luz mayo, reparación de fuga..." required />
            @error('descripcion') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        {{-- Proveedor --}}
        <flux:input wire:model="proveedor" label="Proveedor" placeholder="Opcional" />

        {{-- Comprobante --}}
        <div>
            <flux:label>Comprobante (opcional)</flux:label>
            @if ($comprobanteActual)
                <p class="mt-1 text-xs text-zinc-500 flex items-center gap-1">
                    <flux:icon.paper-clip class="size-3.5" />
                    Ya hay un comprobante adjunto. Sube uno nuevo para reemplazarlo.
                </p>
            @endif
            <input
                type="file"
                wire:model="comprobante"
                accept=".jpg,.jpeg,.png,.pdf"
                class="mt-2 block w-full text-sm text-zinc-600 dark:text-zinc-400 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 dark:file:bg-zinc-700 file:px-3 file:py-1.5 file:text-sm file:font-medium" />
            <div wire:loading wire:target="comprobante" class="mt-1 text-xs text-blue-500">Subiendo...</div>
            @error('comprobante') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        {{-- Notas --}}
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
