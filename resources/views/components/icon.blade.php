@props(['name', 'size' => 20])
@php
// Iconos de línea estilo Tabler (mismo trazo que el logo del carro).
// SVG estático y de confianza: por eso se imprime con {!! !!}.
$dot = $name === 'dot';
$paths = match($name) {
    'x'              => '<path d="M18 6l-12 12" /><path d="M6 6l12 12" />',
    'check'          => '<path d="M5 12l5 5l10 -10" />',
    'pencil'         => '<path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" />',
    'search'         => '<circle cx="10" cy="10" r="7" /><path d="M21 21l-6 -6" />',
    'arrow-left'     => '<path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" />',
    'arrow-right'    => '<path d="M5 12l14 0" /><path d="M13 18l6 -6" /><path d="M13 6l6 6" />',
    'arrow-up'       => '<path d="M12 5l0 14" /><path d="M18 11l-6 -6" /><path d="M6 11l6 -6" />',
    'arrow-down'     => '<path d="M12 5l0 14" /><path d="M18 13l-6 6" /><path d="M6 13l6 6" />',
    'arrow-back-up'  => '<path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" />',
    'download'       => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" />',
    'upload'         => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" />',
    'restore'        => '<path d="M3.06 13a9 9 0 1 0 .49 -4.087" /><path d="M3 4.001v5h5" /><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />',
    'refresh'        => '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />',
    'alert-triangle' => '<path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" />',
    'lock'           => '<rect x="5" y="11" width="14" height="10" rx="2" /><circle cx="12" cy="16" r="1" /><path d="M8 11v-4a4 4 0 0 1 8 0v4" />',
    'player-play'    => '<path d="M7 4v16l13 -8z" />',
    'player-pause'   => '<rect x="6" y="5" width="4" height="14" rx="1" /><rect x="14" y="5" width="4" height="14" rx="1" />',
    'truck'          => '<circle cx="7" cy="17" r="2" /><circle cx="17" cy="17" r="2" /><path d="M5 17h-2v-6l2-5h9l4 5h1a2 2 0 0 1 2 2v4h-2" /><path d="M9 17h6" />',
    'tool'           => '<path d="M7 10h-3l-1 -2l1 -2h3" /><path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5" />',
    'ban'            => '<circle cx="12" cy="12" r="9" /><path d="M5.7 5.7l12.6 12.6" />',
    'chevron-down'   => '<path d="M6 9l6 6l6 -6" />',
    'trash'          => '<path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />',
    default          => '',
};
@endphp
@if($dot)
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="currentColor" stroke="none" {{ $attributes->merge(['class' => 'icon-inline']) }}>
    <circle cx="12" cy="12" r="5" />
</svg>
@else
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->merge(['class' => 'icon-inline']) }}>
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    {!! $paths !!}
</svg>
@endif
