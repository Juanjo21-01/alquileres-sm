<?php

namespace App\Services;

use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReciboPdfService
{
    /**
     * Relaciones necesarias para renderizar el recibo.
     *
     * @var list<string>
     */
    private const RELACIONES = [
        'tipoPago',
        'estancia.inquilino',
        'estancia.cuarto.propiedad',
        'userRegistro',
    ];

    /**
     * Genera el PDF al vuelo y lo retorna como respuesta de descarga.
     *
     * No persiste el archivo: los datos del pago son inmutables, así que
     * cada descarga reconstruye un PDF idéntico.
     */
    public function descargar(Pago $pago): Response
    {
        return $this->construir($pago)->download("{$this->nombreArchivo($pago)}.pdf");
    }

    /**
     * Genera el PDF y lo sirve inline (preview en navegador).
     */
    public function stream(Pago $pago): Response
    {
        return $this->construir($pago)->stream("{$this->nombreArchivo($pago)}.pdf");
    }

    private function construir(Pago $pago): \Barryvdh\DomPDF\PDF
    {
        $pago->loadMissing(self::RELACIONES);

        return Pdf::loadView('pdfs.recibo', ['pago' => $pago])
            ->setPaper('letter', 'portrait');
    }

    private function nombreArchivo(Pago $pago): string
    {
        return $pago->recibo_numero ?? "recibo-{$pago->id}";
    }
}
