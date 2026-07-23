{{--
    Encabezado corporativo reutilizable para PDFs tamaño carta (cotizaciones, liquidaciones).
    Variables esperadas:
      $config  App\Models\ConfiguracionEmpresa
      $titulo  string  Ej: "COTIZACIÓN"
      $numero  string|int|null  Consecutivo del documento
      $fecha   string|null  Fecha ya formateada para mostrar
      $badge   string|null  Etiqueta secundaria, ej. "PREVIA"
      $color   string|null  Color corporativo del documento (default azul Tabler)
      $logoMax int|null     Lado máximo del logo en px (default 56)
--}}
@php
    $razonSocial = trim($config->razon_social ?? '') ?: 'INGECOSMOS';
    $tieneLogo   = !empty($config->logo_path) && file_exists($config->logo_path);
    $colorDoc    = $color   ?? '#1a56db';
    $logoLado    = $logoMax ?? 56;
@endphp
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="vertical-align:middle; padding:0;">
            <table style="border-collapse:collapse;">
                <tr>
                    @if($tieneLogo)
                    <td style="width:{{ $logoLado + 2 }}px; vertical-align:middle; padding-right:12px;">
                        <img src="{{ $config->logo_path }}" style="max-width:{{ $logoLado }}px; max-height:{{ $logoLado }}px;">
                    </td>
                    @endif
                    <td style="vertical-align:middle;">
                        <div style="font-size:18px; font-weight:bold; color:#111827; letter-spacing:.2px;">
                            {{ $razonSocial }}
                        </div>
                        @if(!empty($config->nit))
                        <div style="font-size:10px; color:#6b7280; margin-top:1px;">NIT {{ $config->nit }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
        <td style="text-align:right; vertical-align:middle;">
            <div style="display:inline-block; background:{{ $colorDoc }}; color:#fff; font-size:12px; font-weight:bold;
                        padding:5px 14px; border-radius:3px; letter-spacing:.4px;">
                {{ $titulo }}{{ !empty($numero) ? ' N.º ' . $numero : '' }}
            </div>
            @if(!empty($badge))
            <div style="margin-top:5px;">
                <span style="background:#fef3c7; color:#92400e; font-size:9px; font-weight:bold;
                             padding:2px 8px; border-radius:9px;">{{ $badge }}</span>
            </div>
            @endif
            @if(!empty($fecha))
            <div style="font-size:10px; color:#6b7280; margin-top:5px;">{{ $fecha }}</div>
            @endif
        </td>
    </tr>
</table>
<div style="border-bottom:3px solid {{ $colorDoc }}; margin-top:9px;"></div>
<div style="border-bottom:1px solid #e5e7eb; margin-bottom:16px;"></div>
