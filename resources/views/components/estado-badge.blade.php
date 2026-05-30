@props(['estado'])

@php
$config = match($estado) {
    'PTE_COTIZACION'      => ['color' => 'secondary', 'texto' => 'Pendiente Cotización'],
    'PTE_AUTORIZACION'    => ['color' => 'warning',   'texto' => 'Pendiente Autorización'],
    'PTE_ORDEN'           => ['color' => 'warning',   'texto' => 'Pendiente Orden Repuestos'],
    'PTE_REPUESTOS'       => ['color' => 'orange',    'texto' => 'Esperando Repuestos'],
    'RTO_INSTALADO'       => ['color' => 'cyan',      'texto' => 'Repuestos Instalados'],
    'EN_PROCESO'          => ['color' => 'blue',      'texto' => 'En Proceso'],
    'PROGRAMADO_ENTREGA'  => ['color' => 'teal',      'texto' => 'Programado para Entrega'],
    'ENTREGADO'           => ['color' => 'success',   'texto' => 'Entregado'],
    'NO_AUTORIZADO'       => ['color' => 'danger',    'texto' => 'No Autorizado'],
    'ORDEN_ANULADA'       => ['color' => 'danger',    'texto' => 'Orden Anulada'],
    'PERDIDA_TOTAL'       => ['color' => 'dark',      'texto' => 'Pérdida Total'],
    'VFT'                 => ['color' => 'purple',    'texto' => 'Vehículo Fuera del Taller'],
    'GARANTIA'            => ['color' => 'indigo',    'texto' => 'Garantía'],
    'ARREGLO_DIRECTO'     => ['color' => 'lime',      'texto' => 'Arreglo Directo'],
    'ENTREGA_PARCIAL'     => ['color' => 'yellow',    'texto' => 'Entrega Parcial'],
    'EN_OTRO_TALLER'      => ['color' => 'pink',      'texto' => 'En Otro Taller'],
    'PTE_RETIRO'          => ['color' => 'muted',     'texto' => 'Pendiente de Retiro'],
    default               => ['color' => 'secondary', 'texto' => $estado],
};
@endphp

<span class="badge bg-{{ $config['color'] }}-lt estado-badge">{{ $config['texto'] }}</span>
