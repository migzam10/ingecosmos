@props(['tg' => null])

@if($tg)
@php
$clase = match($tg) {
    'Leve'   => 'tg-leve',
    'Medio'  => 'tg-medio',
    'Fuerte' => 'tg-fuerte',
    default  => 'bg-secondary',
};
$texto = match($tg) {
    'Leve'   => 'Daño Leve',
    'Medio'  => 'Daño Medio',
    'Fuerte' => 'Daño Fuerte',
    default  => $tg,
};
@endphp
<span class="badge {{ $clase }}">{{ $texto }}</span>
@else
<span class="text-muted">—</span>
@endif
