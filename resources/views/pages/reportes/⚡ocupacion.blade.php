<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reporte de ocupación')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Ocupación de cuartos</flux:heading>
        <flux:subheading>Tiempo de ocupación por cuarto y propiedad. Entra al detalle de un cuarto para ver su historial completo.</flux:subheading>
    </div>

    <livewire:reportes.tabla-ocupacion />
</div>
