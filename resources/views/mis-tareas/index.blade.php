@extends('layouts.app')

@section('title', 'Mis Tareas')
@section('page_title', 'Mis Tareas')
@section('breadcrumb', isset($tecnico) ? $tecnico->nombre : 'Técnico')

@section('page_actions')
<a href="{{ route('mis-tareas.historial') }}" class="btn btn-outline-secondary btn-sm">
    Historial completo
</a>
@endsection

@section('content')

@if(!isset($tecnico) || !$tecnico)
<div class="alert alert-warning">
    Tu usuario no tiene un técnico asociado. Contacta al administrador.
</div>
@else

{{-- KPIs --}}
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
                <div class="kpi-value text-success" style="font-size:1.1rem">
                    @if($ganadoMes > 0)
                        ${{ number_format($ganadoMes/1000, 0) }}K
                    @else
                        <span class="text-muted" style="font-size:0.9rem">Sin asignar</span>
                    @endif
                </div>
                <div class="kpi-label mt-1">Ganado este mes</div>
            </div>
        </div>
    </div>
</div>

{{-- Tareas activas --}}
<h3 class="mb-3">Tareas Asignadas</h3>

@php $nombresEsp2 = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico']; @endphp

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
                    <span class="badge {{ $enProceso ? 'bg-warning text-dark' : 'bg-secondary-lt' }}">
                        {{ $nombresEsp2[$trabajo->especialidad] ?? $trabajo->especialidad }}
                        — {{ $enProceso ? 'En proceso' : 'Pendiente' }}
                    </span>
                    <a href="{{ route('mis-tareas.vehiculo', $trabajo) }}"
                       class="btn btn-sm btn-outline-secondary">
                        Ver vehículo
                    </a>
                </div>
                <div class="text-muted small mt-1">
                    {{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}
                    · {{ $ot->empresaCliente->nombre }}
                    · Asignado: {{ ($trabajo->fecha_asignacion ?? $trabajo->created_at)->format('d/m/Y') }}
                </div>
            </div>
            <div class="col-auto text-end">
                <x-semaforo :estado="$ot->estado_semaforo" />
                @if($ot->salida_estimada)
                <div class="text-muted small mt-1">Entrega: {{ $ot->salida_estimada->format('d/m/Y') }}</div>
                @endif
                @if($trabajo->inicio_en)
                <div class="text-muted small">Inicio: {{ $trabajo->inicio_en->format('d/m H:i') }}</div>
                @endif
                <div class="text-muted small mt-1">
                    @if($trabajo->valor_liquidar > 0)
                        <span class="text-success fw-semibold">
                            Valor: ${{ number_format($trabajo->valor_liquidar, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-muted">Valor: pendiente de asignar</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Observaciones de la OT --}}
        @if($ot->observaciones)
        <div class="alert alert-info py-2 mb-3 small">
            <strong>Obs. OT:</strong> {{ $ot->observaciones }}
        </div>
        @endif

        {{-- Historial de comentarios --}}
        @if($trabajo->historialComentarios->count() > 0)
        <div class="mb-3">
            <div class="fw-semibold small text-muted mb-1">Comentarios</div>
            <div class="border rounded p-2 bg-light" style="max-height:180px;overflow-y:auto">
                @foreach($trabajo->historialComentarios as $com)
                <div class="d-flex gap-2 mb-2 {{ !$loop->last ? 'border-bottom pb-2' : '' }}">
                    <div class="flex-shrink-0">
                        <span class="badge bg-secondary-lt">{{ $com->tecnico->nombre ?? '—' }}</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small">{{ $com->texto }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ $com->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Fotos del técnico --}}
        @if($trabajo->fotos->count() > 0)
        <div class="mb-3">
            <div class="fw-semibold small text-muted mb-1">Fotos subidas ({{ $trabajo->fotos->count() }})</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($trabajo->fotos as $foto)
                <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank" class="d-block">
                    <img src="{{ asset('storage/' . $foto->ruta) }}"
                         alt="{{ $foto->descripcion ?? 'Foto' }}"
                         class="rounded border"
                         style="height:64px;width:64px;object-fit:cover">
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Acciones --}}
        <div class="row g-2">

            {{-- Iniciar (con fecha opcional) --}}
            @if($trabajo->estado === 'PENDIENTE')
            <div class="col-12">
                <form method="POST" action="{{ route('mis-tareas.iniciar', $trabajo) }}"
                      class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label class="form-label mb-1 small">
                            Fecha de inicio
                            <span class="text-muted fw-normal">(opcional, por defecto hoy)</span>
                        </label>
                        @php
                            $minAsignacion = $trabajo->fecha_asignacion
                                ? $trabajo->fecha_asignacion->toDateString()
                                : $trabajo->created_at->toDateString();
                            $minInicio = $ot->fecha_autorizacion
                                ? max($minAsignacion, \Carbon\Carbon::parse($ot->fecha_autorizacion)->toDateString())
                                : $minAsignacion;
                        @endphp
                        <input type="date" name="inicio_en" class="form-control form-control-sm"
                               value="{{ now()->toDateString() }}"
                               min="{{ $minInicio }}"
                               max="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-warning"
                                data-confirm="¿Iniciar el trabajo en OT #{{ $ot->numero_ot }}?">
                            ▶ Iniciar Trabajo
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Formulario unificado: comentario + fotos --}}
            <div class="col-12">
                <form method="POST" action="{{ route('mis-tareas.guardar', $trabajo) }}"
                      enctype="multipart/form-data" class="row g-2">
                    @csrf

                    @error('guardar')
                    <div class="col-12">
                        <div class="text-danger small">{{ $message }}</div>
                    </div>
                    @enderror

                    <div class="col-12">
                        <textarea name="comentario" class="form-control form-control-sm"
                                  rows="2" maxlength="1000"
                                  placeholder="Comentario de avance (opcional si subes fotos)..."></textarea>
                    </div>

                    <div class="col-12 col-md-7">
                        <label class="form-label small text-muted mb-1">
                            Fotos <span class="text-muted fw-normal">(opcional, máx. 5 de 5MB c/u)</span>
                        </label>
                        <input type="file" name="fotos[]" accept="image/*" multiple
                               class="form-control form-control-sm">
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label small text-muted mb-1">Descripción de las fotos</label>
                        <input type="text" name="descripcion" class="form-control form-control-sm"
                               placeholder="Ej: Daño lateral izquierdo" maxlength="150">
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                    </div>
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
<h3 class="mt-4 mb-3">
    Finalizados este mes
    <a href="{{ route('mis-tareas.historial') }}" class="btn btn-outline-secondary btn-sm ms-2">
        Ver historial completo
    </a>
</h3>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th># OT</th>
                    <th>Placa</th>
                    <th>Función</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th class="text-end">Valor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($finalizados as $t)
                <tr>
                    <td class="fw-bold text-primary">{{ $t->ot->numero_ot }}</td>
                    <td><span class="badge bg-blue-lt">{{ $t->ot->vehiculo->placa }}</span></td>
                    <td><span class="badge bg-secondary-lt">{{ $nombresEsp2[$t->especialidad] ?? $t->especialidad }}</span></td>
                    <td class="small text-muted">{{ $t->inicio_en?->format('d/m H:i') ?? '—' }}</td>
                    <td class="small text-muted">{{ $t->fin_en?->format('d/m H:i') ?? '—' }}</td>
                    <td class="text-end small">
                        @if($t->valor_liquidar > 0)
                            <span class="text-success">${{ number_format($t->valor_liquidar, 0, ',', '.') }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('mis-tareas.detalle', $t) }}"
                           class="btn btn-xs btn-outline-secondary">Ver</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif
@endsection
