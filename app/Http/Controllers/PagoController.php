<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\ReciboPdfService;
use Symfony\Component\HttpFoundation\Response;

class PagoController extends Controller
{
    /**
     * Genera y descarga el recibo del pago en PDF (on-demand, sin persistir).
     */
    public function descargarRecibo(Pago $pago, ReciboPdfService $service): Response
    {
        $this->authorize('view', $pago);

        return $service->descargar($pago);
    }
}
