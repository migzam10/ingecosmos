@extends('layouts.app')

@section('title', 'Vehículo OT #' . $trabajo->ot->numero_ot)
@section('page_title', 'Información del Vehículo')
@section('breadcrumb', 'Mis Tareas')

@section('page_actions')
<a href="{{ route('mis-tareas.index') }}" class="btn btn-outline-secondary btn-sm">
    ← Mis Tareas
</a>
@endsection

@section('content')
@php $ot = $trabajo->ot; @endphp
<div class="row g-3">

    {{-- Datos del vehículo --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Vehículo</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 small">Placa</dt>
                    <dd class="col-7">
                        <span class="badge bg-blue-lt fw-bold fs-6">{{ $ot->vehiculo->placa }}</span>
                    </dd>
                    <dt class="col-5 small">Marca</dt>
                    <dd class="col-7 small">{{ $ot->vehiculo->marca->nombre }}</dd>
                    <dt class="col-5 small">Modelo</dt>
                    <dd class="col-7 small">{{ $ot->vehiculo->modelo?->nombre ?? '—' }}</dd>
                    <dt class="col-5 small">Color</dt>
                    <dd class="col-7 small">{{ $ot->vehiculo->color ?? '—' }}</dd>
                    <dt class="col-5 small">Año</dt>
                    <dd class="col-7 small">{{ $ot->vehiculo->anio ?? '—' }}</dd>
                    <dt class="col-5 small">Km ingreso</dt>
                    <dd class="col-7 small">{{ number_format($ot->km_ingreso) }} km</dd>
                    <dt class="col-5 small">Área</dt>
                    <dd class="col-7 small">{{ $ot->area === 'LYP' ? 'Latonería y Pintura' : 'Mecánica' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Observaciones generales --}}
    @if($ot->observaciones)
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Observaciones de la OT</h3></div>
            <div class="card-body">
                <p class="mb-0">{{ $ot->observaciones }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Inventario B/R/G --}}
    @if($ot->inventario)
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Inventario del Vehículo</h3></div>
            <div class="card-body">
                @php
                $inv = $ot->inventario;
                $items = [
                    'Retrovisores'    => $inv->retrovisores,
                    'Retrovisor int.' => $inv->retrovisor_interno,
                    'Radio'           => $inv->radio,
                    'Encendedor'      => $inv->encendedor,
                    'Pito'            => $inv->pito,
                    'Tapizado'        => $inv->tapizado,
                    'Luz techo'       => $inv->luz_techo,
                    'Tapa gasolina'   => $inv->tapa_gasolina,
                    'Batería'         => $inv->bateria,
                    'Plumillas'       => $inv->plumillas,
                    'Tapetes'         => $inv->tapetes,
                    'Cinturones'      => $inv->cinturones,
                    'Sensores'        => $inv->sensores,
                    'Cámara reversa'  => $inv->camara_reversa,
                    'Control alarma'  => $inv->control_alarma,
                    'Gato'            => $inv->gato,
                    'Extintor'        => $inv->extintor,
                    'Kit carretera'   => $inv->kit_carretera,
                ];
                $colores = ['B' => 'success', 'R' => 'warning', 'M' => 'danger'];
                $letras  = ['B' => 'Bueno', 'R' => 'Regular', 'M' => 'Malo'];
                @endphp
                <div class="row g-2">
                    @foreach($items as $nombre => $valor)
                    @if($valor)
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-{{ $colores[$valor] ?? 'secondary' }}-lt text-{{ $colores[$valor] ?? 'secondary' }}">
                                {{ $letras[$valor] ?? $valor }}
                            </span>
                            <span class="small text-muted">{{ $nombre }}</span>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                @if($inv->observaciones)
                <div class="mt-3 text-muted small">
                    <strong>Nota inventario:</strong> {{ $inv->observaciones }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Fotos de recepción --}}
    @if($ot->fotos->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Fotos del vehículo</h3>
                <span class="card-subtitle ms-2 text-muted">{{ $ot->fotos->count() }} foto(s) registrada(s) en recepción</span>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($ot->fotos as $foto)
                    <div class="col-6 col-md-3 col-lg-2">
                        <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank">
                            <img src="{{ asset('storage/' . $foto->ruta) }}"
                                 alt="{{ $foto->descripcion ?? 'Foto vehículo' }}"
                                 class="img-fluid rounded border"
                                 style="width:100%;height:100px;object-fit:cover">
                        </a>
                        @if($foto->descripcion)
                        <div class="text-muted text-truncate mt-1" style="font-size:.75rem">
                            {{ $foto->descripcion }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
