@extends('layouts.app')

@section('title', 'Mi Panel')
@section('page_title', 'Mi Panel')
@section('breadcrumb', isset($tecnico) ? $tecnico->nombre : 'Técnico')

@section('content')

@if(!$tecnico)
<div class="alert alert-warning">
    Tu usuario no tiene un técnico asociado. Contacta al administrador.
</div>
@else

{{-- KPIs del técnico --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card kpi-card {{ $kpis['en_proceso'] > 0 ? 'border-warning' : '' }}">
            <div class="card-body text-center py-3">
                <div class="kpi-value {{ $kpis['en_proceso'] > 0 ? 'text-warning' : '' }}">
                    {{ $kpis['en_proceso'] }}
                </div>
                <div class="kpi-label mt-1">En Proceso</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card">
            <div class="card-body text-center py-3">
                <div class="kpi-value text-secondary">{{ $kpis['pendientes'] }}</div>
                <div class="kpi-label mt-1">Pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card">
            <div class="card-body text-center py-3">
                <div class="kpi-value text-success">{{ $kpis['fin_este_mes'] }}</div>
                <div class="kpi-label mt-1">Fin. este mes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card">
            <div class="card-body text-center py-3">
                <div class="kpi-value text-primary">{{ $kpis['fin_total'] }}</div>
                <div class="kpi-label mt-1">Total histórico</div>
            </div>
        </div>
    </div>
</div>

{{-- Acceso rápido --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <a href="{{ route('mis-tareas.index') }}" class="btn btn-primary btn-lg w-100">
            Ver Mis Tareas Activas
            @if($kpis['en_proceso'] + $kpis['pendientes'] > 0)
            <span class="badge bg-white text-primary ms-2">{{ $kpis['en_proceso'] + $kpis['pendientes'] }}</span>
            @endif
        </a>
    </div>
    <div class="col-12 col-md-6">
        <a href="{{ route('mis-tareas.historial') }}" class="btn btn-outline-secondary btn-lg w-100">
            Ver Historial Completo
        </a>
    </div>
</div>

{{-- Especialidades --}}
<div class="card mb-3">
    <div class="card-body">
        @php
        $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
        @endphp
        <strong>Técnico:</strong> {{ $tecnico->nombre }}
        &nbsp;·&nbsp;
        <strong>Especialidades:</strong>
        @foreach($tecnico->especialidades ?: [] as $esp)
        <span class="badge bg-blue-lt ms-1">{{ $nombresEsp[$esp] ?? $esp }}</span>
        @endforeach
    </div>
</div>

{{-- Tareas activas (resumen) --}}
@if($tareasActivas->count() > 0)
<h3 class="mb-2">Tareas asignadas ahora</h3>
@foreach($tareasActivas as $trabajo)
@php $ot = $trabajo->ot; @endphp
<div class="card mb-2 border-start border-4 {{ $trabajo->estado === 'EN_PROCESO' ? 'border-warning' : 'border-secondary' }}">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold text-primary"># {{ $ot->numero_ot }}</span>
            <span class="badge bg-blue-lt">{{ $ot->vehiculo->placa }}</span>
            <span class="badge {{ $trabajo->estado === 'EN_PROCESO' ? 'bg-warning text-dark' : 'bg-secondary-lt' }}">
                {{ $trabajo->estado === 'EN_PROCESO' ? 'En proceso' : 'Pendiente' }}
            </span>
            <span class="text-muted small">{{ $ot->vehiculo->marca->nombre }} · {{ $ot->empresaCliente->nombre }}</span>
        </div>
    </div>
</div>
@endforeach
<a href="{{ route('mis-tareas.index') }}" class="btn btn-outline-primary btn-sm mt-1">Ver todas mis tareas →</a>
@else
<div class="alert alert-success">
    No tienes tareas pendientes en este momento.
</div>
@endif

@endif
@endsection
