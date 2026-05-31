<?php

use App\Models\Estancia;
use App\Models\Pago;
use App\Models\TipoPago;
use App\Services\EstanciaService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $estanciaId = null;

    public string $fechaFin = '';

    public string $motivo = '';

    protected function rules(): array
    {
        return [
            'fechaFin' => 'required|date',
            'motivo' => 'nullable|string|max:255',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['fechaFin' => 'fecha de fin'];
    }

    #[On('abrir-form-cerrar-estancia')]
    public function abrir(int $id): void
    {
        $estancia = Estancia::findOrFail($id);
        $this->authorize('update', $estancia);
        $this->reset();
        $this->resetValidation();
        $this->estanciaId = $estancia->id;
        $this->fechaFin = now()->toDateString();

        Flux::modal('form-cerrar-estancia')->show();
    }

    public function guardar(EstanciaService $service): void
    {
        $estancia = Estancia::findOrFail($this->estanciaId);
        $this->authorize('update', $estancia);
        $this->validate();

        $service->cerrar(
            estancia: $estancia,
            fechaFin: $this->fechaFin,
            motivo: $this->motivo ?: null,
        );

        Flux::modal('form-cerrar-estancia')->close();
        Flux::toast(text: 'Estancia cerrada. Cuarto disponible nuevamente.', variant: 'success');
        $this->dispatch('estancia-cerrada', id: $this->estanciaId);
    }

    public function cancelar(): void
    {
        Flux::modal('form-cerrar-estancia')->close();
    }

    #[Computed]
    public function mesesSinPagar(): array
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

        $mesesPagados = Pago::where('estancia_id', $estancia->id)
            ->whereHas('tipoPago', fn ($q) => $q->where('codigo', TipoPago::COD_MENSUALIDAD))
            ->pluck('mes_aplicado')
            ->map(fn ($m) => Carbon::parse($m)->startOfMonth()->toDateString())
            ->all();

        $pendientes = [];
        $cursor = $inicio->copy();

        while ($cursor->lte($hoy)) {
            if (! in_array($cursor->toDateString(), $mesesPagados)) {
                $pendientes[] = ucfirst($cursor->translatedFormat('F Y'));
            }
            $cursor = $cursor->addMonth();
        }

        return $pendientes;
    }
}; ?>

<flux:modal name="form-cerrar-estancia" class="md:w-[500px]">
    <form wire:submit="guardar" class="space-y-4">
        <div>
            <flux:heading size="lg">Cerrar estancia</flux:heading>
            <flux:subheading>El cuarto quedará disponible nuevamente.</flux:subheading>
        </div>

        <flux:input wire:model="fechaFin" label="Fecha de cierre" type="date" required />

        <flux:input
            wire:model="motivo"
            label="Motivo de cierre"
            placeholder="Ej. Fin de contrato, traslado..." />

        @if (count($this->mesesSinPagar) > 0)
            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-4 py-3 space-y-1.5">
                <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400 text-sm font-medium">
                    <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                    {{ count($this->mesesSinPagar) }} {{ count($this->mesesSinPagar) === 1 ? 'mes sin pagar' : 'meses sin pagar' }}
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($this->mesesSinPagar as $mes)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-800/40 text-amber-700 dark:text-amber-300">
                            {{ $mes }}
                        </span>
                    @endforeach
                </div>
                <p class="text-xs text-amber-600 dark:text-amber-400">Podrás registrar estos pagos después de cerrar la estancia.</p>
            </div>
        @endif

        <div class="flex gap-2 justify-end">
            <flux:button type="button" wire:click="cancelar" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="guardar">Cerrar estancia</span>
                <span wire:loading wire:target="guardar">Cerrando...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
