<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; padding: 16px; }
        .titulo { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .subtitulo { text-align: center; font-size: 10px; color: #555; margin-bottom: 12px; }
        .linea { border-top: 1px dashed #aaa; margin: 8px 0; }
        .fila { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .etiqueta { color: #555; }
        .valor { font-weight: bold; text-align: right; }
        .total-box { background: #f5f5f5; border: 1px solid #ddd; padding: 8px; text-align: center; margin-top: 10px; border-radius: 4px; }
        .total-monto { font-size: 18px; font-weight: bold; color: #1a1a1a; }
        .firma { margin-top: 30px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-abono    { background: #d1e7dd; color: #0a3622; }
        .badge-anticipo { background: #fff3cd; color: #664d03; }
        .badge-final    { background: #cfe2ff; color: #084298; }
    </style>
</head>
<body>

<div class="titulo">INGECOSMOS</div>
<div class="subtitulo">Taller Automotriz — Comprobante de Pago</div>
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
        @if($pago->tipo === 'ABONO') Abono
        @elseif($pago->tipo === 'ANTICIPO') Anticipo
        @else Pago Final
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
    Generado por INGECOSMOS · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
