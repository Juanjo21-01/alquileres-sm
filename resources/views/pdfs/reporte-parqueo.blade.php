@php
    use Carbon\CarbonImmutable;
    use Illuminate\Support\Carbon;

    $estadoLabel = match ($estado) {
        'pagado' => 'Solo pagados',
        'pendiente' => 'Solo pendientes',
        default => 'Todos',
    };
    $rango = Carbon::parse($desde)->format('d/m/Y').' — '.Carbon::parse($hasta)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de parqueo</title>
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

        .vehiculos { margin: 4px 0 16px; padding: 8px 12px; background: #f0f5fa; border: 1px solid #dce6f0; border-radius: 6px; font-size: 9pt; }
        .vehiculos strong { color: #1F4E79; }

        table.datos { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.datos th { background: #1F4E79; color: #fff; padding: 7px 8px; text-align: left; font-size: 9pt; }
        table.datos th.num, table.datos td.num { text-align: right; }
        table.datos td { padding: 6px 8px; border-bottom: 1px solid #e4e4e7; }
        table.datos tr:nth-child(even) td { background: #f7f7f8; }
        .pos { color: #157347; }
        .warn { color: #b8860b; }
        tr.total td { border-top: 2px solid #1F4E79; border-bottom: none; font-weight: bold; font-size: 11pt; padding-top: 8px; background: #fff !important; }
        .footer { margin-top: 26px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 8pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Parqueo</h1>
        <div class="sub">Sistema de Gestión de Alquileres — San Marcos, Guatemala</div>
    </div>

    <table class="meta">
        <tr><td class="label">Periodo:</td><td>{{ $rango }}</td></tr>
        <tr><td class="label">Estado:</td><td>{{ $estadoLabel }}</td></tr>
    </table>

    <div class="vehiculos">
        Vehículos esperados en parqueo: <strong>{{ $vehiculos['total'] }}</strong>
        ({{ $vehiculos['inquilinos'] }} inquilinos con vehículo + {{ $vehiculos['externos'] }} arrendatarios externos activos)
    </div>

    <table class="datos">
        <thead>
            <tr>
                <th>Mes</th>
                <th class="num">Espacios</th>
                <th class="num">Cobrado</th>
                <th class="num">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($parqueo as $fila)
                <tr>
                    <td>{{ ucfirst(CarbonImmutable::createFromFormat('Y-m', $fila['periodo'])->translatedFormat('F Y')) }}</td>
                    <td class="num">{{ $fila['espacios'] }}</td>
                    <td class="num pos">Q {{ number_format($fila['cobrado'], 2) }}</td>
                    <td class="num warn">Q {{ number_format($fila['pendiente'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#999;padding:16px;">Sin datos en el rango seleccionado.</td></tr>
            @endforelse

            @if (! empty($parqueo) && $parqueo->isNotEmpty())
                <tr class="total">
                    <td>TOTAL</td>
                    <td class="num">{{ $totales['espacios'] }}</td>
                    <td class="num">Q {{ number_format($totales['cobrado'], 2) }}</td>
                    <td class="num">Q {{ number_format($totales['pendiente'], 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} · Ingreso de parqueo externo, independiente del flujo de caja principal.
    </div>
</body>
</html>
