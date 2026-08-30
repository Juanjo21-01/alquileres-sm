<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh bg-zinc-50 antialiased dark:bg-zinc-900">
        <main class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 text-center">
            {{-- Marca --}}
            <div class="flex flex-col items-center gap-5">
                <x-app-logo-icon class="size-24 drop-shadow-sm" />

                <div class="space-y-2">
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
                        {{ config('app.name', 'Alquileres SM') }}
                    </h1>
                    <p class="mx-auto max-w-md text-zinc-500 dark:text-zinc-400">
                        Sistema de gestión de alquileres de cuartos para estudiantes y personal de salud
                        en San Marcos, Guatemala.
                    </p>
                </div>
            </div>

            {{-- Acceso --}}
            <div class="flex flex-wrap items-center justify-center gap-3">
                @auth
                    <flux:button href="{{ route('dashboard') }}" variant="primary" icon="squares-2x2">
                        Ir al panel
                    </flux:button>
                @endauth
                @guest
                    <flux:button href="{{ route('login') }}" variant="primary" icon="arrow-right-end-on-rectangle">
                        Ingresar
                    </flux:button>
                @endguest
            </div>
        </main>

        @fluxScripts
    </body>
</html>
