<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    /* OJO: el reset NO debe incluir `margin:0`, porque en dompdf el `*`
       también alcanza a html/body y anularía el margen de @page. */
    * { padding: 0; box-sizing: border-box; }
    @page { margin: 40px 42px 46px 42px; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }

    .info-grid { display: table; width: 100%; margin-bottom: 9px; }
    .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
    .info-col:last-child { padding-right: 0; }
    .info-box { background: #f8f9fa; border: 1px solid #eef0f2; border-radius: 4px; padding: 7px 10px; }
    .info-row { margin-bottom: 3px; }
    .info-row:last-child { margin-bottom: 0; }
    .label { color: #8a8f98; font-size: 8px; text-transform: uppercase; letter-spacing: .3px; }
    .value { font-weight: bold; font-size: 11px; color: #1f2937; }

    h2 { font-size: 11px; background: #040065; color: white;
         padding: 4px 9px; margin: 9px 0 5px 0; border-radius: 3px; letter-spacing: .3px; }
    h2 .leyenda { float: right; font-weight: normal; font-size: 9px; opacity: .85; }

    /* ── Inventario ── */
    table.inv { width: 100%; border-collapse: collapse; }
    table.inv td { padding: 2.5px 7px; border-bottom: 1px solid #f0f1f3; font-size: 9.5px; }
    table.inv tr:nth-child(even) td { background: #fbfbfc; }
    .inv-est { font-weight: bold; text-align: center; width: 28px; }
    .e-B { color: #065f46; }
    .e-R { color: #92400e; }
    .e-M { color: #991b1b; }
    .e-vacio { color: #b8bcc4; }

    /* ── Fotos ── */
    table.fotos { width: 100%; border-collapse: separate; border-spacing: 6px; }
    table.fotos td { border: 1px solid #eef0f2; background: #f8f9fa; border-radius: 4px;
                     padding: 5px; text-align: center; vertical-align: top; width: 33.33%; }
    table.fotos img { max-width: 100%; max-height: 150px; border-radius: 3px; }
    .foto-cap { font-size: 8.5px; color: #6b7280; margin-top: 4px; }

    .observaciones { clear: both; margin-top: 5px; padding: 6px 10px; background: #f8f9fa;
                     border-left: 3px solid #040065; border-radius: 3px; font-size: 10px; color: #374151; }
    .vacio { color: #9ca3af; font-size: 10px; padding: 2px; }

    .generado { clear: both; margin-top: 10px; font-size: 9px; color: #9ca3af; }

    /* ── Términos y condiciones ── */
    .terminos { clear: both; margin-top: 8px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    .terminos .bloque-tit { font-size: 9px; font-weight: bold; color: #040065;
                            text-transform: uppercase; letter-spacing: .3px; margin: 5px 0 1px; }
    .terminos p { font-size: 8px; color: #374151; text-align: justify; line-height: 1.25; margin: 0 0 1px; }
    .terminos .num { font-weight: bold; color: #040065; }
    .terminos .grupo { page-break-inside: avoid; }
</style>
</head>
<body>

@php
    $v   = $orden->vehiculo;
    $cl  = $orden->clientePersona;
    $inv = $orden->inventario;
@endphp

@include('pdf._header', [
    'config'  => $config,
    'titulo'  => 'ORDEN DE TRABAJO',
    'numero'  => $orden->numero_ot,
    'fecha'   => $orden->fecha_ingreso->format('d/m/Y'),
    'color'   => '#040065',
    'logoMax' => 92,
])

{{-- Vehículo y propietario --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-box">
            <div class="info-row"><span class="label">Placa</span><br><span class="value">{{ $v->placa }}</span></div>
            <div class="info-row"><span class="label">Vehículo</span><br>
                <span class="value">{{ trim(($v->marca?->nombre ?? '') . ' ' . ($v->modelo?->nombre ?? '')) ?: '—' }}</span>
            </div>
            <div class="info-row"><span class="label">Color</span><br><span class="value">{{ $v->color ?? '—' }}</span></div>
            <div class="info-row"><span class="label">Año</span><br><span class="value">{{ $v->anio ?? '—' }}</span></div>
            <div class="info-row"><span class="label">KM ingreso</span><br><span class="value">{{ number_format($orden->km_ingreso, 0, ',', '.') }}</span></div>
            <div class="info-row"><span class="label">Combustible</span><br><span class="value">{{ $orden->nivel_combustible }}/10</span></div>
        </div>
    </div>
    <div class="info-col">
        <div class="info-box">
            <div class="info-row"><span class="label">Propietario</span><br><span class="value">{{ $cl?->nombre ?? '—' }}</span></div>
            <div class="info-row"><span class="label">Cédula / NIT</span><br><span class="value">{{ $cl?->cedula ?? '—' }}</span></div>
            <div class="info-row"><span class="label">Teléfono</span><br><span class="value">{{ $cl?->telefono ?? '—' }}</span></div>
            <div class="info-row"><span class="label">Email</span><br><span class="value">{{ $cl?->email ?? '—' }}</span></div>
            @if($cl?->direccion)
            <div class="info-row"><span class="label">Dirección</span><br><span class="value">{{ $cl->direccion }}</span></div>
            @endif
            <div class="info-row"><span class="label">Llaves / Documentos</span><br>
                <span class="value">{{ $orden->llaves_entregadas ? 'Sí' : 'No' }} / {{ $orden->documentos_entregados ? 'Sí' : 'No' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Inventario --}}
<h2>Inventario del Vehículo <span class="leyenda">B = Bueno · R = Regular · M = Malo</span></h2>
@if($inv)
@php
    $filas = [];
    foreach ($invSimples as $campo => $etq) {
        $filas[] = [$etq, $inv->$campo, null];
    }
    foreach ($invCantidad as $campo => $etq) {
        $filas[] = [$etq, $inv->$campo, $inv->{$campo . '_qty'}];
    }
    $chunks = array_chunk($filas, 3);
@endphp
<table class="inv">
    @foreach($chunks as $chunk)
    <tr>
        @foreach($chunk as [$etq, $est, $qty])
        <td style="width:22%;">{{ $etq }}@if($qty !== null && $qty !== '') <span style="color:#8a8f98;">({{ $qty }})</span>@endif</td>
        <td class="inv-est {{ $est ? 'e-' . $est : 'e-vacio' }}">{{ $est ?: '—' }}</td>
        @endforeach
        @for($i = count($chunk); $i < 3; $i++)<td></td><td></td>@endfor
    </tr>
    @endforeach
</table>
@if($inv->observaciones)
<div class="observaciones"><strong>Obs. del inventario:</strong> {{ $inv->observaciones }}</div>
@endif
@else
<div class="vacio">Sin inventario registrado.</div>
@endif

{{-- Fotos --}}
<h2>Fotos del Vehículo <span class="leyenda">{{ $orden->fotos->count() }} foto(s)</span></h2>
@if($orden->fotos->count())
<table class="fotos">
    @foreach($orden->fotos->chunk(3) as $fila)
    <tr>
        @foreach($fila as $foto)
        <td>
            @php
                $ruta = storage_path('app/public/' . $foto->ruta);
                $img = is_file($ruta)
                    ? 'data:' . (mime_content_type($ruta) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($ruta))
                    : null;
            @endphp
            @if($img)<img src="{{ $img }}">@else<span class="foto-cap">(imagen no disponible)</span>@endif
            @if($foto->descripcion)<div class="foto-cap">{{ $foto->descripcion }}</div>@endif
        </td>
        @endforeach
        @for($i = $fila->count(); $i < 3; $i++)<td style="border:none; background:none;"></td>@endfor
    </tr>
    @endforeach
</table>
@else
<div class="vacio">Sin fotos registradas.</div>
@endif

{{-- Observaciones --}}
<h2>Observaciones</h2>
@if($orden->observaciones)
<div class="observaciones">{{ $orden->observaciones }}</div>
@else
<div class="vacio">Sin observaciones.</div>
@endif

<div class="generado">
    Elaborado por {{ $orden->creadoPor?->name ?? '—' }} · {{ now()->format('d/m/Y H:i') }}
</div>

{{-- Términos, garantías y condiciones de pago --}}
<div class="terminos">
    <div class="bloque-tit">Términos y condiciones</div>
    <p>
        <span class="num">1.</span> Autorizo al taller a realizar pruebas de rutas requeridas fuera del taller, traslados.
        <span class="num">2.</span> Realización de diagnósticos requeridos en lo solicitado en la orden.
        <span class="num">3.</span> El taller se compromete a no realizar trabajos adicionales sin previa autorización.
        <span class="num">4.</span> Para la entrega del vehículo se requiere el pago total de las reparaciones autorizadas y realizadas.
        <span class="num">5.</span> Las pertenencias deben ser retiradas en el momento del inventario. No nos hacemos responsables de objetos personales no reportados.
        <span class="num">6.</span> Cargos por bodegaje: pasados los 3 días de notificada la finalización del trabajo, si no ha sido retirado el vehículo, se genera un cobro diario de $20.000, que deben ser cancelados al momento del retiro.
    </p>

    <div class="grupo">
        <div class="bloque-tit">Garantías</div>
        <p>
            <span class="num">1.</span> Reparación de lámina y pintura tiene cobertura de 6 meses.
            <span class="num">2.</span> Partes electrónicas no cuentan con coberturas.
            <span class="num">3.</span> Parte mecánica tendrá cobertura de repuestos hasta cumplir los 3 meses.
        </p>
        <p>La garantía se hace efectiva siempre y cuando el vehículo no haya sido intervenido después de la reparación o usado productos abrasivos y/o mal uso de la unidad que afecten la calidad de la reparación.</p>
    </div>

    <div class="grupo">
        <div class="bloque-tit">Pagos</div>
        <p>Los pagos se realizan únicamente a las cuentas autorizadas a la empresa. No se aceptan pagos transferidos a cuentas personales de los colaboradores. El taller se exime de esta responsabilidad.</p>
    </div>
</div>

@include('pdf._footer', ['config' => $config])

</body>
</html>
