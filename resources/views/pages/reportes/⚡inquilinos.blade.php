<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reporte de inquilinos')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Inquilinos</flux:heading>
        <flux:subheading>Actividad de estancias por inquilino. Entra al detalle para ver su historial completo.</flux:subheading>
    </div>

    <livewire:reportes.tabla-inquilinos />
</div>
