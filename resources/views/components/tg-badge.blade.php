@props(['tg' => null])

@if($tg)
@php
$clase = match($tg) {
    'Leve'   => 'tg-leve',
    'Medio'  => 'tg-medio',
    'Fuerte' => 'tg-fuerte',
    default  => 'bg-secondary',
};
@endphp
<span class="badge {{ $clase }}">{{ $tg }}</span>
@else
<span class="text-muted">—</span>
@endif
