{{--
    Pie de página corporativo. Se incluye una sola vez al final del documento
    (en flujo normal, no repetido por página: dompdf no combina de forma
    confiable "position: fixed" con los floats usados en los cuadros de
    resumen de estas plantillas).
    Variable esperada: $config  App\Models\ConfiguracionEmpresa

    Si el usuario definió un texto propio en "Pie de página en PDFs", se
    muestra tal cual y nada más (sin repetir razón social/NIT debajo).
    Si no lo definió, se arma una línea de contacto + razón social/NIT.
--}}
@php
    $razonSocial = trim($config->razon_social ?? '') ?: 'INGECOSMOS';
    $contacto = collect([
        $config->direccion ?? null,
        $config->ciudad ?? null,
        $config->telefono ?? null,
        $config->email ?? null,
    ])->filter()->implode('  ·  ');
@endphp
<div style="clear:both; margin-top:16px; text-align:center;
            font-size:9px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:7px;">
    @if(!empty($config->pie_pagina_pdf))
    <div>{{ $config->pie_pagina_pdf }}</div>
    @else
    @if($contacto)
    <div>{{ $contacto }}</div>
    @endif
    <div style="margin-top:2px;">
        {{ $razonSocial }}{{ !empty($config->nit) ? ' · NIT ' . $config->nit : '' }}
    </div>
    @endif
</div>
