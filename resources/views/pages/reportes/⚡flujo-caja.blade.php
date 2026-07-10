<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Flujo de caja')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Flujo de caja</flux:heading>
        <flux:subheading>Ingresos, egresos y ganancia por mes. No incluye parqueo externo.</flux:subheading>
    </div>

    <livewire:reportes.tabla-flujo />
</div>
