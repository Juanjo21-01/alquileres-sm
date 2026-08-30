<?php

use App\Models\AlquilerParqueo;
use App\Models\ArrendatarioParqueo;
use App\Services\AlquilerParqueoService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $arrendatarioParqueoId = null;
    public string $mes = '';
    public string $monto = '';
    public bool $pagado = false;
    public string $fechaPago = '';
    public string $metodoPago = 'efectivo';
    public string $notas = '';

    protected function rules(): array
    {
        return [
            'arrendatarioParqueoId' => 'required|exists:arrendatarios_parqueo,id',
            'mes'                   => 'required|date',
            'monto'                 => 'required|numeric|min:0.01',
            'pagado'                => 'boolean',
            'fechaPago'             => 'nullable|date|required_if:pagado,true',
            'metodoPago'            => 'required|in:efectivo,cuenta',
            'notas'                 => 'nullable|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'arrendatarioParqueoId' => 'arrendatario',
            'fechaPago'             => 'fecha de pago',
            'metodoPago'            => 'método de pago',
        ];
    }

    #[On('abrir-form-mes')]
    public function abrir(?int $arrendatarioParqueoId = null): void
    {
        $this->authorize('create', AlquilerParqueo::class);
        $this->reset();
        $this->resetValidation();
        $this->metodoPago = 'efectivo';
        $this->fechaPago  = now()->toDateString();

        if ($arrendatarioParqueoId) {
            $this->arrendatarioParqueoId = $arrendatarioParqueoId;
        }

        // Default mes = primer día del mes actual
        $this->mes = now()->startOfMonth()->format('Y-m-d');

        Flux::modal('form-mes')->show();
    }

    public function updatedPagado(): void
    {
        if (! $this->pagado) {
            $this->fechaPago = '';
        } else {
            $this->fechaPago = now()->toDateString();
        }
    }

    public function guardar(AlquilerParqueoService $service): void
    {
        $this->authorize('create', AlquilerParqueo::class);
        $this->validate();

        try {
            $service->registrarMes(
                datos: [
                    'arrendatario_parqueo_id' => $this->arrendatarioParqueoId,
                    'mes'                     => $this->mes,
                    'monto'                   => $this->monto,
                    'pagado'                  => $this->pagado,
                    'fecha_pago'              => $this->fechaPago ?: null,
                    'metodo_pago'             => $this->metodoPago,
                    'notas'                   => $this->notas ?: null,
                ],
                userId: auth()->id(),
            );

            Flux::modal('form-mes')->close();
            Flux::toast(text: 'Mes registrado correctamente.', variant: 'success');
            $this->dispatch('mes-registrado');
        } catch (RuntimeException $e) {
            $this->addError('monto', $e->getMessage());
        }
    }

    public function cancelar(): void
    {
        Flux::modal('form-mes')->close();
    }

    public function with(): array
    {
        $arrendatarios = ArrendatarioParqueo::activos()->orderBy('nombre_completo')->get();

        return compact('arrendatarios');
    }
}; ?>

<flux:modal name="form-mes" class="md:w-130">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">Registrar mes de parqueo</flux:heading>
            <flux:subheading>Registra el pago mensual de un espacio de parqueo.</flux:subheading>
        </div>

        {{-- Arrendatario (selector solo si no viene fijo desde el contexto) --}}
        @if (! $arrendatarioParqueoId)
            <flux:select wire:model="arrendatarioParqueoId" label="Arrendatario" required>
                <flux:select.option value="">Seleccionar...</flux:select.option>
                @foreach ($arrendatarios as $a)
                    <flux:select.option value="{{ $a->id }}">
                        {{ $a->nombre_completo }}
                        @if ($a->placa) ({{ $a->placa }}) @endif
                    </flux:select.option>
                @endforeach
            </flux:select>
            @error('arrendatarioParqueoId') <flux:error>{{ $message }}</flux:error> @enderror
        @endif

        <div class="grid grid-cols-2 gap-3">
            <flux:input
                wire:model="mes"
                type="month"
                label="Mes"
                required />
            @error('mes') <flux:error>{{ $message }}</flux:error> @enderror

            <div>
                <flux:input
                    wire:model="monto"
                    type="number"
                    min="0.01"
                    step="0.01"
                    label="Monto (Q)"
                    placeholder="Ej. 100.00"
                    required />
                @error('monto') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        <flux:switch wire:model.live="pagado" label="Marcar como pagado" />

        @if ($pagado)
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:input
                        wire:model="fechaPago"
                        type="date"
                        label="Fecha de pago"
                        required />
                    @error('fechaPago') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <flux:select wire:model="metodoPago" label="Método de pago">
                    <flux:select.option value="efectivo">Efectivo</flux:select.option>
                    <flux:select.option value="cuenta">Transferencia / Cuenta</flux:select.option>
                </flux:select>
            </div>
        @endif

        <flux:textarea wire:model="notas" label="Notas" rows="2"
            placeholder="Detalles del vehículo, observaciones..." />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="outline">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Registrar</span>
                <span wire:loading wire:target="guardar">Registrando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
