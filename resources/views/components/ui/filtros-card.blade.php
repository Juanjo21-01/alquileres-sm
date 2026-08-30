{{--
    Card contenedora de filtros (patrón de /gastos). El slot principal son los
    controles de filtro (el módulo los distribuye); el slot "actions" es el pie
    con los botones Buscar / Limpiar cuando el módulo usa filtros diferidos.

    Uso (el slot principal son los inputs/selects del módulo):
    <x-ui.filtros-card>
        ...inputs/selects...
        <x-slot:actions>
            <flux:button variant="outline" icon="x-mark" wire:click="limpiar">Limpiar</flux:button>
            <flux:button variant="primary" icon="magnifying-glass" wire:click="buscar">Buscar</flux:button>
        </x-slot:actions>
    </x-ui.filtros-card>
--}}
@props([
    'title' => null,
])

<flux:card class="space-y-4">
    @if ($title)
        <div class="flex items-center gap-2">
            <flux:icon.funnel variant="micro" class="text-zinc-400" />
            <flux:subheading>{{ $title }}</flux:subheading>
        </div>
    @endif

    {{ $slot }}

    @isset($actions)
        <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            {{ $actions }}
        </div>
    @endisset
</flux:card>
