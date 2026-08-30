{{-- Toggle de apariencia claro / oscuro / sistema. Reusa el motor de Flux (@fluxAppearance). --}}
<flux:dropdown x-data position="bottom" align="end">
    <flux:button variant="subtle" size="sm" square aria-label="Cambiar tema">
        <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" />
        <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" />
        <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
        <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
    </flux:button>

    <flux:menu>
        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Claro</flux:menu.item>
        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Oscuro</flux:menu.item>
        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">Sistema</flux:menu.item>
    </flux:menu>
</flux:dropdown>
