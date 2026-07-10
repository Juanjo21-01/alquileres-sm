<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reporte de parqueo')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Parqueo</flux:heading>
        <flux:subheading>Ingresos de parqueo externo por mes y vehículos esperados.</flux:subheading>
    </div>

    <livewire:reportes.tabla-parqueo />
</div>
