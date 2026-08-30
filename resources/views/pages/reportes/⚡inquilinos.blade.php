<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reporte de inquilinos')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Inquilinos" subtitle="Actividad de estancias por inquilino. Entra al detalle para ver su historial completo." />

    <livewire:reportes.tabla-inquilinos />
</div>
