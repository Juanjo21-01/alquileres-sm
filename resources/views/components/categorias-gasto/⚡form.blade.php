<?php

use App\Models\CategoriaGasto;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $categoriaId = null;
    public string $nombre = '';
    public string $codigo = '';
    public string $descripcion = '';
    public bool $requiere_propiedad = false;
    public bool $requiere_cuarto = false;
    public bool $activo = true;

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:80',
                Rule::unique('categorias_gasto', 'nombre')->ignore($this->categoriaId),
            ],
            'codigo' => [
                'nullable', 'string', 'max:20',
                Rule::unique('categorias_gasto', 'codigo')->ignore($this->categoriaId),
            ],
            'descripcion'        => 'nullable|string|max:255',
            'requiere_propiedad' => 'boolean',
            'requiere_cuarto'    => 'boolean',
            'activo'             => 'boolean',
        ];
    }

    #[On('abrir-form-categoria-gasto')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();

        if ($id) {
            $c = CategoriaGasto::findOrFail($id);
            $this->authorize('update', $c);
            $this->categoriaId        = $c->id;
            $this->nombre             = $c->nombre;
            $this->codigo             = $c->codigo ?? '';
            $this->descripcion        = $c->descripcion ?? '';
            $this->requiere_propiedad = $c->requiere_propiedad;
            $this->requiere_cuarto    = $c->requiere_cuarto;
            $this->activo             = $c->activo;
        } else {
            $this->authorize('create', CategoriaGasto::class);
        }

        Flux::modal('form-categoria-gasto')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        // Si requiere cuarto, también requiere propiedad (un cuarto pertenece a una propiedad).
        if ($datos['requiere_cuarto']) {
            $datos['requiere_propiedad'] = true;
        }

        // Convertir strings vacíos a null para columnas nullable.
        $datos['codigo']      = $datos['codigo'] ?: null;
        $datos['descripcion'] = $datos['descripcion'] ?: null;

        if ($this->categoriaId) {
            $c = CategoriaGasto::findOrFail($this->categoriaId);
            $this->authorize('update', $c);
            $c->update($datos);
            $mensaje = 'Categoría actualizada correctamente.';
        } else {
            $this->authorize('create', CategoriaGasto::class);
            CategoriaGasto::create($datos);
            $mensaje = 'Categoría creada correctamente.';
        }

        Flux::modal('form-categoria-gasto')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('categoria-gasto-guardada');
    }

    public function cancelar(): void
    {
        Flux::modal('form-categoria-gasto')->close();
    }
}; ?>

<flux:modal name="form-categoria-gasto" class="md:w-137.5">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $categoriaId ? 'Editar categoría' : 'Nueva categoría' }}
            </flux:heading>
            <flux:subheading>
                Tipo de egreso del negocio (luz, agua, mantenimiento...).
            </flux:subheading>
        </div>

        <flux:input
            wire:model="nombre"
            label="Nombre"
            placeholder="Luz, Agua, Mantenimiento..."
            required />

        <flux:input
            wire:model="codigo"
            label="Código"
            placeholder="luz, agua, mantenimiento..."
            description="Opcional. Identificador corto en minúsculas." />

        <flux:textarea wire:model="descripcion" label="Descripción" rows="2" />

        <div class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <flux:switch
                wire:model.live="requiere_propiedad"
                label="Requiere propiedad"
                description="Al registrar el gasto, será obligatorio indicar a qué propiedad pertenece." />

            <flux:switch
                wire:model.live="requiere_cuarto"
                label="Requiere cuarto"
                description="Obliga a especificar el cuarto. Implica también requerir propiedad." />
        </div>

        <flux:switch wire:model="activo" label="Categoría activa" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="outline">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
