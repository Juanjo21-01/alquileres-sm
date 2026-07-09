<?php

use App\Models\Gasto;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?Gasto $gasto = null;

    #[On('abrir-detalle-gasto')]
    public function abrir(int $id): void
    {
        $gasto = Gasto::with(['categoria', 'propiedad', 'cuarto', 'userRegistro'])->findOrFail($id);
        $this->authorize('view', $gasto);
        $this->gasto = $gasto;

        Flux::modal('detalle-gasto')->show();
    }

    public function verComprobante(): void
    {
        if (! $this->gasto || ! $this->gasto->tieneComprobante()) {
            return;
        }

        $this->authorize('view', $this->gasto);

        $extension = strtolower(pathinfo($this->gasto->comprobante_path, PATHINFO_EXTENSION));
        $esImagen = in_array($extension, ['jpg', 'jpeg', 'png'], true);

        $this->dispatch('abrir-visor-pdf',
            url: route('gastos.comprobante.preview', $this->gasto),
            titulo: "Comprobante #{$this->gasto->id} - {$this->gasto->descripcion}",
            descargaUrl: route('gastos.comprobante.descargar', $this->gasto),
            esImagen: $esImagen,
        );
    }

    public function editar(): void
    {
        if (! $this->gasto) {
            return;
        }

        $id = $this->gasto->id;
        Flux::modal('detalle-gasto')->close();
        $this->dispatch('abrir-form-gasto', id: $id);
    }
}; ?>

<flux:modal name="detalle-gasto" class="md:w-[600px]">
    @if ($gasto)
        <div class="space-y-5">
            <div class="flex items-start justify-between gap-3 pr-10">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ $gasto->descripcion }}</flux:heading>
                    <flux:subheading>{{ $gasto->fecha->format('d/m/Y') }}</flux:subheading>
                </div>
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                    Q {{ number_format((float) $gasto->monto, 2) }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Categoría</span>
                    <p class="mt-0.5">
                        <flux:badge size="sm" color="zinc">{{ $gasto->categoria->nombre }}</flux:badge>
                    </p>
                </div>

                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Método de pago</span>
                    <p class="mt-0.5">
                        @if ($gasto->metodo_pago === 'efectivo')
                            <flux:badge size="sm" color="green">Efectivo</flux:badge>
                        @else
                            <flux:badge size="sm" color="violet">Cuenta</flux:badge>
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Propiedad</span>
                    <p class="mt-0.5 font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $gasto->propiedad?->nombre ?? 'General (sin propiedad)' }}
                    </p>
                </div>

                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Cuarto</span>
                    <p class="mt-0.5 font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $gasto->cuarto?->codigo ?? '—' }}
                    </p>
                </div>

                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Proveedor</span>
                    <p class="mt-0.5 font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $gasto->proveedor ?: '—' }}
                    </p>
                </div>

                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Registrado por</span>
                    <p class="mt-0.5 font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $gasto->userRegistro?->name ?? 'Sistema' }}
                    </p>
                </div>
            </div>

            {{-- Notas --}}
            <div>
                <span class="text-zinc-500 text-xs uppercase tracking-wide">Notas</span>
                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line">
                    {{ $gasto->notas ?: 'Sin notas.' }}
                </p>
            </div>

            {{-- Comprobante --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm">
                    <flux:icon.paper-clip class="size-4 text-zinc-400" />
                    <span class="text-zinc-600 dark:text-zinc-400">Comprobante</span>
                </div>
                @if ($gasto->tieneComprobante())
                    <flux:button wire:click="verComprobante" size="sm" variant="ghost" icon="eye">
                        Ver
                    </flux:button>
                @else
                    <span class="text-zinc-400 text-sm">Sin comprobante</span>
                @endif
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cerrar</flux:button>
                </flux:modal.close>

                @can('update', $gasto)
                    <flux:button wire:click="editar" variant="primary" icon="pencil-square">
                        Editar
                    </flux:button>
                @endcan
            </div>
        </div>
    @endif
</flux:modal>
