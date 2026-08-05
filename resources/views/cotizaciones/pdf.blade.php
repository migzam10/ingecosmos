<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    /* OJO: el reset NO debe incluir `margin:0`, porque en dompdf el `*`
       también alcanza a html/body y anularía el margen de @page. Con solo
       padding/box-sizing, @page aplica margen en las 4 caras y en todas las páginas. */
    * { padding: 0; box-sizing: border-box; }
    @page { margin: 40px 42px 46px 42px; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }

    .info-grid { display: table; width: 100%; margin-bottom: 16px; }
    .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 14px; }
    .info-box { background: #f8f9fa; border: 1px solid #eef0f2; border-radius: 4px; padding: 10px 12px; }
    .info-row { margin-bottom: 5px; }
    .info-row:last-child { margin-bottom: 0; }
    .label { color: #8a8f98; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
    .value { font-weight: bold; font-size: 11.5px; color: #1f2937; }

    h2 { font-size: 11.5px; background: #040065; color: white;
         padding: 5px 9px; margin: 14px 0 0 0; border-radius: 3px; letter-spacing: .3px; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.items th { background: #ececf5; text-align: left; padding: 5px 7px;
         font-size: 9.5px; text-transform: uppercase; color: #4b5563; letter-spacing: .2px;
         border-bottom: 1px solid #dcdae8; }
    table.items td { padding: 5px 7px; border-bottom: 1px solid #f0f1f3; }
    table.items tbody tr:nth-child(even) td { background: #fbfbfc; }
    .text-right { text-align: right; }
    .subtotal-row td { background: #f0f0f7 !important; font-weight: bold; border-top: 1.5px solid #c9c7dd; }

    .resumen-wrap { margin-top: 16px; clear: both; }
    .resumen { width: 270px; float: right; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; }
    .resumen table { width: 100%; border-collapse: collapse; }
    .resumen td { padding: 5px 10px; }
    .resumen .iva-row td { color: #6b7280; border-top: 1px solid #f0f1f3; }
    .resumen .subtotal-neto td { background: #f8f9fa; font-weight: bold; border-top: 1px solid #f0f1f3; }
    .resumen .total-row td { font-size: 14px; font-weight: bold; color: #040065;
                              background: #ececf5; border-top: 2px solid #040065; padding-top: 7px; padding-bottom: 7px; }

    .kpis { clear: both; margin-top: 14px; }
    .kpi-item { border: 1px solid #e5e7eb; padding: 7px 14px; text-align: center; border-radius: 4px; }
    .kpi-val { font-size: 17px; font-weight: bold; color: #040065; }
    .kpi-lab { font-size: 9px; color: #8a8f98; text-transform: uppercase; letter-spacing: .2px; }

    .observaciones { clear: both; margin-top: 16px; padding: 9px 12px; background: #f8f9fa;
                     border-left: 3px solid #040065; border-radius: 3px; font-size: 10px; color: #374151; }

    .generado { clear: both; margin-top: 18px; font-size: 9px; color: #9ca3af; }

    .badge-leve   { background:#d1fae5; color:#065f46; padding:2px 7px; border-radius:10px; }
    .badge-medio  { background:#fef3c7; color:#92400e; padding:2px 7px; border-radius:10px; }
    .badge-fuerte { background:#fee2e2; color:#991b1b; padding:2px 7px; border-radius:10px; }
</style>
</head>
<body>


@php
    $titulo = $cotizacion->es_previa ? 'COTIZACIÓN PREVIA' : 'COTIZACIÓN';
    $fechaCot = $cotizacion->fecha_cotizacion ?? $cotizacion->created_at;
    $km = $cotizacion->kilometraje();
@endphp
@include('pdf._header', [
    'config'  => $config,
    'titulo'  => $titulo,
    'numero'  => $cotizacion->numero_cot,
    'fecha'   => $fechaCot->format('d/m/Y'),
    'color'   => '#040065',
    'logoMax' => 92,
])

<div class="info-grid">
    @if($cotizacion->es_previa)
    <div class="info-col">
        <div class="info-box">
            <div class="info-row"><span class="label">Placa</span><br><span class="value">{{ $cotizacion->placa_previa ?? '—' }}</span></div>
            @if($cotizacion->marcaPrevia)
            <div class="info-row"><span class="label">Vehículo</span><br>
                <span class="value">{{ $cotizacion->marcaPrevia->nombre }}{{ $cotizacion->modeloPrevia ? ' ' . $cotizacion->modeloPrevia->nombre : '' }}</span>
            </div>
            @endif
            @if($km)
            <div class="info-row"><span class="label">Kilometraje</span><br>
                <span class="value">{{ number_format($km, 0, ',', '.') }} km</span>
            </div>
            @endif
        </div>
    </div>
    <div class="info-col">
        <div class="info-box">
            @if($cotizacion->clientePrevia)
            <div class="info-row"><span class="label">Cliente</span><br>
                <span class="value">{{ $cotizacion->clientePrevia->nombre }}</span>
            </div>
            @endif
            @if($cotizacion->clientePrevia?->cedula)
            <div class="info-row"><span class="label">Cédula / NIT</span><br>
                <span class="value">{{ $cotizacion->clientePrevia->cedula }}</span>
            </div>
            @endif
            @if($cotizacion->clientePrevia?->telefono)
            <div class="info-row"><span class="label">Teléfono</span><br>
                <span class="value">{{ $cotizacion->clientePrevia->telefono }}</span>
            </div>
            @endif
            @if($cotizacion->descripcion_previa)
            <div class="info-row"><span class="label">Descripción</span><br>
                <span class="value">{{ $cotizacion->descripcion_previa }}</span>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="info-col">
        <div class="info-box">
            <div class="info-row"><span class="label">OT #</span><br><span class="value">{{ $cotizacion->ot->numero_ot }}</span></div>
            <div class="info-row"><span class="label">Placa</span><br><span class="value">{{ $cotizacion->ot->vehiculo->placa }}</span></div>
            <div class="info-row"><span class="label">Vehículo</span><br>
                <span class="value">{{ $cotizacion->ot->vehiculo->marca->nombre }} {{ $cotizacion->ot->vehiculo->modelo?->nombre }}</span>
            </div>
            @if($km)
            <div class="info-row"><span class="label">Kilometraje</span><br>
                <span class="value">{{ number_format($km, 0, ',', '.') }} km</span>
            </div>
            @endif
        </div>
    </div>
    <div class="info-col">
        <div class="info-box">
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
    </div>
    @endif
</div>

{{-- MANO DE OBRA --}}
@if($cotizacion->itemsMo->count())
<h2>Mano de Obra</h2>
<table class="items">
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
<table class="items">
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

{{-- INSUMOS DE PINTURA (Fase 3) --}}
@if($cotizacion->itemsInsumo->count())
<h2>Insumos</h2>
<table class="items">
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="text-right" style="width:60px">Cant.</th>
            <th style="width:60px">Unidad</th>
            <th class="text-right" style="width:110px">P. Venta</th>
            <th class="text-right" style="width:130px">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->itemsInsumo as $item)
        <tr>
            <td>{{ $item->descripcion }}</td>
            <td class="text-right">{{ number_format($item->cantidad_solicitada, 2) }}</td>
            <td>{{ $item->insumo?->unidad_medida }}</td>
            <td class="text-right">$ {{ number_format($item->precio_venta, 0, ',', '.') }}</td>
            <td class="text-right">$ {{ number_format($item->precio_total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="subtotal-row">
            <td colspan="4" class="text-right">Subtotal Insumos</td>
            <td class="text-right">$ {{ number_format($cotizacion->subtotal_insumos, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- INSUMOS (legado) --}}
@if($cotizacion->itemsSuministro->count())
<h2>Insumos</h2>
<table class="items">
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
    $subtotalNeto = $cotizacion->subtotal_mo + $cotizacion->subtotal_rto
                  + $cotizacion->subtotal_insumos + $cotizacion->subtotal_suministros
                  + $cotizacion->subtotal_terceros + $cotizacion->subtotal_op;
    $descVal   = $cotizacion->descuento_valor ?? 0;
    $baseIva   = max(0, $subtotalNeto - $descVal);
    $ivaVal    = $cotizacion->iva_valor ?? 0;
@endphp
<div class="resumen-wrap">
    <div class="resumen">
        <table>
            @if($cotizacion->subtotal_mo > 0)
            <tr><td>Mano de Obra</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_mo, 0, ',', '.') }}</td></tr>
            @endif
            @if($cotizacion->subtotal_rto > 0)
            <tr><td>Repuestos</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_rto, 0, ',', '.') }}</td></tr>
            @endif
            @if($cotizacion->subtotal_insumos > 0)
            <tr><td>Insumos</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_insumos, 0, ',', '.') }}</td></tr>
            @endif
            @if($cotizacion->subtotal_suministros > 0)
            <tr><td>Insumos (legado)</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_suministros, 0, ',', '.') }}</td></tr>
            @endif
            @if($cotizacion->subtotal_terceros > 0)
            <tr><td>Subcontratados</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_terceros, 0, ',', '.') }}</td></tr>
            @endif
            @if($cotizacion->subtotal_op > 0)
            <tr><td>Otros</td><td class="text-right">$ {{ number_format($cotizacion->subtotal_op, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="subtotal-neto"><td>Subtotal neto</td><td class="text-right">$ {{ number_format($subtotalNeto, 0, ',', '.') }}</td></tr>
            @if($descVal > 0)
            <tr class="iva-row">
                <td>Descuento{{ $cotizacion->descuento_porcentaje > 0 ? ' ' . number_format($cotizacion->descuento_porcentaje, 0) . '%' : '' }}</td>
                <td class="text-right">- $ {{ number_format($descVal, 0, ',', '.') }}</td>
            </tr>
            <tr class="subtotal-neto"><td>Base gravable</td><td class="text-right">$ {{ number_format($baseIva, 0, ',', '.') }}</td></tr>
            @endif
            @if($ivaVal > 0)
            <tr class="iva-row">
                <td>IVA{{ $cotizacion->iva_porcentaje > 0 ? ' ' . number_format($cotizacion->iva_porcentaje, 0) . '%' : '' }}</td>
                <td class="text-right">$ {{ number_format($ivaVal, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">$ {{ number_format($cotizacion->total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- KPIs (solo cotizaciones con OT) --}}
@if(!$cotizacion->es_previa && $cotizacion->ot && $cotizacion->ot->ha)
<div class="kpis">
    <table style="width:auto; border-collapse:separate; border-spacing:5px 0;">
        <tr>
            <td class="kpi-item">
                <div class="kpi-val">{{ number_format($cotizacion->ot->ha, 1) }}</div>
                <div class="kpi-lab">HA</div>
            </td>
            <td class="kpi-item">
                <div class="kpi-val">{{ $cotizacion->ot->dr }}</div>
                <div class="kpi-lab">DR (días)</div>
            </td>
            <td class="kpi-item">
                @php $tg = $cotizacion->ot->tg; @endphp
                <div class="kpi-val">
                    <span class="badge-{{ strtolower($tg) }}">{{ $tg }}</span>
                </div>
                <div class="kpi-lab">TG</div>
            </td>
            @if($cotizacion->ot->salida_estimada)
            <td class="kpi-item">
                <div class="kpi-val" style="font-size:13px;">{{ $cotizacion->ot->salida_estimada->format('d/m/Y') }}</div>
                <div class="kpi-lab">Salida Estimada</div>
            </td>
            @endif
        </tr>
    </table>
</div>
@endif

@if($cotizacion->observaciones)
<div class="observaciones">
    <strong>Observaciones:</strong> {{ $cotizacion->observaciones }}
</div>
@endif

<div class="generado">
    Cotización generada por {{ $cotizacion->creadaPor->name }} el {{ $cotizacion->created_at->format('d/m/Y H:i') }}
</div>

@include('pdf._footer', ['config' => $config])


</body>
</html>
