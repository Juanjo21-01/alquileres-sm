<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reporte de ocupación')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Ocupación de cuartos" subtitle="Tiempo de ocupación por cuarto y propiedad. Entra al detalle de un cuarto para ver su historial completo." />

    <livewire:reportes.tabla-ocupacion />
</div>
