<?php

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?int $usuarioId = null;
    public ?string $usuarioNombre = null;
    public bool $activo = true;

    #[On('confirmar-toggle-usuario')]
    public function abrir(int $id): void
    {
        $u = User::findOrFail($id);
        $this->authorize('update', $u);

        // La cuenta administrador está protegida: no se desactiva.
        if ($u->esAdministrador()) {
            Flux::toast(text: 'No se puede cambiar el estado de un administrador.', variant: 'warning');

            return;
        }

        $this->usuarioId     = $u->id;
        $this->usuarioNombre = $u->name;
        $this->activo        = $u->activo;

        Flux::modal('toggle-usuario')->show();
    }

    public function confirmar(): void
    {
        $u = User::findOrFail($this->usuarioId);
        $this->authorize('update', $u);

        if ($u->esAdministrador()) {
            Flux::modal('toggle-usuario')->close();
            Flux::toast(text: 'No se puede cambiar el estado de un administrador.', variant: 'warning');

            return;
        }

        $u->update(['activo' => ! $u->activo]);
        $nombre = $u->name;
        $accion = $u->activo ? 'activado' : 'desactivado';
        $this->reset(['usuarioId', 'usuarioNombre', 'activo']);

        Flux::modal('toggle-usuario')->close();
        Flux::toast(text: "Usuario '{$nombre}' {$accion}.", variant: 'success');
        $this->dispatch('usuario-estado-cambiado');
    }
}; ?>

<flux:modal name="toggle-usuario" class="md:w-125">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $activo ? 'Desactivar usuario' : 'Activar usuario' }}
            </flux:heading>
            <flux:subheading>
                @if ($activo)
                    ¿Seguro que deseas desactivar a <strong>{{ $usuarioNombre }}</strong>?
                    No podrá iniciar sesión mientras esté inactivo.
                @else
                    ¿Seguro que deseas activar a <strong>{{ $usuarioNombre }}</strong>?
                    Podrá volver a iniciar sesión.
                @endif
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="confirmar" variant="{{ $activo ? 'danger' : 'primary' }}">
                <span wire:loading.remove wire:target="confirmar">{{ $activo ? 'Desactivar' : 'Activar' }}</span>
                <span wire:loading wire:target="confirmar">Procesando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
