@props(['estado'])

@php
$config = match($estado) {
    'PTE_COTIZACION'      => ['color' => 'secondary', 'texto' => 'Pte. Cotización'],
    'PTE_AUTORIZACION'    => ['color' => 'warning',   'texto' => 'Pte. Autorización'],
    'PTE_ORDEN'           => ['color' => 'warning',   'texto' => 'Pte. Orden'],
    'PTE_REPUESTOS'       => ['color' => 'orange',    'texto' => 'Pte. Repuestos'],
    'RTO_INSTALADO'       => ['color' => 'cyan',      'texto' => 'Rto. Instalado'],
    'EN_PROCESO'          => ['color' => 'blue',      'texto' => 'En Proceso'],
    'PROGRAMADO_ENTREGA'  => ['color' => 'teal',      'texto' => 'Prog. Entrega'],
    'ENTREGADO'           => ['color' => 'success',   'texto' => 'Entregado'],
    'NO_AUTORIZADO'       => ['color' => 'danger',    'texto' => 'No Autorizado'],
    'ORDEN_ANULADA'       => ['color' => 'danger',    'texto' => 'Anulada'],
    'PERDIDA_TOTAL'       => ['color' => 'dark',      'texto' => 'Pérdida Total'],
    'VFT'                 => ['color' => 'purple',    'texto' => 'VFT'],
    'GARANTIA'            => ['color' => 'indigo',    'texto' => 'Garantía'],
    'ARREGLO_DIRECTO'     => ['color' => 'lime',      'texto' => 'Arreglo Directo'],
    'ENTREGA_PARCIAL'     => ['color' => 'yellow',    'texto' => 'Entrega Parcial'],
    'EN_OTRO_TALLER'      => ['color' => 'pink',      'texto' => 'Otro Taller'],
    'PTE_RETIRO'          => ['color' => 'muted',     'texto' => 'Pte. Retiro'],
    default               => ['color' => 'secondary', 'texto' => $estado],
};
@endphp

<span class="badge bg-{{ $config['color'] }}-lt estado-badge">{{ $config['texto'] }}</span>
