<?php

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use App\Services\PagoService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $estanciaId = null;
    public bool $estanciaFijada = false;
    public ?int $tipoPagoId = null;
    public string $fechaPago = '';
    public string $mesAplicado = '';
    public string $montoBruto = '';
    public string $descuento = '0';
    public string $motivoDescuento = '';
    public string $metodoPago = 'efectivo';
    public string $referencia = '';
    public string $notas = '';

    protected function rules(): array
    {
        return [
            'estanciaId'      => 'required|exists:estancias,id',
            'tipoPagoId'      => 'required|exists:tipos_pago,id',
            'fechaPago'       => 'required|date',
            'mesAplicado'     => 'nullable|date',
            'montoBruto'      => 'required|numeric|min:0.01',
            'descuento'       => 'nullable|numeric|min:0',
            'motivoDescuento' => 'nullable|string|max:255',
            'metodoPago'      => 'required|in:efectivo,cuenta',
            'referencia'      => 'nullable|string|max:100',
            'notas'           => 'nullable|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'estanciaId'  => 'estancia',
            'tipoPagoId'  => 'tipo de pago',
            'fechaPago'   => 'fecha de pago',
            'mesAplicado' => 'mes aplicado',
            'montoBruto'  => 'monto bruto',
        ];
    }

    #[Computed]
    public function estanciaFija(): ?Estancia
    {
        return $this->estanciaId
            ? Estancia::with(['inquilino', 'cuarto.propiedad'])->find($this->estanciaId)
            : null;
    }

    #[Computed]
    public function montoNeto(): float
    {
        return max(0, round((float) $this->montoBruto - (float) $this->descuento, 2));
    }

    #[Computed]
    public function tipoRequiereMes(): bool
    {
        return $this->tipoPagoId
            ? (bool) TipoPago::find($this->tipoPagoId)?->requiere_mes
            : false;
    }

    #[On('abrir-form-pago')]
    public function abrir(?int $estanciaId = null): void
    {
        $this->authorize('create', Pago::class);
        $this->reset();
        $this->resetValidation();
        $this->fechaPago      = now()->toDateString();
        $this->descuento      = '0';
        $this->metodoPago     = 'efectivo';
        $this->estanciaFijada = false;

        if ($estanciaId) {
            $this->estanciaId     = $estanciaId;
            $this->estanciaFijada = true;
        }

        Flux::modal('form-pago')->show();
    }

    public function updatedTipoPagoId(): void
    {
        if (! $this->tipoRequiereMes) {
            $this->mesAplicado = '';
        }

        $this->montoBruto      = '';
        $this->descuento       = '0';
        $this->motivoDescuento = '';
    }

    public function updatedDescuento(): void
    {
        if ((float) $this->descuento === 0.0) {
            $this->motivoDescuento = '';
        }
    }

    public function updatedEstanciaId(): void
    {
        $this->mesAplicado     = '';
        $this->montoBruto      = '';
        $this->descuento       = '0';
        $this->motivoDescuento = '';
    }

    public function updatedMetodoPago(): void
    {
        if ($this->metodoPago !== 'cuenta') {
            $this->referencia = '';
        }
    }

    #[Computed]
    public function mesesDisponibles(): array
    {
        if (! $this->estanciaId) {
            return [];
        }

        $estancia = Estancia::find($this->estanciaId);
        if (! $estancia) {
            return [];
        }

        $inicio = $estancia->fecha_inicio->copy()->startOfMonth();
        $hoy = now()->startOfMonth();

        $finReal = $estancia->fecha_fin ?? $estancia->fecha_fin_estimada;
        $fin = $finReal
            ? $finReal->copy()->startOfMonth()
            : $hoy->copy()->addMonth();

        $mesesPagados = Pago::where('estancia_id', $estancia->id)
            ->whereHas('tipoPago', fn ($q) => $q->where('codigo', TipoPago::COD_MENSUALIDAD))
            ->pluck('mes_aplicado')
            ->map(fn ($m) => Carbon::parse($m)->startOfMonth()->toDateString())
            ->all();

        $meses = [];
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $valor = $cursor->toDateString();
            $pagado = in_array($valor, $mesesPagados);
            $esFuturo = $cursor->gt($hoy);

            $meses[] = [
                'valor'    => $valor,
                'etiqueta' => ucfirst($cursor->translatedFormat('F Y')),
                'pagado'   => $pagado,
                'esFuturo' => $esFuturo,
            ];

            $cursor = $cursor->addMonth();
        }

        return $meses;
    }

    public function seleccionarMes(string $mes): void
    {
        if (! $this->estanciaId) {
            return;
        }

        $mesCarbon = Carbon::parse($mes)->startOfMonth();

        if ($mesCarbon->gt(now()->startOfMonth())) {
            return;
        }

        $existe = Pago::where('estancia_id', $this->estanciaId)
            ->whereHas('tipoPago', fn ($q) => $q->where('codigo', TipoPago::COD_MENSUALIDAD))
            ->where('mes_aplicado', $mesCarbon->toDateString())
            ->exists();

        if ($existe) {
            Flux::toast(text: 'Este mes ya fue pagado.', variant: 'warning');

            return;
        }

        $this->mesAplicado = $mes;
        $this->autoFillMonto();
    }

    #[Computed]
    public function montoSugerido(): float
    {
        if (! $this->estanciaId) {
            return 0.0;
        }

        $estancia = Estancia::with('extras')->find($this->estanciaId);
        if (! $estancia) {
            return 0.0;
        }

        return (float) $estancia->precio_acordado
            + $estancia->extras->where('periodicidad', 'mensual')->sum(fn ($e) => (float) $e->monto);
    }

    protected function autoFillMonto(): void
    {
        if (! $this->estanciaId) {
            return;
        }

        $estancia = Estancia::with('extras')->find($this->estanciaId);
        if (! $estancia) {
            return;
        }

        $totalMensual = (float) $estancia->precio_acordado
            + $estancia->extras->where('periodicidad', 'mensual')->sum(fn ($e) => (float) $e->monto);

        $this->montoBruto = (string) $totalMensual;

        if ($this->mesAplicado) {
            $mesCarbon = Carbon::parse($this->mesAplicado)->startOfMonth();
            $primerMes = $estancia->fecha_inicio->startOfMonth();

            if ($mesCarbon->eq($primerMes) && (float) $estancia->anticipo > 0) {
                $this->descuento       = (string) (float) $estancia->anticipo;
                $this->motivoDescuento = 'Anticipo inicial de la estancia';
            } else {
                $this->descuento       = '0';
                $this->motivoDescuento = '';
            }
        }
    }

    public function restaurarMontoSugerido(): void
    {
        $this->autoFillMonto();
    }

    #[Computed]
    public function esPrimerMesConAnticipo(): bool
    {
        if (! $this->estanciaId || ! $this->mesAplicado) {
            return false;
        }

        $estancia = Estancia::find($this->estanciaId);
        if (! $estancia || (float) $estancia->anticipo <= 0) {
            return false;
        }

        return Carbon::parse($this->mesAplicado)->startOfMonth()
            ->eq($estancia->fecha_inicio->startOfMonth());
    }

    public function guardar(PagoService $service): void
    {
        $this->authorize('create', Pago::class);
        $this->resetValidation();
        $this->validate();

        try {
            $service->registrar(
                datos: [
                    'estancia_id'      => $this->estanciaId,
                    'tipo_pago_id'     => $this->tipoPagoId,
                    'fecha_pago'       => $this->fechaPago,
                    'mes_aplicado'     => $this->mesAplicado ?: null,
                    'monto_bruto'      => $this->montoBruto,
                    'descuento'        => $this->descuento ?: 0,
                    'motivo_descuento' => $this->motivoDescuento ?: null,
                    'metodo_pago'      => $this->metodoPago,
                    'referencia'       => $this->referencia ?: null,
                    'notas'            => $this->notas ?: null,
                ],
                userId: auth()->id(),
            );

            Flux::modal('form-pago')->close();
            Flux::toast(text: 'Pago registrado correctamente.', variant: 'success');
            $this->dispatch('pago-registrado');
        } catch (RuntimeException $e) {
            Flux::toast(text: $e->getMessage(), variant: 'danger');
        }
    }

    public function cancelar(): void
    {
        Flux::modal('form-pago')->close();
    }

    public function with(): array
    {
        $estancias = Estancia::with(['inquilino', 'cuarto.propiedad'])
            ->where('estado', Estancia::ESTADO_ACTIVA)
            ->orderBy('id')
            ->get();

        $tipos = TipoPago::activos()->orderBy('nombre')->get();

        return compact('estancias', 'tipos');
    }
}; ?>

<flux:modal name="form-pago" class="md:w-[680px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">Registrar pago</flux:heading>
            <flux:subheading>Asocia el pago a una estancia activa.</flux:subheading>
        </div>

        {{-- Estancia --}}
        @if ($estanciaFijada)
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-4 py-2.5 flex items-center gap-2 text-sm">
                <flux:icon.home class="size-4 text-zinc-400 shrink-0" />
                <div>
                    <span class="text-zinc-500 text-xs uppercase tracking-wide">Estancia</span>
                    <p class="font-medium text-zinc-800 dark:text-zinc-200">
                        {{ $this->estanciaFija->inquilino->nombre_completo }}
                        <span class="text-zinc-400">—</span>
                        {{ $this->estanciaFija->cuarto->codigo }}
                        <span class="text-zinc-400 text-xs">({{ $this->estanciaFija->cuarto->propiedad->nombre }})</span>
                    </p>
                </div>
            </div>
        @else
            <flux:select wire:model.live="estanciaId" label="Estancia activa" required>
                <flux:select.option value="">Seleccionar estancia...</flux:select.option>
                @foreach ($estancias as $estancia)
                    <flux:select.option value="{{ $estancia->id }}">
                        {{ $estancia->inquilino->nombre_completo }} — {{ $estancia->cuarto->codigo }} ({{ $estancia->cuarto->propiedad->nombre }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            @error('estanciaId') <flux:error>{{ $message }}</flux:error> @enderror
        @endif

        <div class="grid grid-cols-2 gap-3">
            {{-- Tipo de pago --}}
            <div>
                <flux:select wire:model.live="tipoPagoId" label="Tipo de pago" required>
                    <flux:select.option value="">Seleccionar tipo...</flux:select.option>
                    @foreach ($tipos as $tipo)
                        <flux:select.option value="{{ $tipo->id }}">{{ $tipo->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('tipoPagoId') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            {{-- Fecha de pago --}}
            <flux:input wire:model="fechaPago" type="date" label="Fecha de pago" required />
        </div>

        {{-- Mes aplicado — selector de botones --}}
        @if ($this->tipoRequiereMes)
            <div>
                <flux:label>Mes aplicado <span class="text-red-500 ml-0.5">*</span></flux:label>
                @if (! $this->estanciaId)
                    <p class="mt-2 text-sm text-zinc-400">Selecciona una estancia primero.</p>
                @elseif (count($this->mesesDisponibles) === 0)
                    <p class="mt-2 text-sm text-zinc-400">No hay meses disponibles.</p>
                @else
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->mesesDisponibles as $mes)
                            @php $isSelected = $this->mesAplicado === $mes['valor']; @endphp
                            <button
                                type="button"
                                @if (! $mes['esFuturo'])
                                    wire:click="seleccionarMes('{{ $mes['valor'] }}')"
                                @endif
                                @disabled($mes['esFuturo'])
                                @class([
                                    'inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors',
                                    'bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900 border-zinc-800 dark:border-zinc-100' => $isSelected,
                                    'opacity-40 cursor-not-allowed bg-zinc-100 dark:bg-zinc-800 text-zinc-400 border-zinc-200 dark:border-zinc-700' => ! $isSelected && $mes['esFuturo'],
                                    'line-through text-zinc-400 dark:text-zinc-500 bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 cursor-pointer' => ! $isSelected && ! $mes['esFuturo'] && $mes['pagado'],
                                    'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer' => ! $isSelected && ! $mes['esFuturo'] && ! $mes['pagado'],
                                ])
                            >
                                {{ $mes['etiqueta'] }}
                                @if ($mes['pagado'])
                                    <flux:icon.check class="size-3" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @if (! $this->mesAplicado)
                        <p class="mt-1.5 text-xs text-zinc-400">Selecciona el mes a pagar.</p>
                    @endif
                @endif
                @error('mesAplicado') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            {{-- Monto bruto --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <flux:label>Monto bruto (Q) <span class="text-red-500 ml-0.5">*</span></flux:label>
                    @if ($this->tipoRequiereMes && $this->mesAplicado && $this->montoSugerido > 0)
                        <button
                            type="button"
                            wire:click="restaurarMontoSugerido"
                            class="inline-flex items-center gap-1 text-xs text-blue-500 hover:text-blue-600 hover:underline">
                            <flux:icon.arrow-path class="size-3" />
                            Q {{ number_format($this->montoSugerido, 2) }}
                        </button>
                    @endif
                </div>
                <flux:input
                    wire:model.live.debounce.300ms="montoBruto"
                    type="number"
                    min="0.01"
                    step="0.01"
                    required />
                @error('montoBruto') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            {{-- Descuento --}}
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="descuento"
                    type="number"
                    min="0"
                    step="0.01"
                    label="Descuento (Q)" />
                @if ($this->esPrimerMesConAnticipo)
                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                        <flux:icon.information-circle class="size-3.5 shrink-0" />
                        Anticipo inicial aplicado automáticamente
                    </p>
                @endif
            </div>
        </div>

        {{-- Motivo descuento — solo si hay descuento --}}
        @if ((float) $descuento > 0)
            <flux:input
                wire:model="motivoDescuento"
                label="Motivo del descuento"
                placeholder="Ej. Pago adelantado, acuerdo especial..."
                required />
            @error('motivoDescuento') <flux:error>{{ $message }}</flux:error> @enderror
        @endif

        {{-- Monto neto calculado --}}
        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 px-4 py-3 flex items-center justify-between">
            <span class="text-sm text-zinc-600 dark:text-zinc-400">Monto neto a cobrar</span>
            <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                Q {{ number_format($this->montoNeto, 2) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            {{-- Método de pago --}}
            <div>
                <flux:select wire:model.live="metodoPago" label="Método de pago" required>
                    <flux:select.option value="efectivo">Efectivo</flux:select.option>
                    <flux:select.option value="cuenta">Transferencia / Cuenta</flux:select.option>
                </flux:select>
            </div>

            {{-- Referencia — solo si método = cuenta --}}
            @if ($metodoPago === 'cuenta')
                <flux:input
                    wire:model="referencia"
                    label="Referencia / N° transferencia"
                    placeholder="Ej. TRF-00123..." />
            @endif
        </div>

        <flux:textarea wire:model="notas" label="Notas" rows="2" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Registrar pago</span>
                <span wire:loading wire:target="guardar">Registrando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
