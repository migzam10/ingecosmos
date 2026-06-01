@extends('layouts.app')

@section('title', 'Detalle OT #' . $trabajo->ot->numero_ot)
@section('page_title', 'Detalle Trabajo — OT #' . $trabajo->ot->numero_ot)
@section('breadcrumb', 'Mis Tareas')

@section('page_actions')
<a href="{{ route('mis-tareas.index') }}" class="btn btn-outline-secondary btn-sm">
    ← Mis Tareas
</a>
<a href="{{ route('mis-tareas.historial') }}" class="btn btn-outline-secondary btn-sm ms-1">
    Historial
</a>
@endsection

@section('content')

@php
$ot = $trabajo->ot;
$nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
@endphp

<div class="row g-3">

    {{-- Info del trabajo --}}
    <div class="col-12 col-md-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Información</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 small">OT</dt>
                    <dd class="col-7 fw-bold text-primary"># {{ $ot->numero_ot }}</dd>

                    <dt class="col-5 small">Placa</dt>
                    <dd class="col-7"><span class="badge bg-blue-lt">{{ $ot->vehiculo->placa }}</span></dd>

                    <dt class="col-5 small">Vehículo</dt>
                    <dd class="col-7 small">{{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}</dd>

                    <dt class="col-5 small">Cliente</dt>
                    <dd class="col-7 small">{{ $ot->empresaCliente->nombre }}</dd>

                    <dt class="col-5 small">Área</dt>
                    <dd class="col-7 small">{{ $ot->area }}</dd>

                    <dt class="col-5 small">Función</dt>
                    <dd class="col-7">
                        <span class="badge bg-secondary-lt">{{ $nombresEsp[$trabajo->especialidad] ?? $trabajo->especialidad }}</span>
                    </dd>

                    <dt class="col-5 small">Estado</dt>
                    <dd class="col-7">
                        <span class="badge bg-success-lt">Finalizado</span>
                    </dd>

                    <dt class="col-5 small">Inicio</dt>
                    <dd class="col-7 small">{{ $trabajo->inicio_en?->format('d/m/Y H:i') ?? '—' }}</dd>

                    <dt class="col-5 small">Fin</dt>
                    <dd class="col-7 small">{{ $trabajo->fin_en?->format('d/m/Y H:i') ?? '—' }}</dd>

                    @if($trabajo->fin_en && $trabajo->inicio_en)
                    <dt class="col-5 small">Duración</dt>
                    <dd class="col-7 small">
                        {{ $trabajo->inicio_en->diffForHumans($trabajo->fin_en, true) }}
                    </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Comentarios --}}
    <div class="col-12 col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Historial de Comentarios
                    <span class="badge bg-secondary-lt ms-1">{{ $trabajo->historialComentarios->count() }}</span>
                </h3>
            </div>
            <div class="card-body p-0">
                @if($trabajo->historialComentarios->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($trabajo->historialComentarios as $com)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-blue-lt">{{ $com->tecnico->nombre ?? '—' }}</span>
                            <span class="text-muted" style="font-size:.75rem">{{ $com->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="small">{{ $com->texto }}</div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-3 text-muted text-center small">Sin comentarios registrados.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Fotos --}}
    @if($trabajo->fotos->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Fotos
                    <span class="badge bg-secondary-lt ms-1">{{ $trabajo->fotos->count() }}</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($trabajo->fotos as $foto)
                    <div class="col-6 col-md-3 col-lg-2">
                        <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank">
                            <img src="{{ asset('storage/' . $foto->ruta) }}"
                                 alt="{{ $foto->descripcion ?? 'Foto' }}"
                                 class="img-fluid rounded border"
                                 style="width:100%;height:100px;object-fit:cover">
                        </a>
                        @if($foto->descripcion)
                        <div class="text-muted text-truncate mt-1" style="font-size:.75rem">{{ $foto->descripcion }}</div>
                        @endif
                        <div class="text-muted" style="font-size:.7rem">{{ $foto->created_at->format('d/m H:i') }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
