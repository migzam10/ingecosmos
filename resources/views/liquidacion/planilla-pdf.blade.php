<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { padding: 0; box-sizing: border-box; }
    @page { margin: 26px 24px 30px 24px; }
    body { font-family: Arial, sans-serif; font-size: 9px; color: #1a1a2e; }

    .titulo { text-align: center; font-size: 14px; font-weight: bold; color: #1a1a5e; letter-spacing: .5px; }
    .subtitulo { text-align: center; font-size: 10px; color: #333; margin-bottom: 8px; }

    table.planilla { width: 100%; border-collapse: collapse; }
    table.planilla th, table.planilla td { border: 1px solid #1a1a5e; padding: 3px 4px; }
    table.planilla thead th { background: #ececf5; color: #1a1a5e; font-size: 8px;
                              text-transform: uppercase; text-align: center; }
    table.planilla td { font-size: 8.5px; }
    .num { text-align: right; }
    .nombre { text-align: left; }
    table.planilla tbody tr:nth-child(even) td { background: #fbfbfc; }
    tfoot td { font-weight: bold; background: #ececf5 !important; color: #1a1a5e; }
    .grupo-ded { background: #dfe3f3; }
</style>
</head>
<body>
@php
    $tDev = 0; $tNeto = 0; $tSaldo = 0;
    $tDed = array_fill_keys(array_keys($deducciones), 0);
@endphp

<div class="titulo">CONTRATISTAS INDEPENDIENTES</div>
<div class="subtitulo">{{ $meses[$mes] }} de {{ $anio }}</div>

<table class="planilla">
    <thead>
        <tr>
            <th rowspan="2" style="width:16%">Empleado</th>
            <th rowspan="2">Devengado</th>
            <th colspan="{{ count($deducciones) }}" class="grupo-ded">Deducciones</th>
            <th rowspan="2">Neto entregado</th>
            <th rowspan="2">Saldo</th>
        </tr>
        <tr>
            @foreach($deducciones as $label)
            <th class="grupo-ded">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($resumen as $r)
        @php
            $tDev += $r['total_ganado']; $tNeto += $r['total_neto']; $tSaldo += $r['saldo'];
            foreach ($deducciones as $col => $lbl) { $tDed[$col] += $r['deducciones'][$col] ?? 0; }
        @endphp
        <tr>
            <td class="nombre">{{ $r['tecnico']->nombre }}</td>
            <td class="num">{{ number_format($r['total_ganado'], 0, ',', '.') }}</td>
            @foreach($deducciones as $col => $lbl)
            <td class="num">{{ ($r['deducciones'][$col] ?? 0) > 0 ? number_format($r['deducciones'][$col], 0, ',', '.') : '—' }}</td>
            @endforeach
            <td class="num">{{ $r['total_neto'] > 0 ? number_format($r['total_neto'], 0, ',', '.') : '—' }}</td>
            <td class="num">{{ number_format($r['saldo'], 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="nombre">TOTAL</td>
            <td class="num">{{ number_format($tDev, 0, ',', '.') }}</td>
            @foreach($deducciones as $col => $lbl)
            <td class="num">{{ number_format($tDed[$col], 0, ',', '.') }}</td>
            @endforeach
            <td class="num">{{ number_format($tNeto, 0, ',', '.') }}</td>
            <td class="num">{{ number_format($tSaldo, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:10px; font-size:8px; color:#9ca3af;">
    Generado el {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
