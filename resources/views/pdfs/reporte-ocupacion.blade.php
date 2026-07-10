@php
    use Illuminate\Support\Carbon;

    $rango = Carbon::parse($desde)->format('d/m/Y').' — '.Carbon::parse($hasta)->format('d/m/Y');
    $grupos = $rows->groupBy('propiedad');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de ocupación</title>
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

        .grupo { color: #1F4E79; font-weight: bold; font-size: 11pt; margin: 14px 0 4px; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.datos th { background: #1F4E79; color: #fff; padding: 6px 8px; text-align: left; font-size: 8.5pt; }
        table.datos th.num, table.datos td.num { text-align: right; }
        table.datos th.c, table.datos td.c { text-align: center; }
        table.datos td { padding: 5px 8px; border-bottom: 1px solid #e4e4e7; font-size: 9pt; }
        table.datos tr:nth-child(even) td { background: #f7f7f8; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 8pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Ocupación</h1>
        <div class="sub">Sistema de Gestión de Alquileres — San Marcos, Guatemala</div>
    </div>

    <table class="meta">
        <tr><td class="label">Periodo:</td><td>{{ $rango }} ({{ $diasPeriodo }} días)</td></tr>
        <tr><td class="label">Propiedad:</td><td>{{ $propiedadNombre }}</td></tr>
    </table>

    @forelse ($grupos as $propiedad => $cuartos)
        <div class="grupo">{{ $propiedad ?? 'Sin propiedad' }}</div>
        <table class="datos">
            <thead>
                <tr>
                    <th>Cuarto</th>
                    <th>Estado</th>
                    <th class="c">Rotación</th>
                    <th class="num">Días ocupado</th>
                    <th class="num">% Ocupación</th>
                    <th>Inquilino actual/último</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cuartos as $c)
                    <tr>
                        <td>{{ $c['codigo'] }}</td>
                        <td>{{ $c['estado_label'] }}</td>
                        <td class="c">{{ $c['rotacion'] }}</td>
                        <td class="num">{{ $c['dias_ocupado'] }}</td>
                        <td class="num">{{ $c['porcentaje'] }}%</td>
                        <td>{{ $c['inquilino'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p style="text-align:center;color:#999;padding:16px;">Sin cuartos para el filtro seleccionado.</p>
    @endforelse

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} · Estadística de tiempo de ocupación, no incluye montos.
    </div>
</body>
</html>
