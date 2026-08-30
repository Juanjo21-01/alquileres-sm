{{--
    Estado vacío con ícono, título, descripción opcional y CTA (slot).
    Pensado para ir dentro de la celda @empty de una tabla (colspan) o como bloque suelto.

    Uso:
    <x-ui.empty-state icon="building-office-2" title="No hay propiedades"
                      description="Comienza registrando tu primera propiedad.">
        <flux:button variant="primary" icon="plus" size="sm" wire:click="$dispatch('abrir-form-propiedad')">
            Nueva propiedad
        </flux:button>
    </x-ui.empty-state>
--}}
@props([
    'icon' => 'inbox',
    'title' => 'Sin registros',
    'description' => null,
])

<div class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
    <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
        <flux:icon :icon="$icon" variant="outline" class="size-6" />
    </div>

    <div class="space-y-1">
        <flux:heading size="lg">{{ $title }}</flux:heading>
        @if ($description)
            <flux:subheading>{{ $description }}</flux:subheading>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="mt-1">
            {{ $slot }}
        </div>
    @endif
</div>
