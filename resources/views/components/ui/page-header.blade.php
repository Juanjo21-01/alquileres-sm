{{--
    Encabezado de página reutilizable: título (+ subtítulo opcional) a la izquierda
    y acciones (CTA) a la derecha. En mobile se apila; en sm+ va en fila.

    Uso:
    <x-ui.page-header title="Propiedades" subtitle="Gestión de inmuebles">
        <flux:button variant="primary" icon="plus" wire:click="abrirFormNuevo">Nueva propiedad</flux:button>
    </x-ui.page-header>
--}}
@props([
    'title',
    'subtitle' => null,
])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="space-y-1">
        <flux:heading size="xl">{{ $title }}</flux:heading>
        @if ($subtitle)
            <flux:subheading>{{ $subtitle }}</flux:subheading>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
