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

    public string $motivo = '';

    protected function rules(): array
    {
        return [
            'motivo' => 'required|string|max:255',
        ];
    }

    #[On('confirmar-cancelar-estancia')]
    public function abrir(int $id): void
    {
        $estancia = Estancia::findOrFail($id);
        $this->authorize('cancelar', $estancia);
        $this->reset();
        $this->resetValidation();
        $this->estanciaId = $estancia->id;

        Flux::modal('cancelar-estancia')->show();
    }

    public function cancelarEstancia(EstanciaService $service): void
    {
        $estancia = Estancia::findOrFail($this->estanciaId);
        $this->authorize('cancelar', $estancia);
        $this->validate();

        $service->cancelar(estancia: $estancia, motivo: $this->motivo);

        Flux::modal('cancelar-estancia')->close();
        Flux::toast(text: 'Estancia cancelada.', variant: 'success');
        $this->dispatch('estancia-cancelada', id: $this->estanciaId);
    }

    public function cerrarModal(): void
    {
        Flux::modal('cancelar-estancia')->close();
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

        $inicio = $estancia->fecha_inicio->startOfMonth();
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

<flux:modal name="cancelar-estancia" class="md:w-[500px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Cancelar estancia</flux:heading>
            <flux:subheading>
                Esta acción cancela la estancia y deja el cuarto disponible. Requiere motivo.
            </flux:subheading>
        </div>

        @if (count($this->mesesSinPagar) > 0)
            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3 space-y-2">
                <div class="flex items-center gap-2 text-red-700 dark:text-red-400 text-sm font-medium">
                    <flux:icon.x-circle class="size-4 shrink-0" />
                    No se puede cancelar — {{ count($this->mesesSinPagar) }} {{ count($this->mesesSinPagar) === 1 ? 'mes sin pagar' : 'meses sin pagar' }}
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($this->mesesSinPagar as $mes)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-800/40 text-red-700 dark:text-red-300">
                            {{ $mes }}
                        </span>
                    @endforeach
                </div>
                <p class="text-xs text-red-600 dark:text-red-400">Registra todos los pagos pendientes antes de cancelar la estancia.</p>
            </div>
        @else
            <flux:input
                wire:model="motivo"
                label="Motivo de cancelación"
                placeholder="Describe el motivo..."
                required />

            @error('motivo')
                <flux:error>{{ $message }}</flux:error>
            @enderror
        @endif

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cerrar</flux:button>
            </flux:modal.close>
            <flux:button
                wire:click="cancelarEstancia"
                variant="danger"
                :disabled="count($this->mesesSinPagar) > 0">
                <span wire:loading.remove wire:target="cancelarEstancia">Cancelar estancia</span>
                <span wire:loading wire:target="cancelarEstancia">Cancelando...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>
