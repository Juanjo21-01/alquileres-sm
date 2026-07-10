@php
    use Illuminate\Support\Carbon;

    $ocupacionLabel = match ($ocupacion) {
        'estudiante' => 'Estudiantes',
        'salud' => 'Personal de salud',
        'otro' => 'Otros',
        default => 'Todas las ocupaciones',
    };
    $rango = Carbon::parse($desde)->format('d/m/Y').' — '.Carbon::parse($hasta)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de inquilinos</title>
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
        <h1>Reporte de Inquilinos</h1>
        <div class="sub">Sistema de Gestión de Alquileres — San Marcos, Guatemala</div>
    </div>

    <table class="meta">
        <tr><td class="label">Periodo:</td><td>{{ $rango }}</td></tr>
        <tr><td class="label">Ocupación:</td><td>{{ $ocupacionLabel }}</td></tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Inquilino</th>
                <th>Ocupación</th>
                <th class="c">Estancias</th>
                <th class="c">Cuartos</th>
                <th class="num">Días ocupado</th>
                <th>Cuarto actual</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $r['nombre'] }}{{ $r['activo'] ? '' : ' (inactivo)' }}</td>
                    <td>{{ ucfirst($r['ocupacion']) }}</td>
                    <td class="c">{{ $r['estancias'] }}</td>
                    <td class="c">{{ $r['cuartos'] }}</td>
                    <td class="num">{{ $r['dias_ocupado'] }}</td>
                    <td>{{ $r['cuarto_actual'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#999;padding:16px;">Sin inquilinos con actividad en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} · Actividad de estancias por inquilino.
    </div>
</body>
</html>
