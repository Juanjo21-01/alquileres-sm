<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Flujo de caja')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Flujo de caja" subtitle="Ingresos, egresos y ganancia por mes. No incluye parqueo externo." />

    <livewire:reportes.tabla-flujo />
</div>
