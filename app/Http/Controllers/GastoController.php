<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class GastoController extends Controller
{
    /**
     * Sirve el comprobante inline (para mostrar en visor).
     */
    public function previewComprobante(Gasto $gasto): Response
    {
        $this->authorize('view', $gasto);

        return $this->servir($gasto, inline: true);
    }

    /**
     * Sirve el comprobante como descarga.
     */
    public function descargarComprobante(Gasto $gasto): Response
    {
        $this->authorize('view', $gasto);

        return $this->servir($gasto, inline: false);
    }

    private function servir(Gasto $gasto, bool $inline): Response
    {
        if (! $gasto->comprobante_path || ! Storage::exists($gasto->comprobante_path)) {
            abort(404, 'Comprobante no encontrado.');
        }

        $contenido = Storage::get($gasto->comprobante_path);
        $mime = Storage::mimeType($gasto->comprobante_path);
        $extension = pathinfo($gasto->comprobante_path, PATHINFO_EXTENSION);
        $nombreDescarga = sprintf('comprobante-%d-%s.%s', $gasto->id, $gasto->fecha->format('Y-m-d'), $extension);

        $disposition = $inline ? 'inline' : 'attachment';

        return response($contenido, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => "{$disposition}; filename=\"{$nombreDescarga}\"",
        ]);
    }
}
