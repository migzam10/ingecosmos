<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    /* Reset sin margin (dompdf: `* { margin:0 }` anula el margen de @page). */
    * { padding: 0; box-sizing: border-box; }
    @page { margin: 34px 36px 40px 36px; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a2e; }

    .doc { border: 2px solid #1a1a5e; }

    /* ── Encabezado ── */
    .encabezado { width: 100%; border-collapse: collapse; }
    .encabezado td { vertical-align: middle; padding: 8px 10px; }
    .empresa-nombre { font-size: 20px; font-weight: bold; color: #1a1a5e; letter-spacing: .5px; }
    .empresa-info { font-size: 9px; color: #333; line-height: 1.5; }
    .oc-box { border: 2px solid #1a1a5e; border-radius: 6px; text-align: center; padding: 8px 14px; }
    .oc-titulo { font-size: 15px; font-weight: bold; color: #1a1a5e; letter-spacing: .5px; }
    .oc-num { font-size: 20px; font-weight: bold; color: #c0392b; }

    /* ── Datos proveedor / fecha ── */
    .datos { width: 100%; border-collapse: collapse; border-top: 2px solid #1a1a5e; }
    .datos td { border: 1px solid #1a1a5e; padding: 5px 8px; font-size: 10.5px; vertical-align: middle; }
    .campo-lbl { font-weight: bold; color: #1a1a5e; font-size: 9px; text-transform: uppercase; }

    /* ── Ítems ── */
    .items { width: 100%; border-collapse: collapse; border-top: 2px solid #1a1a5e; }
    .items th { background: #ececf5; border: 1px solid #1a1a5e; padding: 5px 6px;
                font-size: 9px; text-transform: uppercase; color: #1a1a5e; text-align: center; }
    .items td { border: 1px solid #1a1a5e; padding: 5px 7px; font-size: 10.5px; }
    .items td.num { text-align: right; }
    .items td.center { text-align: center; }
    .items .fila-vacia td { height: 20px; }
    .items .totrow td { font-weight: bold; }
    .items .total-final td { font-size: 12.5px; font-weight: bold; color: #1a1a5e; background: #ececf5; }

    /* ── Pie ── */
    .obs { border: 1px solid #1a1a5e; border-top: none; padding: 6px 8px; font-size: 10px; min-height: 34px; }
    .firma { width: 100%; border-collapse: collapse; }
    .firma td { border: 1px solid #1a1a5e; border-top: none; padding: 8px 8px 18px; font-size: 10px; width: 50%; }
    .firma .lbl { font-weight: bold; color: #1a1a5e; font-size: 9px; text-transform: uppercase; }
    .legal { font-size: 7.5px; color: #444; text-align: justify; margin-top: 8px; line-height: 1.4; }
</style>
</head>
<body>
@php
    $razon = trim($config->razon_social ?? '') ?: 'INGECOSMOS';
    $tieneLogo = !empty($config->logo_path) && file_exists($config->logo_path);
@endphp
<div class="doc">

    {{-- Encabezado --}}
    <table class="encabezado">
        <tr>
            <td style="width:58%;">
                <table style="border-collapse:collapse;"><tr>
                    @if($tieneLogo)
                    <td style="width:74px; vertical-align:middle; padding:0 10px 0 0;">
                        <img src="{{ $config->logo_path }}" style="max-width:66px; max-height:66px;">
                    </td>
                    @endif
                    <td style="vertical-align:middle;">
                        <div class="empresa-nombre">{{ $razon }}</div>
                        <div class="empresa-info">
                            @if(!empty($config->nit))NIT: {{ $config->nit }}<br>@endif
                            @if(!empty($config->direccion)){{ $config->direccion }}@if(!empty($config->ciudad)) - {{ $config->ciudad }}@endif<br>@endif
                            @if(!empty($config->telefono))Celular: {{ $config->telefono }}<br>@endif
                            @if(!empty($config->email))E-mail: {{ $config->email }}@endif
                        </div>
                    </td>
                </tr></table>
            </td>
            <td style="width:42%; text-align:right;">
                <div class="oc-box">
                    <span class="oc-titulo">ORDEN DE COMPRA N.º</span>
                    <span class="oc-num">{{ $orden->numero }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Datos proveedor / fecha / vehículo --}}
    <table class="datos">
        <tr>
            <td style="width:12%;"><span class="campo-lbl">Proveedor:</span></td>
            <td style="width:48%;">{{ $orden->proveedor?->nombre }}</td>
            <td style="width:15%;"><span class="campo-lbl">Fecha:</span></td>
            <td style="width:25%;">{{ $orden->fecha?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><span class="campo-lbl">NIT:</span></td>
            <td>{{ $orden->proveedor?->nit }}</td>
            <td><span class="campo-lbl">Forma de pago:</span></td>
            <td>{{ ucfirst(strtolower($orden->forma_pago)) }}</td>
        </tr>
        <tr>
            <td><span class="campo-lbl">Teléfono:</span></td>
            <td>{{ $orden->proveedor?->telefono }}</td>
            <td><span class="campo-lbl">OT:</span></td>
            <td>{{ $orden->numero_ot }}</td>
        </tr>
        <tr>
            <td><span class="campo-lbl">Placa:</span></td>
            <td>{{ $orden->placa }}</td>
            <td><span class="campo-lbl">Vehículo:</span></td>
            <td>{{ trim(($orden->marca?->nombre ?? '') . ' ' . ($orden->modelo?->nombre ?? '')) }}</td>
        </tr>
    </table>

    {{-- Ítems --}}
    @php $filasVacias = max(0, 8 - $orden->items->count()); @endphp
    <table class="items">
        <thead>
            <tr>
                <th style="width:11%;">Cantidad</th>
                <th style="width:9%;">Und</th>
                <th>Descripción</th>
                <th style="width:17%;">Vr. Unitario</th>
                <th style="width:17%;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orden->items as $it)
            <tr>
                <td class="center">{{ rtrim(rtrim(number_format($it->cantidad, 2, ',', '.'), '0'), ',') }}</td>
                <td class="center">{{ $it->unidad }}</td>
                <td>{{ $it->descripcion }}</td>
                <td class="num">$ {{ number_format($it->valor_unitario, 0, ',', '.') }}</td>
                <td class="num">$ {{ number_format($it->valor_total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @for($i = 0; $i < $filasVacias; $i++)
            <tr class="fila-vacia"><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor

            <tr class="totrow">
                <td colspan="4" class="num">Subtotal</td>
                <td class="num">$ {{ number_format($orden->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($orden->descuento_valor > 0)
            <tr class="totrow">
                <td colspan="4" class="num">Descuento{{ $orden->descuento_porcentaje > 0 ? ' ' . number_format($orden->descuento_porcentaje, 0) . '%' : '' }}</td>
                <td class="num">- $ {{ number_format($orden->descuento_valor, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($orden->iva_valor > 0)
            <tr class="totrow">
                <td colspan="4" class="num">IVA{{ $orden->iva_porcentaje > 0 ? ' ' . number_format($orden->iva_porcentaje, 0) . '%' : '' }}</td>
                <td class="num">$ {{ number_format($orden->iva_valor, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-final">
                <td colspan="4" class="num">TOTAL</td>
                <td class="num">$ {{ number_format($orden->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Observaciones --}}
    <div class="obs">
        <span class="campo-lbl">Observaciones:</span> {{ $orden->observaciones }}
    </div>

    {{-- Elaborado por --}}
    <table class="firma">
        <tr>
            <td colspan="2">
                <span class="lbl">Elaborado por:</span> {{ $orden->creadoPor?->name }}
            </td>
        </tr>
    </table>
</div>

<div class="legal">
    LAS ESPECIFICACIONES DE LA MERCANCÍA ANOTADAS EN ESTA ORDEN DEBERÁN SER IGUALES A LO SUMINISTRADO Y COINCIDIR CON LA FACTURA,
    FAVOR ANOTAR EL NÚMERO DE ESTA ORDEN EN LAS FACTURAS, PLANILLAS, RELACIONES, REMISIONES Y DEMÁS DOCUMENTOS. LAS COMPRAS QUE SE
    EFECTÚAN CON LA PRESENTE SERÁN PAGADAS POR EL VALOR NETO, DESCUENTOS E IMPUESTOS QUE CON ELLA APAREZCA. PASADAS LAS FECHAS DE
    ENTREGAS ESTIPULADAS EN LA MISMA, NOS RESERVAMOS EL DERECHO DE RECIBIR LA MERCANCÍA, DE NO ESTAR DE ACUERDO CON ESTA ORDEN
    FAVOR NO ACEPTARLA.
</div>

</body>
</html>
