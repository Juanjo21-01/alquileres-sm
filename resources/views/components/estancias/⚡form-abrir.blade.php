<?php

use App\Models\Cuarto;
use App\Models\Estancia;
use App\Models\Inquilino;
use App\Services\EstanciaService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $inquilinoId = null;

    public ?int $cuartoId = null;

    public string $fechaInicio = '';

    public string $fechaFinEstimada = '';

    public string $precioAcordado = '';

    public string $anticipo = '0';

    public string $notas = '';

    public array $extras = [];

    protected function rules(): array
    {
        return [
            'inquilinoId' => 'required|integer|exists:inquilinos,id',
            'cuartoId' => 'required|integer|exists:cuartos,id',
            'fechaInicio' => 'required|date',
            'fechaFinEstimada' => 'nullable|date|after_or_equal:fechaInicio',
            'precioAcordado' => 'required|numeric|min:0',
            'anticipo' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
            'extras' => 'array',
            'extras.*.descripcion' => 'required|string|max:150',
            'extras.*.monto' => 'required|numeric|min:0',
            'extras.*.periodicidad' => 'required|in:unico,mensual',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'inquilinoId' => 'inquilino',
            'cuartoId' => 'cuarto',
            'fechaInicio' => 'fecha de inicio',
            'precioAcordado' => 'precio acordado',
        ];
    }

    #[On('abrir-form-estancia')]
    public function abrir(?int $inquilinoId = null): void
    {
        $this->authorize('create', Estancia::class);
        $this->reset();
        $this->resetValidation();
        $this->anticipo = '0';
        $this->fechaInicio = now()->toDateString();

        if ($inquilinoId) {
            $this->inquilinoId = $inquilinoId;
        }

        Flux::modal('form-abrir-estancia')->show();
    }

    public function guardar(EstanciaService $service): void
    {
        $this->authorize('create', Estancia::class);
        $this->validate();

        try {
            $estancia = $service->abrir(
                datos: [
                    'inquilino_id' => $this->inquilinoId,
                    'cuarto_id' => $this->cuartoId,
                    'fecha_inicio' => $this->fechaInicio,
                    'fecha_fin_estimada' => $this->fechaFinEstimada ?: null,
                    'precio_acordado' => $this->precioAcordado,
                    'anticipo' => $this->anticipo ?: 0,
                    'notas' => $this->notas ?: null,
                ],
                extras: $this->extras,
                userId: auth()->id(),
            );

            session()->flash('toast_success', "Estancia #{$estancia->id} abierta correctamente.");
            $this->redirect(route('estancias.detalle', $estancia));
        } catch (RuntimeException $e) {
            $this->addError('cuartoId', $e->getMessage());
        }
    }

    public function cancelar(): void
    {
        Flux::modal('form-abrir-estancia')->close();
    }

    public function with(): array
    {
        $cuartos = Cuarto::with('propiedad')
            ->where('estado', Cuarto::ESTADO_DISPONIBLE)
            ->where('activo', true)
            ->whereHas('propiedad', fn ($q) => $q->where('activo', true))
            ->orderBy('propiedad_id')
            ->orderBy('codigo')
            ->get()
            ->groupBy('propiedad.nombre');

        $inquilinos = Inquilino::where('activo', true)
            ->whereDoesntHave('estanciaActiva')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return compact('cuartos', 'inquilinos');
    }
}; ?>

<flux:modal name="form-abrir-estancia" class="md:w-170">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">Abrir estancia</flux:heading>
            <flux:subheading>Asigna un cuarto disponible a un inquilino.</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:select wire:model="inquilinoId" label="Inquilino" required>
                <flux:select.option value="">Seleccionar...</flux:select.option>
                @foreach ($inquilinos as $inquilino)
                    <flux:select.option value="{{ $inquilino->id }}">
                        {{ $inquilino->nombre_completo }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div>
                <flux:label>Cuarto disponible</flux:label>
                @if ($cuartos->isEmpty())
                    <div class="mt-1 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.exclamation-triangle class="size-4 shrink-0 text-amber-500" />
                        No hay cuartos disponibles en este momento.
                    </div>
                @else
                    <flux:select wire:model="cuartoId" required>
                        <flux:select.option value="">Seleccionar...</flux:select.option>
                        @foreach ($cuartos as $propiedad => $listaCuartos)
                            <flux:select.option disabled>── {{ $propiedad }} ──</flux:select.option>
                            @foreach ($listaCuartos as $cuarto)
                                <flux:select.option value="{{ $cuarto->id }}">
                                    {{ $cuarto->codigo }} — Q {{ number_format((float) $cuarto->precio_base, 2) }}
                                </flux:select.option>
                            @endforeach
                        @endforeach
                    </flux:select>
                    @error('cuartoId') <flux:error>{{ $message }}</flux:error> @enderror
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="fechaInicio" label="Fecha de inicio" type="date" required />
            <flux:input wire:model="fechaFinEstimada" label="Fecha fin estimada" type="date" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <flux:input wire:model="precioAcordado" label="Precio acordado (Q)" type="number" min="0" step="0.01" required />
            <flux:input wire:model="anticipo" label="Anticipo (Q)" type="number" min="0" step="0.01" />
        </div>

        <flux:textarea wire:model="notas" label="Notas" rows="2" />

        <flux:separator />

        <livewire:estancias.repeater-extras wire:model="extras" />

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Abrir estancia</span>
                <span wire:loading wire:target="guardar">Abriendo...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
