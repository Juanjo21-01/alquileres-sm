<?php

use App\Models\Estancia;
use App\Models\Pago;
use Livewire\Attributes\Reactive;
use Livewire\Component;

new class extends Component {
    #[Reactive]
    public ?int $estanciaId = null;

    public function with(): array
    {
        if (!$this->estanciaId) {
            return ['estancia' => null, 'totalMensual' => 0, 'ultimoPago' => null];
        }

        $estancia = Estancia::with(['inquilino', 'cuarto.propiedad', 'extras'])->find($this->estanciaId);

        if (!$estancia) {
            return ['estancia' => null, 'totalMensual' => 0, 'ultimoPago' => null];
        }

        $totalMensual = (float) $estancia->precio_acordado + $estancia->extras->where('periodicidad', 'mensual')->sum(fn($e) => (float) $e->monto);

        $ultimoPago = Pago::where('estancia_id', $estancia->id)->with('tipoPago')->latest('fecha_pago')->first();

        return compact('estancia', 'totalMensual', 'ultimoPago');
    }
}; ?>

<div>
    @if (!$estancia)
        <div
            class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 py-16 flex flex-col items-center justify-center text-center text-zinc-400 gap-2">
            <flux:icon.home class="size-8 opacity-40" />
            <p class="text-sm">Selecciona una estancia para ver el resumen.</p>
        </div>
    @else
        <div class="space-y-4">

            {{-- Inquilino --}}
            <div
                class="rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
                <div class="px-4 py-3 flex items-center gap-2">
                    <flux:icon.user class="size-4 text-zinc-400 shrink-0" />
                    <span class="text-xs uppercase tracking-wide text-zinc-500 font-medium">Inquilino</span>
                </div>
                <div class="px-4 py-3 space-y-1">
                    <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                        {{ $estancia->inquilino->nombre_completo }}
                    </p>
                    @if ($estancia->inquilino->telefono)
                        <a href="https://wa.me/502{{ preg_replace('/\D/', '', $estancia->inquilino->telefono) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400 hover:underline">
                            {{ $estancia->inquilino->telefono }}
                            <flux:icon.chat-bubble-left-ellipsis class="size-3.5" />
                        </a>
                    @endif
                    <p class="text-sm text-zinc-500 capitalize">{{ $estancia->inquilino->ocupacion }}</p>
                </div>
            </div>

            {{-- Cuarto y fechas --}}
            <div
                class="rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
                <div class="px-4 py-3 flex items-center gap-2">
                    <flux:icon.building-office-2 class="size-4 text-zinc-400 shrink-0" />
                    <span class="text-xs uppercase tracking-wide text-zinc-500 font-medium">Cuarto</span>
                </div>
                <div class="px-4 py-3">
                    <p class="font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $estancia->cuarto->propiedad->nombre }} — {{ $estancia->cuarto->codigo }}
                    </p>
                </div>
                <div class="px-4 py-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div>
                        <p class="text-zinc-500 text-xs">Inicio</p>
                        <p class="font-medium">{{ $estancia->fecha_inicio->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-500 text-xs">Fin estimada</p>
                        <p class="font-medium">{{ $estancia->fecha_fin_estimada?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Resumen financiero --}}
            <div
                class="rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
                <div class="px-4 py-3 flex items-center gap-2">
                    <flux:icon.banknotes class="size-4 text-zinc-400 shrink-0" />
                    <span class="text-xs uppercase tracking-wide text-zinc-500 font-medium">Resumen financiero</span>
                </div>
                <div class="px-4 py-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Renta mensual</span>
                        <span class="font-medium">Q {{ number_format((float) $estancia->precio_acordado, 2) }}</span>
                    </div>
                    @foreach ($estancia->extras->where('periodicidad', 'mensual') as $extra)
                        <div class="flex justify-between text-zinc-500">
                            <span class="pl-2">+ {{ $extra->descripcion }}</span>
                            <span>Q {{ number_format((float) $extra->monto, 2) }}</span>
                        </div>
                    @endforeach
                    <div
                        class="flex justify-between font-bold text-zinc-800 dark:text-zinc-100 border-t border-zinc-100 dark:border-zinc-700 pt-2 mt-1">
                        <span>Total mensual</span>
                        <span class="text-green-700 dark:text-green-400">Q {{ number_format($totalMensual, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-500">
                        <span>Depósito</span>
                        <span>Q {{ number_format((float) $estancia->deposito, 2) }}</span>
                    </div>
                    @if ($estancia->extras->where('periodicidad', 'unica')->isNotEmpty())
                        <div class="pt-1 border-t border-zinc-100 dark:border-zinc-700">
                            <p class="text-xs text-zinc-400 mb-1">Extras únicos</p>
                            @foreach ($estancia->extras->where('periodicidad', 'unica') as $extra)
                                <div class="flex justify-between text-zinc-500">
                                    <span class="pl-2">{{ $extra->descripcion }}</span>
                                    <span>Q {{ number_format((float) $extra->monto, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Último pago --}}
            <div
                class="rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
                <div class="px-4 py-3 flex items-center gap-2">
                    <flux:icon.clock class="size-4 text-zinc-400 shrink-0" />
                    <span class="text-xs uppercase tracking-wide text-zinc-500 font-medium">Último pago</span>
                </div>
                <div class="px-4 py-3 text-sm">
                    @if ($ultimoPago)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">{{ $ultimoPago->tipoPago->nombre }}</p>
                                <p class="text-zinc-500">{{ $ultimoPago->fecha_pago->format('d/m/Y') }}</p>
                            </div>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-100">
                                Q {{ number_format((float) $ultimoPago->monto_neto, 2) }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-400 mt-1 font-mono">{{ $ultimoPago->recibo_numero }}</p>
                    @else
                        <p class="text-zinc-400">Sin pagos registrados.</p>
                    @endif
                </div>
            </div>

        </div>
    @endif
</div>
