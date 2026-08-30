<?php

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public string $titulo = '';
    public string $url = '';
    public string $descargaUrl = '';
    public bool $esImagen = false;

    #[On('abrir-visor-pdf')]
    public function abrir(string $url, string $titulo = '', string $descargaUrl = '', bool $esImagen = false): void
    {
        $this->url = $url;
        $this->titulo = $titulo;
        $this->descargaUrl = $descargaUrl;
        $this->esImagen = $esImagen;

        Flux::modal('visor-pdf')->show();
    }
}; ?>

<flux:modal name="visor-pdf" class="w-[92vw] max-w-6xl">
    <div class="flex flex-col" style="height: 82vh;">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 pb-3 pr-10 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-2 min-w-0">
                <flux:icon.document class="size-5 shrink-0 text-zinc-400" />
                <flux:heading size="lg" class="truncate">{{ $titulo ?: 'Comprobante' }}</flux:heading>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if ($descargaUrl)
                    <flux:button href="{{ $descargaUrl }}" size="sm" variant="outline" icon="arrow-down-tray">
                        Descargar
                    </flux:button>
                @endif
                @if ($url)
                    <flux:button href="{{ $url }}" target="_blank" size="sm" variant="outline" icon="arrow-top-right-on-square">
                        Abrir
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Contenido: imagen o PDF embebido --}}
        <div class="flex-1 mt-3 overflow-auto rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if ($url)
                @if ($esImagen)
                    <div class="flex items-center justify-center h-full p-4">
                        <img src="{{ $url }}" alt="{{ $titulo }}" class="max-w-full max-h-full object-contain" />
                    </div>
                @else
                    <embed src="{{ $url }}" type="application/pdf" class="w-full h-full" />
                @endif
            @endif
        </div>
    </div>
</flux:modal>
