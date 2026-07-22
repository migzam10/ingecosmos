<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; padding: 0 42px; }
    /* dompdf ignora el margen superior/inferior de @page cuando hay <table>
       en el documento; se usa un div con altura fija (no margin/padding)
       para el espacio vertical, que sí respeta el flujo normal. */
    .v-spacer { height: 34px; }

    .info-grid { display: table; width: 100%; margin-bottom: 16px; }
    .info-col { display: table-cell; width: 50%; vertical-align: top; }
    .info-box { background: #f8f9fa; border: 1px solid #eef0f2; border-radius: 4px; padding: 10px 12px; }
    .label { color: #8a8f98; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
    .value { font-weight: bold; font-size: 12px; color: #1f2937; }

    h2 { font-size: 11.5px; background: #1a56db; color: white;
         padding: 5px 9px; margin: 14px 0 0 0; border-radius: 3px; letter-spacing: .3px; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.items th { background: #eef2ff; text-align: left; padding: 5px 7px;
         font-size: 9.5px; text-transform: uppercase; color: #4b5563; letter-spacing: .2px;
         border-bottom: 1px solid #dde2f0; }
    table.items td { padding: 5px 7px; border-bottom: 1px solid #f0f1f3; }
    table.items tbody tr:nth-child(even) td { background: #fbfbfc; }
    .text-right { text-align: right; }

    .totales-wrap { margin-top: 16px; clear: both; }
    .totales { width: 270px; float: right; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; }
    .totales table { width: 100%; border-collapse: collapse; }
    .totales td { padding: 5px 10px; }
    .total-final td { font-size: 14px; font-weight: bold; border-top: 2px solid #1a56db; padding-top: 7px; padding-bottom: 7px; }
    .saldo-verde td { color: #2fb344; background: #eafaf0; }
    .saldo-rojo  td { color: #d63939; background: #fdeeee; }

    .firma { clear: both; margin-top: 55px; display: table; width: 100%; }
    .firma-col { display: table-cell; text-align: center; width: 50%; padding: 0 24px; }
    .firma-linea { border-top: 1px solid #333; padding-top: 4px; margin-top: 40px; font-size: 10px; color: #555; }

    .generado { clear: both; margin-top: 18px; font-size: 9px; color: #9ca3af; }

    .badge-fin  { background:#d1fae5; color:#065f46; padding:2px 6px; border-radius:8px; font-size:9px; }
    .badge-proc { background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:8px; font-size:9px; }
</style>
</head>
<body>

<div class="v-spacer"></div>

@include('pdf._header', [
    'config' => $config,
    'titulo' => 'LIQUIDACIÓN DE MANO DE OBRA',
    'fecha'  => $meses[$data['mes']] . ' de ' . $data['anio'],
])

<div class="info-grid">
    <div class="info-col">
        <div class="info-box">
            <div class="label">Técnico</div>
            <div class="value" style="font-size:15px;">{{ $data['tecnico']->nombre }}</div>
            <div class="label" style="margin-top:6px;">Especialidades</div>
            @php
            $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor',
                           'MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
            $esps = array_map(fn($e) => $nombresEsp[$e] ?? $e, $data['tecnico']->especialidades ?: []);
            @endphp
            <div class="value">{{ implode(', ', $esps) ?: '—' }}</div>
        </div>
    </div>
    <div class="info-col" style="padding-left:14px;">
        <div class="info-box" style="text-align:right;">
            <div class="label">Fecha de emisión</div>
            <div class="value">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

{{-- Detalle de OTs --}}
<h2>Detalle de Órdenes Trabajadas</h2>
<table class="items">
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
<table class="items">
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
<div class="totales-wrap">
    <div class="totales">
        <table>
            <tr>
                <td>Total mano de obra</td>
                <td class="text-right">$ {{ number_format($data['total_ganado'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Avances pagados</td>
                <td class="text-right">- $ {{ number_format($data['total_avances'], 0, ',', '.') }}</td>
            </tr>
            <tr class="total-final {{ $data['saldo'] <= 0 ? 'saldo-verde' : 'saldo-rojo' }}">
                <td>Saldo a pagar</td>
                <td class="text-right">$ {{ number_format($data['saldo'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
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

<div class="generado">
    Documento generado el {{ now()->format('d/m/Y H:i') }}
</div>

@include('pdf._footer', ['config' => $config])

<div class="v-spacer"></div>

</body>
</html>
