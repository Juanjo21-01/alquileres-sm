{{--
    Placeholder animado para tablas mientras cargan. Se muestra con wire:loading.delay
    y se oculta la tabla real. Props: rows (filas), cols (columnas).

    Uso:
    <div wire:loading.delay.long wire:target="busqueda,categoriaId">
        <x-ui.tabla-skeleton :cols="6" />
    </div>
    <div wire:loading.remove.delay.long wire:target="busqueda,categoriaId">
        <flux:table :paginate="$items">...</flux:table>
    </div>
--}}
@props([
    'rows' => 5,
    'cols' => 4,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700']) }} aria-hidden="true">
    {{-- Encabezado --}}
    <div class="flex items-center gap-4 border-b border-zinc-200 bg-zinc-500/5 px-4 py-3 dark:border-zinc-700 dark:bg-white/5">
        @for ($c = 0; $c < (int) $cols; $c++)
            <div class="h-3.5 flex-1 animate-pulse rounded bg-zinc-300 dark:bg-zinc-600"></div>
        @endfor
    </div>

    {{-- Filas --}}
    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @for ($r = 0; $r < (int) $rows; $r++)
            <div class="flex items-center gap-4 px-4 py-3.5">
                @for ($c = 0; $c < (int) $cols; $c++)
                    <div class="h-4 flex-1 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                @endfor
            </div>
        @endfor
    </div>
</div>
