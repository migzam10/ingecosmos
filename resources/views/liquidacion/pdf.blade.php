<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }
    .header { border-bottom: 3px solid #1a56db; padding-bottom: 10px; margin-bottom: 16px; }
    .header h1 { font-size: 20px; color: #1a56db; }
    .header .sub { font-size: 11px; color: #555; }
    .info-grid { display: table; width: 100%; margin-bottom: 14px; }
    .info-col { display: table-cell; width: 50%; vertical-align: top; }
    .label { color: #777; font-size: 10px; text-transform: uppercase; }
    .value { font-weight: bold; font-size: 12px; }
    h2 { font-size: 12px; background: #1a56db; color: white;
         padding: 4px 8px; margin: 12px 0 0 0; border-radius: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #f0f4ff; text-align: left; padding: 4px 6px;
         font-size: 10px; text-transform: uppercase; color: #555; }
    td { padding: 4px 6px; border-bottom: 1px solid #eee; }
    .text-right { text-align: right; }
    .totales { margin-top: 14px; }
    .totales table { width: 260px; float: right; }
    .totales td { padding: 3px 6px; }
    .total-final td { font-size: 14px; font-weight: bold; border-top: 2px solid #1a56db; padding-top: 6px; }
    .saldo-verde td { color: #2fb344; }
    .saldo-rojo  td { color: #d63939; }
    .firma { clear: both; margin-top: 50px; display: table; width: 100%; }
    .firma-col { display: table-cell; text-align: center; width: 50%; padding: 0 20px; }
    .firma-linea { border-top: 1px solid #333; padding-top: 4px; margin-top: 40px; font-size: 10px; color: #555; }
    .footer { margin-top: 20px; border-top: 1px solid #eee; padding-top: 6px;
              font-size: 9px; color: #999; text-align: center; }
    .badge-fin  { background:#d1fae5; color:#065f46; padding:1px 5px; border-radius:8px; font-size:9px; }
    .badge-proc { background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:8px; font-size:9px; }
</style>
</head>
<body>

<div class="header">
    <div style="display:table; width:100%">
        <div style="display:table-cell; vertical-align:middle;">
            <h1>INGECOSMOS — Taller Automotriz</h1>
            <div class="sub">Comprobante de Liquidación de Mano de Obra</div>
        </div>
        <div style="display:table-cell; text-align:right; vertical-align:middle;">
            <div style="font-size:13px; color:#555;">
                {{ $meses[$data['mes']] }} {{ $data['anio'] }}
            </div>
        </div>
    </div>
</div>

<div class="info-grid">
    <div class="info-col">
        <div style="margin-bottom:6px">
            <div class="label">Técnico</div>
            <div class="value" style="font-size:16px;">{{ $data['tecnico']->nombre }}</div>
        </div>
        <div>
            <div class="label">Especialidades</div>
            @php
            $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor',
                           'MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
            $esps = array_map(fn($e) => $nombresEsp[$e] ?? $e, $data['tecnico']->especialidades ?: []);
            @endphp
            <div class="value">{{ implode(', ', $esps) ?: '—' }}</div>
        </div>
    </div>
    <div class="info-col" style="text-align:right">
        <div><div class="label">Período</div>
        <div class="value">{{ $meses[$data['mes']] }} de {{ $data['anio'] }}</div></div>
        <div style="margin-top:4px"><div class="label">Fecha de emisión</div>
        <div class="value">{{ now()->format('d/m/Y') }}</div></div>
    </div>
</div>

{{-- Detalle de OTs --}}
<h2>Detalle de Órdenes Trabajadas</h2>
<table>
    <thead>
        <tr>
            <th style="width:60px"># Orden</th>
            <th>Placa</th>
            <th>Vehículo</th>
            <th>Función</th>
            <th>Estado</th>
            <th class="text-right" style="width:110px">Valor</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['trabajos'] as $t)
        <tr>
            <td>{{ $t->ot->numero_ot }}</td>
            <td>{{ $t->ot->vehiculo->placa }}</td>
            <td>{{ $t->ot->vehiculo->marca->nombre }}</td>
            <td>{{ $nombresEsp[$t->especialidad] ?? $t->especialidad }}</td>
            <td>
                @if($t->estado === 'FINALIZADO')
                <span class="badge-fin">Finalizado</span>
                @else
                <span class="badge-proc">En proceso</span>
                @endif
            </td>
            <td class="text-right">$ {{ number_format($t->valor_liquidar, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#999;">Sin órdenes en este período.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Avances --}}
@if($data['avances']->count())
<h2>Avances y Pagos Realizados</h2>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Concepto</th>
            <th>Fecha</th>
            <th class="text-right" style="width:110px">Monto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['avances'] as $pago)
        <tr>
            <td>{{ ['ABONO'=>'Abono','ANTICIPO'=>'Anticipo','PAGO_FINAL'=>'Pago final'][$pago->tipo] ?? $pago->tipo }}</td>
            <td>{{ $pago->concepto ?? '—' }}</td>
            <td>{{ $pago->created_at->format('d/m/Y') }}</td>
            <td class="text-right">$ {{ number_format($pago->monto, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Totales --}}
<div class="totales">
    <table>
        <tr>
            <td>Total mano de obra</td>
            <td class="text-right">$ {{ number_format($data['total_ganado'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Avances pagados</td>
            <td class="text-right">− $ {{ number_format($data['total_avances'], 0, ',', '.') }}</td>
        </tr>
        <tr class="total-final {{ $data['saldo'] <= 0 ? 'saldo-verde' : 'saldo-rojo' }}">
            <td>Saldo a pagar</td>
            <td class="text-right">$ {{ number_format($data['saldo'], 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

{{-- Firmas --}}
<div class="firma">
    <div class="firma-col">
        <div class="firma-linea">Firma del técnico<br>{{ $data['tecnico']->nombre }}</div>
    </div>
    <div class="firma-col">
        <div class="firma-linea">Firma del coordinador / administrador</div>
    </div>
</div>

<div class="footer" style="clear:both;">
    Documento generado el {{ now()->format('d/m/Y H:i') }} — INGECOSMOS Sistema de Gestión de Taller
</div>

</body>
</html>
