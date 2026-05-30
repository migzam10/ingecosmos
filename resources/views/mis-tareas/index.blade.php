@extends('layouts.app')

@section('title', 'Mis Tareas')
@section('page_title', 'Mis Tareas')
@section('breadcrumb', isset($tecnico) ? $tecnico->nombre : 'Técnico')

@section('content')

@if(!isset($tecnico) || !$tecnico)
<div class="alert alert-warning">
    Tu usuario no tiene un técnico asociado. Contacta al administrador.
</div>
@else

{{-- Resumen del técnico --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-warning">{{ $trabajos->where('estado','EN_PROCESO')->count() }}</div>
                <div class="kpi-label mt-1">En Proceso</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-secondary">{{ $trabajos->where('estado','PENDIENTE')->count() }}</div>
                <div class="kpi-label mt-1">Pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-success">{{ $finalizados->count() }}</div>
                <div class="kpi-label mt-1">Fin. este mes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-info">
                    @php
                    $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
                    $espLegibles = array_map(fn($e) => $nombresEsp[$e] ?? $e, $tecnico->especialidades ?: []);
                    @endphp
                    {{ implode(', ', $espLegibles) ?: '—' }}
                </div>
                <div class="kpi-label mt-1">Especialidades</div>
            </div>
        </div>
    </div>
</div>

{{-- Tareas activas --}}
<h3 class="mb-3">Tareas Asignadas</h3>

@forelse($trabajos as $trabajo)
@php
$ot = $trabajo->ot;
$enProceso = $trabajo->estado === 'EN_PROCESO';
$bordeClase = $enProceso ? 'border-warning' : 'border-secondary';
@endphp

<div class="card mb-3 border-start border-4 {{ $bordeClase }}">
    <div class="card-body">

        {{-- Encabezado --}}
        <div class="row align-items-center mb-3">
            <div class="col">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="fw-bold text-primary fs-5"># {{ $ot->numero_ot }}</span>
                    <span class="badge bg-blue-lt fw-bold">{{ $ot->vehiculo->placa }}</span>
                    <span class="badge bg-secondary-lt">{{ $ot->area }}</span>
                    @php $nombresEsp2 = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico']; @endphp
                    <span class="badge {{ $enProceso ? 'bg-warning text-dark' : 'bg-secondary-lt' }}">
                        {{ $nombresEsp2[$trabajo->especialidad] ?? $trabajo->especialidad }}
                        — {{ $enProceso ? 'En proceso' : 'Pendiente' }}
                    </span>
                </div>
                <div class="text-muted small mt-1">
                    {{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}
                    · {{ $ot->empresaCliente->nombre }}
                </div>
            </div>
            <div class="col-auto text-end">
                <x-semaforo :estado="$ot->estado_semaforo" />
                @if($ot->salida_estimada)
                <div class="text-muted small mt-1">
                    Entrega: {{ $ot->salida_estimada->format('d/m/Y') }}
                </div>
                @endif
                @if($trabajo->inicio_en)
                <div class="text-muted small">
                    Inicio: {{ $trabajo->inicio_en->format('d/m H:i') }}
                </div>
                @endif
            </div>
        </div>

        {{-- Observaciones de la OT --}}
        @if($ot->observaciones)
        <div class="alert alert-light py-2 mb-3 small">
            <strong>Obs. OT:</strong> {{ $ot->observaciones }}
        </div>
        @endif

        {{-- Comentario actual --}}
        @if($trabajo->comentarios)
        <div class="mb-3 p-2 bg-light rounded small">
            <strong>Mi comentario:</strong> {{ $trabajo->comentarios }}
        </div>
        @endif

        {{-- Acciones --}}
        <div class="row g-2">

            {{-- Iniciar --}}
            @if($trabajo->estado === 'PENDIENTE')
            <div class="col-12 col-md-auto">
                <form method="POST" action="{{ route('mis-tareas.iniciar', $trabajo) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100"
                            data-confirm="¿Iniciar el trabajo en OT #{{ $ot->numero_ot }}?">
                        ▶ Iniciar Trabajo
                    </button>
                </form>
            </div>
            @endif

            {{-- Comentar --}}
            <div class="col-12 col-md-6">
                <form method="POST" action="{{ route('mis-tareas.comentar', $trabajo) }}"
                      class="d-flex gap-2">
                    @csrf
                    <input type="text" name="comentario" class="form-control form-control-sm"
                           placeholder="Agregar comentario..."
                           value="{{ $trabajo->comentarios }}" maxlength="500">
                    <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                        Guardar
                    </button>
                </form>
            </div>

            {{-- Finalizar --}}
            @if($trabajo->estado === 'EN_PROCESO')
            <div class="col-12 col-md-auto ms-md-auto">
                <form method="POST" action="{{ route('mis-tareas.finalizar', $trabajo) }}">
                    @csrf
                    <button type="submit" class="btn btn-success w-100"
                            data-confirm="¿Marcar como FINALIZADO el trabajo en OT #{{ $ot->numero_ot }}? Esta acción no se puede deshacer.">
                        ✓ Finalizar Trabajo
                    </button>
                </form>
            </div>
            @endif

        </div>

    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <div class="mb-2" style="font-size:2rem;">✓</div>
        No tienes tareas pendientes asignadas.
    </div>
</div>
@endforelse

{{-- Finalizados del mes --}}
@if($finalizados->count() > 0)
<h3 class="mt-4 mb-3">Finalizados este mes</h3>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th># OT</th>
                    <th>Placa</th>
                    <th>Especialidad</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($finalizados as $t)
                <tr>
                    <td class="fw-bold text-primary">{{ $t->ot->numero_ot }}</td>
                    <td><span class="badge bg-blue-lt">{{ $t->ot->vehiculo->placa }}</span></td>
                    <td><span class="badge bg-secondary-lt">{{ $t->especialidad }}</span></td>
                    <td class="small text-muted">{{ $t->inicio_en?->format('d/m H:i') ?? '—' }}</td>
                    <td class="small text-muted">{{ $t->fin_en?->format('d/m H:i') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif
@endsection
