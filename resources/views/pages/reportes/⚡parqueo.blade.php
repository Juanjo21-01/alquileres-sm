<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reporte de parqueo')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Parqueo" subtitle="Ingresos de parqueo externo por mes y vehículos esperados." />

    <livewire:reportes.tabla-parqueo />
</div>
