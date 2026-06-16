<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }
    .header { border-bottom: 3px solid #1a56db; padding-bottom: 10px; margin-bottom: 16px; }
    .header h1 { font-size: 22px; color: #1a56db; letter-spacing: 1px; }
    .header .sub { font-size: 11px; color: #555; }
    .info-grid { display: table; width: 100%; margin-bottom: 14px; }
    .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
    .info-row { margin-bottom: 4px; }
    .label { color: #777; font-size: 10px; text-transform: uppercase; }
    .value { font-weight: bold; font-size: 11px; }
    h2 { font-size: 12px; background: #1a56db; color: white;
         padding: 4px 8px; margin: 10px 0 0 0; border-radius: 2px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    th { background: #f0f4ff; text-align: left; padding: 4px 6px;
         font-size: 10px; text-transform: uppercase; color: #555; }
    td { padding: 4px 6px; border-bottom: 1px solid #eee; }
    .text-right { text-align: right; }
    .subtotal-row td { background: #f8f9fa; font-weight: bold; border-top: 2px solid #dee2e6; }
    .resumen { margin-top: 14px; }
    .resumen table { width: 260px; float: right; }
    .resumen td { padding: 3px 6px; }
    .resumen .iva-row td { color: #555; }
    .resumen .subtotal-neto td { background: #f8f9fa; font-weight: bold; }
    .resumen .total-row td { font-size: 13px; font-weight: bold;
                              border-top: 2px solid #1a56db; padding-top: 5px; }
    .kpis { clear: both; margin-top: 10px; display: table; width: 100%; }
    .kpi { display: table-cell; text-align: center; padding: 6px;
           border: 1px solid #ddd; border-radius: 3px; margin: 2px; }
    .kpi-val { font-size: 18px; font-weight: bold; color: #1a56db; }
    .kpi-lab { font-size: 10px; color: #777; text-transform: uppercase; }
    .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 8px;
              font-size: 9px; color: #999; text-align: center; }
    .badge-leve   { background:#d1fae5; color:#065f46; padding:2px 6px; border-radius:10px; }
    .badge-medio  { background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:10px; }
    .badge-fuerte { background:#fee2e2; color:#991b1b; padding:2px 6px; border-radius:10px; }
    .badge-previa { background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:10px; font-size:10px; }
</style>
</head>
<body>

<div class="header">
    <div style="display:table; width:100%">
        <div style="display:table-cell; vertical-align:middle;">
            <h1>INGECOSMOS</h1>
            <div class="sub">Taller Automotriz — Cotización de Reparación</div>
        </div>
        <div style="display:table-cell; text-align:right; vertical-align:middle;">
            <div style="font-size:18px; font-weight:bold; color:#1a56db;">
                COT # {{ $cotizacion->numero_cot }}
                @if($cotizacion->es_previa)<span class="badge-previa" style="font-size:11px; margin-left:6px;">PREVIA</span>@endif
            </div>
            <div class="sub">{{ $cotizacion->created_at->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<div class="info-grid">
    @if($cotizacion->es_previa)
    <div class="info-col">
        <div class="info-row"><span class="label">Placa</span><br><span class="value">{{ $cotizacion->placa_previa ?? '—' }}</span></div>
        @if($cotizacion->marcaPrevia)
        <div class="info-row"><span class="label">Vehículo</span><br>
            <span class="value">{{ $cotizacion->marcaPrevia->nombre }}{{ $cotizacion->modeloPrevia ? ' ' . $cotizacion->modeloPrevia->nombre : '' }}</span>
        </div>
        @endif
    </div>
    <div class="info-col">
        @if($cotizacion->clientePrevia)
        <div class="info-row"><span class="label">Cliente</span><br>
            <span class="value">{{ $cotizacion->clientePrevia->nombre }}</span>
        </div>
        @if($cotizacion->clientePrevia->cedula)
        <div class="info-row"><span class="label">Cédula / NIT</span><br>
            <span class="value">{{ $cotizacion->clientePrevia->cedula }}</span>
        </div>
        @endif
        @if($cotizacion->clientePrevia->telefono)
        <div class="info-row"><span class="label">Teléfono</span><br>
            <span class="value">{{ $cotizacion->clientePrevia->telefono }}</span>
        </div>
        @endif
        @endif
        @if($cotizacion->descripcion_previa)
        <div class="info-row"><span class="label">Descripción</span><br>
            <span class="value">{{ $cotizacion->descripcion_previa }}</span>
        </div>
        @endif
    </div>
    @else
    <div class="info-col">
        <div class="info-row"><span class="label">OT #</span><br><span class="value">{{ $cotizacion->ot->numero_ot }}</span></div>
        <div class="info-row"><span class="label">Placa</span><br><span class="value">{{ $cotizacion->ot->vehiculo->placa }}</span></div>
        <div class="info-row"><span class="label">Vehículo</span><br>
            <span class="value">{{ $cotizacion->ot->vehiculo->marca->nombre }} {{ $cotizacion->ot->vehiculo->modelo?->nombre }}</span>
        </div>
    </div>
    <div class="info-col">
        <div class="info-row"><span class="label">CIA / Empresa</span><br>
            <span class="value">{{ $cotizacion->ot->empresaCliente->nombre }}</span>
        </div>
        @if($cotizacion->ot->clientePersona)
        <div class="info-row"><span class="label">Propietario</span><br>
            <span class="value">{{ $cotizacion->ot->clientePersona->nombre }}</span>
        </div>
        @endif
        @if($cotizacion->ot->referencia_forc)
        <div class="info-row"><span class="label">FORC</span><br>
            <span class="value">{{ $cotizacion->ot->referencia_forc }}</span>
        </div>
        @endif
    </div>
    @endif
</div>

{{-- MANO DE OBRA --}}
@if($cotizacion->itemsMo->count())
<h2>Mano de Obra</h2>
<table>
    <thead>
        <tr><th>Descripción</th><th class="text-right" style="width:130px">Precio</th></tr>
    </thead>
    <tbody>
        @foreach($cotizacion->itemsMo as $item)
        <tr>
            <td>{{ $item->descripcion }}</td>
            <td class="text-right">$ {{ number_format($item->precio, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <td class="text-right">Subtotal Mano de Obra</td>
            <td class="text-right">$ {{ number_format($cotizacion->subtotal_mo, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- REPUESTOS --}}
@if($cotizacion->itemsRepuesto->count())
<h2>Repuestos</h2>
<table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="text-right" style="width:60px">Und</th>
            <th class="text-right" style="width:110px">P. Unitario</th>
            <th class="text-right" style="width:130px">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->itemsRepuesto as $item)
        <tr>
            <td>{{ $item->descripcion }}</td>
            <td class="text-right">{{ number_format($item->unidades, 2) }}</td>
            <td class="text-right">$ {{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-right">$ {{ number_format($item->precio_total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <td colspan="3" class="text-right">Subtotal Repuestos</td>
            <td class="text-right">$ {{ number_format($cotizacion->subtotal_rto, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- INSUMOS (legado) --}}
@if($cotizacion->itemsSuministro->count())
<h2>Insumos de Pintura</h2>
<table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="text-right" style="width:110px">Costo</th>
            <th class="text-right" style="width:130px">Precio al cliente</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->itemsSuministro as $item)
        <tr>
            <td>{{ $item->descripcion }}</td>
            <td class="text-right">$ {{ number_format($item->costo, 0, ',', '.') }}</td>
            <td class="text-right">$ {{ number_format($item->precio, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <td colspan="2" class="text-right">Subtotal Insumos</td>
            <td class="text-right">$ {{ number_format($cotizacion->subtotal_suministros, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- RESUMEN --}}
@php
    $subtotalNeto = $cotizacion->subtotal_mo + $cotizacion->subtotal_rto + $cotizacion->subtotal_suministros
                  + $cotizacion->subtotal_terceros + $cotizacion->subtotal_op;
    $ivaVal = $cotizacion->iva_valor ?? 0;
@endphp
<div class="resumen">
    <table>
        @if($cotizacion->subtotal_mo > 0)
        <tr><td>Mano de Obra</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_mo, 0, ',', '.') }}</td></tr>
        @endif
        @if($cotizacion->subtotal_rto > 0)
        <tr><td>Repuestos</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_rto, 0, ',', '.') }}</td></tr>
        @endif
        @if($cotizacion->subtotal_suministros > 0)
        <tr><td>Insumos Pintura</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_suministros, 0, ',', '.') }}</td></tr>
        @endif
        @if($cotizacion->subtotal_terceros > 0)
        <tr><td>Subcontratados</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_terceros, 0, ',', '.') }}</td></tr>
        @endif
        @if($cotizacion->subtotal_op > 0)
        <tr><td>Otros</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_op, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="subtotal-neto"><td>Subtotal neto</td><td class="text-right">$ {{ number_format($subtotalNeto, 0, ',', '.') }}</td></tr>
        @if($ivaVal > 0)
        <tr class="iva-row">
            <td>IVA{{ $cotizacion->iva_porcentaje > 0 ? ' ' . number_format($cotizacion->iva_porcentaje, 0) . '%' : '' }}</td>
            <td class="text-right">$ {{ number_format($ivaVal, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td><strong>TOTAL</strong></td>
            <td class="text-right"><strong>$ {{ number_format($cotizacion->total, 0, ',', '.') }}</strong></td>
        </tr>
    </table>
</div>

{{-- KPIs (solo cotizaciones con OT) --}}
@if(!$cotizacion->es_previa && $cotizacion->ot && $cotizacion->ot->ha)
<div class="kpis" style="margin-top:20px; clear:both;">
    <table style="width:auto; float:left; border-collapse:separate; border-spacing:4px;">
        <tr>
            <td style="border:1px solid #ddd; padding:6px 12px; text-align:center; border-radius:3px;">
                <div class="kpi-val">{{ number_format($cotizacion->ot->ha, 1) }}</div>
                <div class="kpi-lab">HA</div>
            </td>
            <td style="border:1px solid #ddd; padding:6px 12px; text-align:center; border-radius:3px;">
                <div class="kpi-val">{{ $cotizacion->ot->dr }}</div>
                <div class="kpi-lab">DR (días)</div>
            </td>
            <td style="border:1px solid #ddd; padding:6px 12px; text-align:center; border-radius:3px;">
                @php $tg = $cotizacion->ot->tg; @endphp
                <div class="kpi-val">
                    <span class="badge-{{ strtolower($tg) }}">{{ $tg }}</span>
                </div>
                <div class="kpi-lab">TG</div>
            </td>
            @if($cotizacion->ot->salida_estimada)
            <td style="border:1px solid #ddd; padding:6px 12px; text-align:center; border-radius:3px;">
                <div class="kpi-val" style="font-size:13px;">{{ $cotizacion->ot->salida_estimada->format('d/m/Y') }}</div>
                <div class="kpi-lab">Salida Estimada</div>
            </td>
            @endif
        </tr>
    </table>
</div>
@endif

@if($cotizacion->observaciones)
<div style="clear:both; margin-top:14px; padding:8px; background:#f8f9fa; border-radius:3px; font-size:10px;">
    <strong>Observaciones:</strong> {{ $cotizacion->observaciones }}
</div>
@endif

<div class="footer" style="clear:both;">
    Cotización generada por {{ $cotizacion->creadaPor->name }} el {{ $cotizacion->created_at->format('d/m/Y H:i') }}
    — INGECOSMOS Sistema de Gestión de Taller
</div>

</body>
</html>
