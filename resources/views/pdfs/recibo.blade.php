@php
    $logoPath = public_path('fondo.png');
    $logo = is_file($logoPath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
        : null;

    $bruto  = (float) $pago->monto_bruto;
    $desc   = (float) $pago->descuento;
    $neto   = (float) $pago->monto_neto;
    $esEfectivo = $pago->metodo_pago === 'efectivo';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo {{ $pago->recibo_numero ?? "#{$pago->id}" }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10pt;
            color: #27272a; /* zinc-800 */
            margin: 0;
            padding: 28px 32px;
        }
        .recibo {
            width: 460px;
            margin: 0 auto;
            border: 1px solid #e4e4e7; /* zinc-200 */
            border-radius: 10px;
            padding: 22px 24px;
        }
        .muted   { color: #a1a1aa; }   /* zinc-400 */
        .muted2  { color: #71717a; }   /* zinc-500 */
        .strong  { font-weight: bold; }
        .mono    { font-family: "DejaVu Sans Mono", monospace; }
        .up      { text-transform: uppercase; letter-spacing: .5px; font-size: 7.5pt; }
        .center  { text-align: center; }
        .right   { text-align: right; }
        .amber   { color: #d97706; }   /* amber-600 */

        .cab { text-align: center; border-bottom: 1px solid #e4e4e7; padding-bottom: 14px; margin-bottom: 12px; }
        .cab img { height: 64px; width: auto; }
        .cab .titulo { font-size: 15pt; font-weight: bold; color: #1f4e79; margin: 6px 0 0; }

        .seccion { border-top: 1px dashed #e4e4e7; padding-top: 10px; margin-top: 10px; }

        table.full { width: 100%; border-collapse: collapse; }
        table.full td { vertical-align: top; padding: 0; }

        .montos td { padding: 2px 0; }
        .total-row td {
            border-top: 1px solid #e4e4e7;
            padding-top: 6px;
            font-size: 12pt;
            font-weight: bold;
        }

        .firmas { margin-top: 20px; }
        .firmas td { width: 50%; text-align: center; padding: 0 10px; }
        .firma-linea { border-bottom: 1px solid #d4d4d8; height: 28px; margin-bottom: 4px; }

        .footer { text-align: center; border-top: 1px dashed #e4e4e7; padding-top: 10px; margin-top: 12px; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="recibo">

        {{-- Encabezado --}}
        <div class="cab">
            @if ($logo)
                <img src="{{ $logo }}" alt="Alquileres SM">
            @else
                <div class="titulo">Alquileres SM</div>
            @endif
            <div class="muted2" style="font-size: 8pt; margin-top: 4px;">San Marcos, Guatemala</div>
        </div>

        {{-- N° recibo y fecha --}}
        <table class="full">
            <tr>
                <td style="width: 55%;">
                    <div class="up muted">Recibo No.</div>
                    <div class="strong mono" style="font-size: 12pt;">{{ $pago->recibo_numero ?? "#{$pago->id}" }}</div>
                </td>
                <td class="right">
                    <div class="up muted">Fecha</div>
                    <div class="strong">{{ $pago->fecha_pago->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>

        {{-- Recibido de --}}
        <div class="seccion">
            <div class="up muted">Recibido de</div>
            <div class="strong" style="font-size: 11pt;">{{ $pago->estancia?->inquilino?->nombre_completo }}</div>
            @if ($pago->estancia?->inquilino?->dpi)
                <div class="muted2" style="font-size: 8pt;">DPI: {{ $pago->estancia->inquilino->dpi }}</div>
            @endif
            @if ($pago->estancia?->inquilino?->telefono)
                <div class="muted2" style="font-size: 8pt;">Tel: {{ $pago->estancia->inquilino->telefono }}</div>
            @endif
        </div>

        {{-- Concepto --}}
        <div class="seccion">
            <div class="up muted">Concepto</div>
            <div class="strong">{{ $pago->tipoPago->nombre }}</div>
            @if ($pago->mes_aplicado)
                <div class="muted2" style="font-size: 8pt;">Mes: {{ ucfirst($pago->mes_aplicado->translatedFormat('F Y')) }}</div>
            @endif
            <div class="muted2" style="font-size: 8pt;">
                Cuarto {{ $pago->estancia?->cuarto?->codigo }} — {{ $pago->estancia?->cuarto?->propiedad?->nombre }}
            </div>
        </div>

        {{-- Montos --}}
        <div class="seccion">
            <table class="full montos">
                <tr>
                    <td class="muted2">Monto bruto</td>
                    <td class="right">Q {{ number_format($bruto, 2) }}</td>
                </tr>
                @if ($desc > 0)
                    <tr>
                        <td class="muted2">
                            Descuento @if ($pago->motivo_descuento)<span style="font-style: italic;">({{ $pago->motivo_descuento }})</span>@endif
                        </td>
                        <td class="right amber">-Q {{ number_format($desc, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="right">Q {{ number_format($neto, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Método --}}
        <div class="seccion">
            <table class="full" style="font-size: 8.5pt;">
                <tr>
                    <td class="muted2">Forma de pago</td>
                    <td class="right strong">{{ $esEfectivo ? 'Efectivo' : 'Transferencia / Cuenta' }}</td>
                </tr>
                @if ($pago->referencia)
                    <tr>
                        <td class="muted2">Referencia</td>
                        <td class="right mono">{{ $pago->referencia }}</td>
                    </tr>
                @endif
            </table>
        </div>

        {{-- Notas --}}
        @if ($pago->notas)
            <div class="seccion" style="font-size: 8.5pt;">
                <span class="up muted">Notas:</span>
                <div class="muted2" style="margin-top: 2px;">{{ $pago->notas }}</div>
            </div>
        @endif

        {{-- Firmas --}}
        <table class="full firmas">
            <tr>
                <td>
                    <div class="firma-linea"></div>
                    <div class="muted" style="font-size: 8pt;">Firma del encargado</div>
                </td>
                <td>
                    <div class="firma-linea"></div>
                    <div class="muted" style="font-size: 8pt;">Firma del inquilino</div>
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="footer muted">
            <div>Registrado por: {{ $pago->userRegistro?->name ?? 'Sistema' }}</div>
            <div>{{ $pago->created_at->format('d/m/Y H:i') }}</div>
        </div>

    </div>
</body>
</html>
