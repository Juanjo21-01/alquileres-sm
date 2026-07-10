@php
    use Illuminate\Support\Carbon;

    $rango = Carbon::parse($desde)->format('d/m/Y').' — '.Carbon::parse($hasta)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historial cuarto {{ $cuarto->codigo }}</title>
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #222; margin: 0; }
        .header { border-bottom: 2px solid #1F4E79; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { color: #1F4E79; margin: 0; font-size: 17pt; }
        .header .sub { color: #666; font-size: 9pt; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 12px; font-size: 9pt; }
        .meta td { padding: 2px 0; }
        .meta .label { color: #666; width: 22%; }

        .stats { width: 100%; border-collapse: collapse; margin: 8px 0 16px; }
        .stats td { width: 25%; text-align: center; border: 1px solid #dce6f0; background: #f0f5fa; padding: 8px; }
        .stats .val { font-size: 15pt; font-weight: bold; color: #1F4E79; }
        .stats .lbl { font-size: 8pt; color: #666; text-transform: uppercase; }

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
        <h1>Historial de Cuarto {{ $cuarto->codigo }}</h1>
        <div class="sub">{{ $cuarto->propiedad->nombre }} — San Marcos, Guatemala</div>
    </div>

    <table class="meta">
        <tr><td class="label">Periodo (estadísticas):</td><td>{{ $rango }}</td></tr>
        <tr><td class="label">Estado actual:</td><td>{{ App\Models\Cuarto::estados()[$cuarto->estado] ?? $cuarto->estado }}</td></tr>
        <tr><td class="label">Precio base:</td><td>Q {{ number_format((float) $cuarto->precio_base, 2) }}</td></tr>
    </table>

    <table class="stats">
        <tr>
            <td><div class="val">{{ $stats['rotacion'] }}</div><div class="lbl">Rotación</div></td>
            <td><div class="val">{{ $stats['dias_ocupado'] }}</div><div class="lbl">Días ocupado</div></td>
            <td><div class="val">{{ $stats['dias_periodo'] }}</div><div class="lbl">Días periodo</div></td>
            <td><div class="val">{{ $stats['porcentaje'] }}%</div><div class="lbl">Ocupación</div></td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Inquilino</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th class="num">Días</th>
                <th class="c">Estado</th>
                <th class="num">Precio acordado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($estancias as $e)
                <tr>
                    <td>{{ $e['inquilino'] ?? '—' }}</td>
                    <td>{{ $e['inicio']->format('d/m/Y') }}</td>
                    <td>{{ $e['fin']?->format('d/m/Y') ?? '—' }}</td>
                    <td class="num">{{ $e['dias'] }}</td>
                    <td class="c">{{ ucfirst($e['estado']) }}</td>
                    <td class="num">Q {{ number_format($e['precio'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#999;padding:16px;">Este cuarto no tiene estancias registradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} · Historial de ocupación del cuarto.
    </div>
</body>
</html>
