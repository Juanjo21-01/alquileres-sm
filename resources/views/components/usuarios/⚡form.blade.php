<?php

use App\Models\Rol;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $usuarioId = null;
    public string $name = '';
    public string $email = '';
    public ?int $rol_id = null;
    public ?string $rolNombre = null;
    public string $password = '';
    public bool $activo = true;

    protected function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->usuarioId),
            ],
            'rol_id'   => ['required', Rule::exists('roles', 'id')->where('activo', true)],
            // En creación la contraseña es obligatoria; al editar es opcional
            // (vacía = no se modifica).
            'password' => [$this->usuarioId ? 'nullable' : 'required', 'string', 'min:8'],
            'activo'   => 'boolean',
        ];
    }

    #[On('abrir-form-usuario')]
    public function abrir(?int $id = null): void
    {
        $this->reset();
        $this->resetValidation();

        if ($id) {
            $u = User::findOrFail($id);
            $this->authorize('update', $u);
            $this->usuarioId = $u->id;
            $this->name      = $u->name;
            $this->email     = $u->email;
            $this->rol_id    = $u->rol_id;
            $this->rolNombre = $u->rol?->nombre;
            $this->activo    = $u->activo;
        } else {
            $this->authorize('create', User::class);
        }

        Flux::modal('form-usuario')->show();
    }

    public function guardar(): void
    {
        $datos = $this->validate();
        $datos['email'] = mb_strtolower(trim($datos['email']));

        if ($this->usuarioId) {
            $u = User::findOrFail($this->usuarioId);
            $this->authorize('update', $u);

            // El rol no es editable: se conserva siempre el original,
            // ignorando cualquier valor manipulado desde el cliente.
            $datos['rol_id'] = $u->rol_id;

            // Solo se actualiza la contraseña si el admin escribió una nueva.
            if ($datos['password'] === '') {
                unset($datos['password']);
            }

            $u->update($datos);
            $mensaje = 'Usuario actualizado correctamente.';
        } else {
            $this->authorize('create', User::class);
            User::create($datos);
            $mensaje = 'Usuario creado correctamente.';
        }

        Flux::modal('form-usuario')->close();
        Flux::toast(text: $mensaje, variant: 'success');
        $this->dispatch('usuario-guardado');
    }

    public function cancelar(): void
    {
        Flux::modal('form-usuario')->close();
    }

    public function with(): array
    {
        return [
            'roles' => Rol::where('activo', true)->orderBy('nombre')->get(),
        ];
    }
}; ?>

<flux:modal name="form-usuario" class="md:w-137.5">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $usuarioId ? 'Editar usuario' : 'Nuevo usuario' }}
            </flux:heading>
            <flux:subheading>
                Operador del sistema (administrador o encargado).
            </flux:subheading>
        </div>

        <flux:input
            wire:model="name"
            label="Nombre"
            placeholder="Nombre completo del operador"
            required />

        <flux:input
            wire:model="email"
            type="email"
            label="Correo"
            placeholder="usuario@correo.com"
            required />

        @if ($usuarioId)
            <flux:input
                label="Rol"
                :value="$rolNombre"
                readonly
                disabled
                description="El rol no puede modificarse una vez creado el usuario." />
        @else
            <flux:select wire:model="rol_id" label="Rol" placeholder="Seleccione un rol..." required>
                @foreach ($roles as $rol)
                    <flux:select.option :value="$rol->id">{{ $rol->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:input
            wire:model="password"
            type="password"
            label="Contraseña"
            placeholder="Mínimo 8 caracteres"
            :description="$usuarioId ? 'Déjala en blanco para conservar la contraseña actual.' : 'Contraseña inicial de acceso.'"
            :required="! $usuarioId" />

        <flux:switch wire:model="activo" label="Usuario activo" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="outline">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
