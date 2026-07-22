<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; padding: 20px 18px; }
        .logo-box { text-align: center; margin-bottom: 6px; }
        .logo-box img { max-height: 46px; max-width: 150px; }
        .titulo { text-align: center; font-size: 14px; font-weight: bold; color: #111827; }
        .nit { text-align: center; font-size: 9px; color: #6b7280; margin-top: 1px; }
        .subtitulo { text-align: center; font-size: 10px; color: #555; margin: 3px 0 10px; }
        .linea { border-top: 1px dashed #aaa; margin: 8px 0; }
        .fila { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .etiqueta { color: #555; }
        .valor { font-weight: bold; text-align: right; }
        .total-box { background: #eef2ff; border: 1px solid #c7d3f2; padding: 9px; text-align: center;
                     margin-top: 10px; border-radius: 4px; }
        .total-monto { font-size: 19px; font-weight: bold; color: #1a56db; }
        .firma { margin-top: 26px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #eee; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9px; font-size: 9.5px; font-weight: bold; }
        .badge-abono    { background: #d1e7dd; color: #0a3622; }
        .badge-anticipo { background: #fff3cd; color: #664d03; }
        .badge-final    { background: #cfe2ff; color: #084298; }
    </style>
</head>
<body>

@php
    $razonSocial = trim($config->razon_social ?? '') ?: 'INGECOSMOS';
    $tieneLogo   = !empty($config->logo_path) && file_exists($config->logo_path);
@endphp

@if($tieneLogo)
<div class="logo-box"><img src="{{ $config->logo_path }}"></div>
@endif
<div class="titulo">{{ $razonSocial }}</div>
@if(!empty($config->nit))
<div class="nit">NIT {{ $config->nit }}</div>
@endif
<div class="subtitulo">Comprobante de Pago — Mano de Obra</div>
<div class="linea"></div>

<div class="fila">
    <span class="etiqueta">N° Comprobante</span>
    <span class="valor"># {{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</span>
</div>
<div class="fila">
    <span class="etiqueta">Fecha de pago</span>
    <span class="valor">{{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : $pago->created_at->format('d/m/Y') }}</span>
</div>
<div class="fila">
    <span class="etiqueta">Registrado el</span>
    <span class="valor">{{ $pago->created_at->format('d/m/Y H:i') }}</span>
</div>

<div class="linea"></div>

<div class="fila">
    <span class="etiqueta">Técnico</span>
    <span class="valor">{{ $pago->tecnico->nombre }}</span>
</div>
<div class="fila">
    <span class="etiqueta">Período</span>
    <span class="valor">{{ $meses[$pago->mes] }} {{ $pago->anio }}</span>
</div>
<div class="fila">
    <span class="etiqueta">Tipo</span>
    <span class="valor">
        @if($pago->tipo === 'ABONO')
            <span class="badge badge-abono">Abono</span>
        @elseif($pago->tipo === 'ANTICIPO')
            <span class="badge badge-anticipo">Anticipo</span>
        @else
            <span class="badge badge-final">Pago Final</span>
        @endif
    </span>
</div>
@if($pago->concepto)
<div class="fila">
    <span class="etiqueta">Concepto</span>
    <span class="valor">{{ $pago->concepto }}</span>
</div>
@endif

<div class="total-box">
    <div style="font-size:10px;color:#555;margin-bottom:3px;">VALOR PAGADO</div>
    <div class="total-monto">$ {{ number_format($pago->monto, 0, ',', '.') }}</div>
</div>

<div class="linea"></div>

<div class="fila">
    <span class="etiqueta">Registrado por</span>
    <span class="valor">{{ $pago->registradoPor?->name ?? '—' }}</span>
</div>

<div class="firma">
    Generado por {{ $razonSocial }} · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
