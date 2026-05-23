@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Alquileres SM" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current bg-amber-100" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Alquileres SM" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current bg-amber-100" />
        </x-slot>
    </flux:brand>
@endif
