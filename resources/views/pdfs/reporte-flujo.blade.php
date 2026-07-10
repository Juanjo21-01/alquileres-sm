@php
    use Carbon\CarbonImmutable;
    use Illuminate\Support\Carbon;

    $vistaLabel = $vista === 'devengado' ? 'Devengado' : 'Flujo de caja';
    $rango = Carbon::parse($desde)->format('d/m/Y').' — '.Carbon::parse($hasta)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de flujo — {{ $vistaLabel }}</title>
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #222; margin: 0; }
        .header { border-bottom: 2px solid #1F4E79; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { color: #1F4E79; margin: 0; font-size: 17pt; }
        .header .sub { color: #666; font-size: 9pt; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 14px; font-size: 9pt; }
        .meta td { padding: 2px 0; }
        .meta .label { color: #666; width: 22%; }

        table.datos { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.datos th {
            background: #1F4E79; color: #fff; padding: 7px 8px; text-align: left; font-size: 9pt;
        }
        table.datos th.num, table.datos td.num { text-align: right; }
        table.datos td { padding: 6px 8px; border-bottom: 1px solid #e4e4e7; }
        table.datos tr:nth-child(even) td { background: #f7f7f8; }
        .pos { color: #157347; }
        .neg { color: #b02a37; }
        tr.total td {
            border-top: 2px solid #1F4E79; border-bottom: none;
            font-weight: bold; font-size: 11pt; padding-top: 8px; background: #fff !important;
        }
        .footer { margin-top: 26px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 8pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Flujo de Caja</h1>
        <div class="sub">Sistema de Gestión de Alquileres — San Marcos, Guatemala</div>
    </div>

    <table class="meta">
        <tr><td class="label">Vista:</td><td><strong>{{ $vistaLabel }}</strong></td></tr>
        <tr><td class="label">Periodo:</td><td>{{ $rango }}</td></tr>
        <tr>
            <td class="label">Base de cálculo:</td>
            <td>{{ $vista === 'devengado'
                ? 'Ingresos imputados al mes al que corresponden (mes aplicado).'
                : 'Ingresos por la fecha en que se cobraron (movimiento de caja).' }}</td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Mes</th>
                <th class="num">Ingresos</th>
                <th class="num">Egresos</th>
                <th class="num">Ganancia</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($flujo as $fila)
                <tr>
                    <td>{{ ucfirst(CarbonImmutable::createFromFormat('Y-m', $fila['periodo'])->translatedFormat('F Y')) }}</td>
                    <td class="num pos">Q {{ number_format($fila['ingresos'], 2) }}</td>
                    <td class="num neg">Q {{ number_format($fila['egresos'], 2) }}</td>
                    <td class="num {{ $fila['ganancia'] >= 0 ? 'pos' : 'neg' }}">Q {{ number_format($fila['ganancia'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#999;padding:16px;">Sin datos en el rango seleccionado.</td></tr>
            @endforelse

            @if (! empty($flujo) && $flujo->isNotEmpty())
                <tr class="total">
                    <td>TOTAL</td>
                    <td class="num">Q {{ number_format($totales['ingresos'], 2) }}</td>
                    <td class="num">Q {{ number_format($totales['egresos'], 2) }}</td>
                    <td class="num {{ $totales['ganancia'] >= 0 ? 'pos' : 'neg' }}">Q {{ number_format($totales['ganancia'], 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} · No incluye ingresos de parqueo externo.
    </div>
</body>
</html>
