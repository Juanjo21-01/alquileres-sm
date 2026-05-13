<?php

use App\Models\ExtraEstancia;
use Livewire\Attributes\Modelable;
use Livewire\Component;

new class extends Component
{
    #[Modelable]
    public array $items = [];

    public function agregar(): void
    {
        $this->items[] = [
            'descripcion' => '',
            'monto' => '',
            'periodicidad' => ExtraEstancia::PERIODICIDAD_MENSUAL,
        ];
    }

    public function quitar(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }
}; ?>

<div class="space-y-3">
    <div class="flex items-center justify-between">
        <flux:heading size="sm">Extras / servicios adicionales</flux:heading>
        <flux:button type="button" wire:click="agregar" size="sm" variant="ghost" icon="plus">
            Agregar extra
        </flux:button>
    </div>

    @forelse ($items as $i => $item)
        <div class="grid grid-cols-12 gap-2 items-end" wire:key="extra-{{ $i }}">
            <div class="col-span-5">
                <flux:input
                    wire:model="items.{{ $i }}.descripcion"
                    label="{{ $i === 0 ? 'Descripción' : '' }}"
                    placeholder="Ej. agua, electricidad..." />
            </div>
            <div class="col-span-3">
                <flux:input
                    wire:model="items.{{ $i }}.monto"
                    label="{{ $i === 0 ? 'Monto (Q)' : '' }}"
                    type="number"
                    min="0"
                    step="0.01" />
            </div>
            <div class="col-span-3">
                <flux:select
                    wire:model="items.{{ $i }}.periodicidad"
                    label="{{ $i === 0 ? 'Periodicidad' : '' }}">
                    <flux:select.option value="mensual">Mensual</flux:select.option>
                    <flux:select.option value="unico">Único</flux:select.option>
                </flux:select>
            </div>
            <div class="col-span-1 flex justify-end pb-1">
                <flux:button
                    type="button"
                    wire:click="quitar({{ $i }})"
                    size="sm"
                    variant="ghost"
                    icon="trash" />
            </div>
        </div>
    @empty
        <p class="text-sm text-zinc-400 italic">Sin extras. Agrega si hay servicios adicionales incluidos.</p>
    @endforelse
</div>
