<?php

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use App\Services\PagoService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $estanciaId = null;
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
        $this->fechaPago  = now()->toDateString();
        $this->descuento  = '0';
        $this->metodoPago = 'efectivo';

        if ($estanciaId) {
            $this->estanciaId = $estanciaId;
        }

        Flux::modal('form-pago')->show();
    }

    public function updatedTipoPagoId(): void
    {
        if (! $this->tipoRequiereMes) {
            $this->mesAplicado = '';
        }
    }

    public function updatedDescuento(): void
    {
        if ((float) $this->descuento === 0.0) {
            $this->motivoDescuento = '';
        }
    }

    public function updatedMetodoPago(): void
    {
        if ($this->metodoPago !== 'cuenta') {
            $this->referencia = '';
        }
    }

    public function guardar(PagoService $service): void
    {
        $this->authorize('create', Pago::class);
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
            $this->addError('montoBruto', $e->getMessage());
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
        <flux:select wire:model.live="estanciaId" label="Estancia activa" required>
            <flux:select.option value="">Seleccionar estancia...</flux:select.option>
            @foreach ($estancias as $estancia)
                <flux:select.option value="{{ $estancia->id }}">
                    {{ $estancia->inquilino->nombre_completo }} — {{ $estancia->cuarto->codigo }} ({{ $estancia->cuarto->propiedad->nombre }})
                </flux:select.option>
            @endforeach
        </flux:select>
        @error('estanciaId') <flux:error>{{ $message }}</flux:error> @enderror

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

        {{-- Mes aplicado — solo si el tipo lo requiere --}}
        @if ($this->tipoRequiereMes)
            <flux:input
                wire:model="mesAplicado"
                type="month"
                label="Mes aplicado"
                required />
            @error('mesAplicado') <flux:error>{{ $message }}</flux:error> @enderror
        @endif

        <div class="grid grid-cols-2 gap-3">
            {{-- Monto bruto --}}
            <flux:input
                wire:model.live.debounce.300ms="montoBruto"
                type="number"
                min="0.01"
                step="0.01"
                label="Monto bruto (Q)"
                required />
            @error('montoBruto') <flux:error>{{ $message }}</flux:error> @enderror

            {{-- Descuento --}}
            <flux:input
                wire:model.live.debounce.300ms="descuento"
                type="number"
                min="0"
                step="0.01"
                label="Descuento (Q)" />
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
