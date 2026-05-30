@props(['estado'])

@php
$config = match($estado) {
    'INCUMPLIDO'   => ['clase' => 'semaforo-incumplido', 'texto' => 'INCUMPLIDO'],
    'ENTREGAR_HOY' => ['clase' => 'semaforo-entregar',   'texto' => 'ENTREGAR HOY'],
    'A_TIEMPO'     => ['clase' => 'semaforo-a-tiempo',   'texto' => 'A TIEMPO'],
    'OK'           => ['clase' => 'semaforo-ok',         'texto' => 'OK'],
    default        => ['clase' => 'semaforo-sin-fecha',  'texto' => 'SIN FECHA'],
};
@endphp

<span class="badge {{ $config['clase'] }}">{{ $config['texto'] }}</span>
