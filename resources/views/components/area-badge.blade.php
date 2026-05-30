@props(['area'])

@php
$nombre = match($area) {
    'LYP'      => 'Latonería y Pintura',
    'MECANICA' => 'Mecánica',
    default    => $area,
};
@endphp

<span class="badge bg-secondary-lt">{{ $nombre }}</span>
