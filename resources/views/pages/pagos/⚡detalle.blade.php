<?php

use App\Models\Pago;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle de pago')] class extends Component {
    public Pago $pago;

    public function mount(Pago $pago): void
    {
        $this->authorize('view', $pago);
        $this->pago = $pago->load([
            'tipoPago',
            'estancia.inquilino',
            'estancia.cuarto.propiedad',
            'userRegistro',
        ]);
    }

    public function confirmarEliminar(): void
    {
        $this->dispatch('confirmar-eliminar-pago', id: $this->pago->id);
    }
}; ?>

<div class="space-y-6">

    {{-- Breadcrumb --}}
    <div>
        <flux:button href="{{ route('pagos.index') }}" variant="ghost" size="xs" icon="arrow-left">
            Pagos
        </flux:button>
    </div>

    {{-- Encabezado --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl">Pago {{ $pago->recibo_numero ?? "#{$pago->id}" }}</flux:heading>
            <flux:subheading>
                {{ $pago->estancia->inquilino->nombre_completo }} —
                {{ $pago->estancia->cuarto->codigo }},
                {{ $pago->estancia->cuarto->propiedad->nombre }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            {{-- PDF: placeholder hasta Fase 5 --}}
            <flux:tooltip content="Descarga de PDF disponible en próxima versión">
                <flux:button variant="ghost" icon="arrow-down-tray" disabled>
                    Descargar PDF
                </flux:button>
            </flux:tooltip>

            @can('delete', $pago)
                <flux:button wire:click="confirmarEliminar" variant="danger" icon="trash">
                    Eliminar
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- ── Columna izquierda: datos del pago ── --}}
        <flux:card class="space-y-4">
            <flux:heading size="sm">Datos del pago</flux:heading>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500">Tipo de pago</dt>
                    <dd class="font-medium">
                        <flux:badge color="blue">{{ $pago->tipoPago->nombre }}</flux:badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Fecha de pago</dt>
                    <dd class="font-medium">{{ $pago->fecha_pago->format('d/m/Y') }}</dd>
                </div>

                @if ($pago->mes_aplicado)
                    <div class="col-span-2">
                        <dt class="text-zinc-500">Mes aplicado</dt>
                        <dd class="font-medium">
                            {{ $pago->mes_aplicado->translatedFormat('F Y') }}
                        </dd>
                    </div>
                @endif

                <div>
                    <dt class="text-zinc-500">Monto bruto</dt>
                    <dd class="font-medium">Q {{ number_format((float) $pago->monto_bruto, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Descuento</dt>
                    <dd class="font-medium">
                        @if ((float) $pago->descuento > 0)
                            <span class="text-amber-600">
                                -Q {{ number_format((float) $pago->descuento, 2) }}
                            </span>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </dd>
                </div>

                @if ($pago->motivo_descuento)
                    <div class="col-span-2">
                        <dt class="text-zinc-500">Motivo del descuento</dt>
                        <dd class="font-medium">{{ $pago->motivo_descuento }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-zinc-500">Monto neto</dt>
                    <dd class="text-lg font-bold">Q {{ number_format((float) $pago->monto_neto, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Método de pago</dt>
                    <dd class="font-medium">
                        @if ($pago->metodo_pago === 'efectivo')
                            <flux:badge color="green">Efectivo</flux:badge>
                        @else
                            <flux:badge color="violet">Cuenta / Transferencia</flux:badge>
                        @endif
                    </dd>
                </div>

                @if ($pago->referencia)
                    <div class="col-span-2">
                        <dt class="text-zinc-500">Referencia</dt>
                        <dd class="font-mono text-sm">{{ $pago->referencia }}</dd>
                    </div>
                @endif

                @if ($pago->notas)
                    <div class="col-span-2">
                        <dt class="text-zinc-500">Notas</dt>
                        <dd class="font-medium">{{ $pago->notas }}</dd>
                    </div>
                @endif
            </dl>

            <flux:separator />

            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500">Estancia</dt>
                    <dd>
                        <flux:button
                            href="{{ route('estancias.detalle', $pago->estancia) }}"
                            variant="ghost"
                            size="xs">
                            Ver estancia #{{ $pago->estancia->id }}
                        </flux:button>
                    </dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Registrado por</dt>
                    <dd class="font-medium">{{ $pago->userRegistro?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Fecha de registro</dt>
                    <dd class="font-medium">{{ $pago->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </flux:card>

        {{-- ── Columna derecha: previsualización del recibo ── --}}
        <div>
            <p class="text-xs text-zinc-400 mb-2 text-center uppercase tracking-wide">
                Vista previa del recibo
            </p>

            {{-- Recibo (siempre fondo blanco, texto oscuro — es un documento) --}}
            <div class="bg-white text-zinc-900 border border-zinc-200 rounded-xl shadow-md p-6 font-sans text-sm space-y-4 max-w-sm mx-auto">

                {{-- Encabezado del recibo --}}
                <div class="text-center space-y-0.5 border-b border-zinc-200 pb-4">
                    <p class="text-xs text-zinc-400 uppercase tracking-widest">Alquileres</p>
                    <h2 class="text-xl font-bold text-zinc-900">San Marcos</h2>
                    <p class="text-xs text-zinc-500">San Marcos, Guatemala</p>
                </div>

                {{-- N° recibo y fecha --}}
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Recibo N°</p>
                        <p class="font-bold font-mono text-base">{{ $pago->recibo_numero ?? "#{$pago->id}" }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Fecha</p>
                        <p class="font-semibold">{{ $pago->fecha_pago->format('d/m/Y') }}</p>
                    </div>
                </div>

                <div class="border-t border-dashed border-zinc-200 pt-3 space-y-1">
                    <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Recibido de</p>
                    <p class="font-semibold text-base">
                        {{ $pago->estancia->inquilino->nombre_completo }}
                    </p>
                    @if ($pago->estancia->inquilino->dpi)
                        <p class="text-xs text-zinc-500">DPI: {{ $pago->estancia->inquilino->dpi }}</p>
                    @endif
                    @if ($pago->estancia->inquilino->telefono)
                        <p class="text-xs text-zinc-500">Tel: {{ $pago->estancia->inquilino->telefono }}</p>
                    @endif
                </div>

                <div class="border-t border-dashed border-zinc-200 pt-3 space-y-1">
                    <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Concepto</p>
                    <p class="font-medium">{{ $pago->tipoPago->nombre }}</p>
                    @if ($pago->mes_aplicado)
                        <p class="text-xs text-zinc-500">
                            Mes: {{ $pago->mes_aplicado->translatedFormat('F Y') }}
                        </p>
                    @endif
                    <p class="text-xs text-zinc-500">
                        Cuarto {{ $pago->estancia->cuarto->codigo }} —
                        {{ $pago->estancia->cuarto->propiedad->nombre }}
                    </p>
                </div>

                {{-- Desglose de montos --}}
                <div class="border-t border-dashed border-zinc-200 pt-3 space-y-1.5">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">Monto bruto</span>
                        <span>Q {{ number_format((float) $pago->monto_bruto, 2) }}</span>
                    </div>
                    @if ((float) $pago->descuento > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500">
                                Descuento
                                @if ($pago->motivo_descuento)
                                    <span class="italic">({{ $pago->motivo_descuento }})</span>
                                @endif
                            </span>
                            <span class="text-amber-600">-Q {{ number_format((float) $pago->descuento, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-base border-t border-zinc-200 pt-1.5 mt-1.5">
                        <span>TOTAL</span>
                        <span>Q {{ number_format((float) $pago->monto_neto, 2) }}</span>
                    </div>
                </div>

                {{-- Método --}}
                <div class="border-t border-dashed border-zinc-200 pt-3 space-y-1 text-xs text-zinc-500">
                    <div class="flex justify-between">
                        <span>Forma de pago</span>
                        <span class="font-medium text-zinc-700">
                            {{ $pago->metodo_pago === 'efectivo' ? 'Efectivo' : 'Transferencia / Cuenta' }}
                        </span>
                    </div>
                    @if ($pago->referencia)
                        <div class="flex justify-between">
                            <span>Referencia</span>
                            <span class="font-mono text-zinc-700">{{ $pago->referencia }}</span>
                        </div>
                    @endif
                </div>

                @if ($pago->notas)
                    <div class="border-t border-dashed border-zinc-200 pt-3 text-xs text-zinc-500">
                        <span class="uppercase tracking-wide">Notas:</span>
                        <p class="mt-0.5">{{ $pago->notas }}</p>
                    </div>
                @endif

                {{-- Firma --}}
                <div class="border-t border-zinc-200 pt-4 grid grid-cols-2 gap-4 text-center text-xs text-zinc-400">
                    <div>
                        <div class="border-b border-zinc-300 mb-1 h-8"></div>
                        <p>Firma del encargado</p>
                    </div>
                    <div>
                        <div class="border-b border-zinc-300 mb-1 h-8"></div>
                        <p>Firma del inquilino</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="text-center text-xs text-zinc-400 border-t border-dashed border-zinc-200 pt-3">
                    <p>Registrado por: {{ $pago->userRegistro?->name ?? 'Sistema' }}</p>
                    <p>{{ $pago->created_at->format('d/m/Y H:i') }}</p>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal eliminar --}}
    <livewire:pagos.modal-eliminar />
</div>
